@component('emails.layout')
    <p style="color:#374060; font-size:15px; line-height:1.6; margin:0 0 16px;">
        Olá, <strong>{{ $nome }}</strong>!
    </p>

    <p style="color:#374060; font-size:15px; line-height:1.6; margin:0 0 24px;">
        Recebemos um pedido para redefinir a senha da sua conta na plataforma <strong>busola</strong>.
        Clique no botão abaixo para escolher uma nova senha.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="border-radius:8px; background:linear-gradient(135deg,#0946b0,#063080); background-color:#0946b0;">
                <a href="{{ $linkRedefinir }}"
                   style="display:inline-block; padding:14px 28px; color:#ffffff; font-weight:700; font-size:15px; text-decoration:none; border-radius:8px;">
                    Redefinir minha senha
                </a>
            </td>
        </tr>
    </table>

    <p style="color:#7a8aac; font-size:13px; line-height:1.6; margin:0 0 8px;">
        Este link é válido por <strong>{{ $minutosValidade }} minutos</strong> e só pode ser usado uma única vez.
        Se ele expirar, solicite uma nova recuperação na tela de login.
    </p>

    <p style="color:#7a8aac; font-size:13px; line-height:1.6; margin:0;">
        Se o botão não funcionar, copie e cole este endereço no seu navegador:<br>
        <span style="word-break:break-all;">{{ $linkRedefinir }}</span>
    </p>

    <p style="color:#aab4c8; font-size:12px; line-height:1.6; margin:24px 0 0;">
        Se você não solicitou esta alteração, pode ignorar este e-mail — sua senha atual continua válida.
    </p>
@endcomponent
