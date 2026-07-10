<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail do Fluxo 1 — convite para o usuário recém-criado definir sua
 * própria senha e ativar a conta.
 *
 * Não implementa `ShouldQueue` deliberadamente: o link expira em 24h e este
 * ambiente não tem garantia de um worker de fila em execução contínua
 * (`QUEUE_CONNECTION=database` sem supervisor configurado) — enviar de forma
 * síncrona garante que a tentativa de entrega aconteça imediatamente. Basta
 * adicionar `implements ShouldQueue` aqui quando um worker estiver
 * confirmado em produção.
 */
class ConviteAtivacaoContaMail extends Mailable
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
            subject: 'Ative sua conta — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.convite-ativacao',
            with: [
                'nome'          => $this->user->nome,
                'linkAtivacao'  => config('frontend.url') . '/ativar-conta.php?token=' . $this->tokenEmClaro,
                'horasValidade' => 24,
            ],
        );
    }
}
