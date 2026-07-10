<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail do Fluxo 2 — link para o usuário redefinir a própria senha.
 *
 * Não implementa `ShouldQueue` pelo mesmo motivo do
 * {@see ConviteAtivacaoContaMail}: o link expira em apenas 30 minutos, prazo
 * curto demais para depender de um worker de fila sem garantia de estar em
 * execução neste ambiente.
 */
class RecuperacaoSenhaMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $tokenEmClaro,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperação de senha — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.recuperacao-senha',
            with: [
                'nome'           => $this->user->nome,
                'linkRedefinir'  => config('frontend.url') . '/redefinir-senha.php?token=' . $this->tokenEmClaro,
                'minutosValidade' => 30,
            ],
        );
    }
}
