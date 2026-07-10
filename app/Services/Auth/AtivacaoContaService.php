<?php

namespace App\Services\Auth;

use App\Mail\ConviteAtivacaoContaMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Orquestra o Fluxo 1 (ativação de usuário criado sem senha pelo
 * administrador). A mecânica de token/senha em si vive em
 * {@see TokenSenhaService} — esta classe só decide QUANDO e PARA QUEM
 * disparar o convite, e qual e-mail enviar.
 */
class AtivacaoContaService
{
    private const TTL_HORAS = 24;

    public function __construct(
        private readonly TokenSenhaService $tokenSenhaService,
    ) {
    }

    /**
     * Gera o link de ativação e envia o e-mail de convite. Chamado pela
     * criação de usuário (UsuarioController::store) quando nenhuma senha é
     * informada.
     */
    public function convidar(User $user): void
    {
        $tokenEmClaro = $this->tokenSenhaService->gerar($user, now()->addHours(self::TTL_HORAS));

        Mail::to($user)->send(new ConviteAtivacaoContaMail($user, $tokenEmClaro));

        Log::info('Convite de ativação de conta enviado.', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);
    }

    /**
     * Confirma que um token de ativação ainda é válido, sem consumi-lo —
     * usado pela tela pública para decidir se mostra o formulário de senha
     * ou uma mensagem de link inválido/expirado.
     *
     * @throws \App\Exceptions\Auth\TokenInvalidoOuExpiradoException
     */
    public function validarToken(string $tokenEmClaro): User
    {
        return $this->tokenSenhaService->validarSemConsumir($tokenEmClaro);
    }

    /**
     * Conclui a ativação: define a senha escolhida pelo usuário, ativa a
     * conta e invalida o token — tudo em uma única transação (ver
     * {@see TokenSenhaService::consumirEDefinirSenha()}).
     *
     * @throws \App\Exceptions\Auth\TokenInvalidoOuExpiradoException
     */
    public function ativar(string $tokenEmClaro, string $novaSenha): User
    {
        $user = $this->tokenSenhaService->consumirEDefinirSenha($tokenEmClaro, $novaSenha);

        Log::info('Conta ativada com sucesso pelo próprio usuário.', ['user_id' => $user->id]);

        return $user;
    }
}
