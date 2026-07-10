<?php

namespace App\Services\Auth;

use App\Mail\RecuperacaoSenhaMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Orquestra o Fluxo 2 (recuperação de senha, autoatendida pelo usuário a
 * partir da tela de login). A mecânica de token/senha em si vive em
 * {@see TokenSenhaService}.
 */
class RecuperacaoSenhaService
{
    private const TTL_MINUTOS = 30;

    public function __construct(
        private readonly TokenSenhaService $tokenSenhaService,
    ) {
    }

    /**
     * Ponto central do requisito "não revelar se o e-mail existe": este
     * método NUNCA lança exceção nem retorna algo que distinga "e-mail não
     * encontrado" de "e-mail encontrado, link enviado" — o Controller
     * sempre devolve a mesma mensagem genérica de sucesso,
     * independentemente do que aconteceu aqui dentro.
     *
     * Contas bloqueadas pelo administrador (`status = bloqueado`) também não
     * recebem o link — recuperar a senha não pode ser uma forma de burlar um
     * bloqueio administrativo.
     */
    public function solicitar(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user || $user->status === 'bloqueado') {
            Log::info('Recuperação de senha solicitada para e-mail sem envio de link.', [
                'email'  => $email,
                'motivo' => $user ? 'conta bloqueada' : 'e-mail não cadastrado',
            ]);

            return;
        }

        $tokenEmClaro = $this->tokenSenhaService->gerar($user, now()->addMinutes(self::TTL_MINUTOS));

        Mail::to($user)->send(new RecuperacaoSenhaMail($user, $tokenEmClaro));

        Log::info('E-mail de recuperação de senha enviado.', ['user_id' => $user->id]);
    }

    /**
     * Confirma que um token de recuperação ainda é válido, sem consumi-lo —
     * usado pela tela pública para decidir se mostra o formulário de nova
     * senha ou uma mensagem de link inválido/expirado.
     *
     * @throws \App\Exceptions\Auth\TokenInvalidoOuExpiradoException
     */
    public function validarToken(string $tokenEmClaro): User
    {
        return $this->tokenSenhaService->validarSemConsumir($tokenEmClaro);
    }

    /**
     * Conclui a recuperação: define a nova senha escolhida pelo usuário e
     * invalida o token — tudo em uma única transação (ver
     * {@see TokenSenhaService::consumirEDefinirSenha()}).
     *
     * @throws \App\Exceptions\Auth\TokenInvalidoOuExpiradoException
     */
    public function redefinir(string $tokenEmClaro, string $novaSenha): User
    {
        $user = $this->tokenSenhaService->consumirEDefinirSenha($tokenEmClaro, $novaSenha);

        Log::info('Senha redefinida com sucesso pelo próprio usuário.', ['user_id' => $user->id]);

        return $user;
    }
}
