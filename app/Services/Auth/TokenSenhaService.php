<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\TokenInvalidoOuExpiradoException;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Mecânica de "token de uso único com expiração" e da troca de senha que ele
 * autoriza — usada tanto pela ativação de conta (Fluxo 1) quanto pela
 * recuperação de senha (Fluxo 2). As duas colunas reaproveitadas
 * (`token_reset_senha`, `token_reset_expira_em`) já existem na tabela
 * `users` desde a migration original — nunca chegaram a ser usadas por
 * nenhuma funcionalidade em produção.
 *
 * Reaproveitar essas duas colunas — em vez de criar uma tabela dedicada de
 * tokens — é uma escolha deliberada: por design, um usuário nunca precisa
 * de mais de um token pendente ao mesmo tempo (gerar um novo token invalida
 * qualquer token anterior não utilizado, o que é uma propriedade de
 * segurança desejável, não um efeito colateral), então não há necessidade
 * de histórico nem de rotina de limpeza de tokens expirados.
 *
 * "Consumir o token" e "definir a nova senha" são tratados como uma única
 * operação atômica (ver {@see consumirEDefinirSenha()}), de propósito: tanto
 * a ativação quanto a recuperação, ao serem concluídas com sucesso, devem
 * deixar a conta em estado utilizável (`status = ativo`,
 * `primeiro_acesso = false`) — inclusive quando um usuário que nunca ativou
 * a conta decide simplesmente usar "esqueci minha senha" em vez do link de
 * ativação original. Centralizar essa regra aqui evita que as duas
 * orquestrações (ativação/recuperação) divirjam e deixem a conta num estado
 * inconsistente (senha definida, mas ainda marcada como inativa).
 *
 * O valor gravado no banco é sempre um hash SHA-256 do token — o valor em
 * claro só existe em memória, o tempo suficiente para compor o e-mail, e
 * nunca é persistido nem logado.
 */
class TokenSenhaService
{
    /**
     * Gera um novo token de uso único para o usuário, substituindo (e
     * portanto invalidando) qualquer token anterior ainda não utilizado.
     *
     * @return string o token em claro — deve ser enviado por e-mail, nunca
     *                 exposto em respostas HTTP ou logs.
     */
    public function gerar(User $user, CarbonInterface $expiraEm): string
    {
        $tokenEmClaro = bin2hex(random_bytes(32)); // 256 bits de entropia

        $user->update([
            'token_reset_senha'     => $this->hash($tokenEmClaro),
            'token_reset_expira_em' => $expiraEm,
        ]);

        return $tokenEmClaro;
    }

    /**
     * Localiza o usuário titular de um token válido (existe e não expirou),
     * sem consumi-lo. Uso previsto: telas que precisam confirmar que o link
     * ainda é válido antes de exibir o formulário de senha.
     *
     * @throws TokenInvalidoOuExpiradoException
     */
    public function validarSemConsumir(string $tokenEmClaro): User
    {
        $user = User::where('token_reset_senha', $this->hash($tokenEmClaro))->first();

        if (! $this->tokenAindaValido($user)) {
            throw new TokenInvalidoOuExpiradoException();
        }

        return $user;
    }

    /**
     * Operação atômica: valida o token (com lock de escrita na linha do
     * usuário, para evitar que duas requisições concorrentes consumam o
     * mesmo token duas vezes), define a nova senha, garante que a conta
     * fique ativa e marca o token como usado.
     *
     * @throws TokenInvalidoOuExpiradoException
     */
    public function consumirEDefinirSenha(string $tokenEmClaro, string $novaSenha): User
    {
        return DB::transaction(function () use ($tokenEmClaro, $novaSenha) {
            $user = User::where('token_reset_senha', $this->hash($tokenEmClaro))
                ->lockForUpdate()
                ->first();

            if (! $this->tokenAindaValido($user)) {
                throw new TokenInvalidoOuExpiradoException();
            }

            $user->update([
                'senha'             => Hash::make($novaSenha),
                'status'            => 'ativo',
                'primeiro_acesso'   => false,
                'token_reset_senha'     => null,
                'token_reset_expira_em' => null,
            ]);

            return $user;
        });
    }

    private function tokenAindaValido(?User $user): bool
    {
        if (! $user || ! $user->token_reset_senha || ! $user->token_reset_expira_em) {
            return false;
        }

        return $user->token_reset_expira_em->isFuture();
    }

    private function hash(string $tokenEmClaro): string
    {
        return hash('sha256', $tokenEmClaro);
    }
}
