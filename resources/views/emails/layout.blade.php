<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f8; font-family: Arial, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f8; padding:32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden;">
          <tr>
            <td style="background:linear-gradient(135deg,#0946b0,#11bbce); background-color:#0946b0; padding:28px 32px;">
              <span style="color:#ffffff; font-size:22px; font-weight:800; letter-spacing:-0.02em;">busola</span><br>
              <span style="color:rgba(255,255,255,.85); font-size:11px; font-weight:700; letter-spacing:0.15em; text-transform:uppercase;">
                Gestão Inteligente de Riscos
              </span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              {{ $slot }}
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px; background:#f8f9fc; text-align:center;">
              <span style="color:#9aa5bd; font-size:12px;">
                busola &copy; {{ date('Y') }} &mdash; Gestão Inteligente de Riscos
              </span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
