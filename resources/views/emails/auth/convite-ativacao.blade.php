@component('emails.layout')
    <p style="color:#374060; font-size:15px; line-height:1.6; margin:0 0 16px;">
        Olá, <strong>{{ $nome }}</strong>!
    </p>

    <p style="color:#374060; font-size:15px; line-height:1.6; margin:0 0 24px;">
        Uma conta foi criada para você na plataforma <strong>busola</strong>. Para começar a usá-la,
        defina sua senha de acesso clicando no botão abaixo.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="border-radius:8px; background:linear-gradient(135deg,#0946b0,#063080); background-color:#0946b0;">
                <a href="{{ $linkAtivacao }}"
                   style="display:inline-block; padding:14px 28px; color:#ffffff; font-weight:700; font-size:15px; text-decoration:none; border-radius:8px;">
                    Ativar minha conta
                </a>
            </td>
        </tr>
    </table>

    <p style="color:#7a8aac; font-size:13px; line-height:1.6; margin:0 0 8px;">
        Este link é válido por <strong>{{ $horasValidade }} horas</strong> e só pode ser usado uma única vez.
        Se ele expirar, solicite ao administrador do sistema um novo convite.
    </p>

    <p style="color:#7a8aac; font-size:13px; line-height:1.6; margin:0;">
        Se o botão não funcionar, copie e cole este endereço no seu navegador:<br>
        <span style="word-break:break-all;">{{ $linkAtivacao }}</span>
    </p>

    <p style="color:#aab4c8; font-size:12px; line-height:1.6; margin:24px 0 0;">
        Se você não esperava este e-mail, pode ignorá-lo com segurança.
    </p>
@endcomponent
