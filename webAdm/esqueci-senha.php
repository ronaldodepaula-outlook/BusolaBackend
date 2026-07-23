<?php
/**
 * Página pública — solicitação de recuperação de senha (Fluxo 2, passo 1).
 * Standalone, fora do fluxo autenticado.
 */
require_once __DIR__ . '/classe/Config.php';
require_once __DIR__ . '/classe/ApiClient.php';

$api = new ApiClient(); // endpoint público — sem token de autenticação
$enviado = false;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $erro = 'Informe seu e-mail.';
    } else {
        $resp = $api->post('auth/recuperar-senha', ['email' => $email]);

        // A API sempre responde com sucesso genérico (não revela se o e-mail
        // existe) — exceto no caso de rate limit, que merece feedback claro.
        if ($resp['status'] === 429) {
            $erro = 'Muitas tentativas em pouco tempo. Aguarde um minuto e tente novamente.';
        } else {
            $enviado = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>busola — Esqueci minha senha</title>

  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root { --primary:#0946b0; --dark:#063080; --cyan:#11bbce; --green:#73ddb3; --bg:#f3f4f8; }
    *,*::before,*::after{box-sizing:border-box}
    html,body{height:100%;margin:0;font-family:'Nunito',sans-serif;background:var(--bg)}
    .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
    .card-auth{width:100%;max-width:420px;background:#fff;border-radius:14px;box-shadow:0 10px 40px rgba(9,70,176,.08);padding:2.2rem 2rem}
    .brand{display:flex;align-items:center;margin-bottom:1.6rem}
    .brand .brand-logo{height:64px;width:auto}
    h1{font-size:1.25rem;font-weight:800;color:#212a3e;margin-bottom:.3rem}
    .subtitle{color:#7a8aac;font-size:.88rem;margin-bottom:1.4rem}
    .form-label{font-weight:600;color:#374060;font-size:.85rem}
    .form-control{border:1.5px solid #d4ddf0;border-radius:8px;padding:.65rem .9rem;font-size:.92rem}
    .form-control:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(17,187,206,.15);outline:none}
    .btn-acao{background:linear-gradient(135deg,var(--primary),var(--dark));border:none;color:#fff;font-weight:700;font-size:.95rem;padding:.75rem;border-radius:8px;width:100%}
    .btn-acao:hover{opacity:.92;color:#fff}
    .alert{border-radius:8px;font-size:.88rem}
    .voltar-login{display:inline-block;margin-top:1.2rem;font-size:.88rem;color:var(--primary);font-weight:700;text-decoration:none}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card-auth">
    <div class="brand">
      <img src="assets/img/logo-header.png" alt="busola" class="brand-logo">
    </div>

    <?php if ($enviado): ?>
      <h1><i class="bi bi-envelope-check-fill text-success me-1"></i> Verifique seu e-mail</h1>
      <p class="subtitle mb-0">
        Se o e-mail informado estiver cadastrado, você receberá em poucos minutos as instruções para
        redefinir sua senha. O link é válido por 30 minutos.
      </p>
      <a href="login.php" class="voltar-login">&larr; Voltar para o login</a>

    <?php else: ?>
      <h1>Esqueci minha senha</h1>
      <p class="subtitle">Informe seu e-mail de acesso para receber o link de redefinição.</p>

      <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
      <?php endif; ?>

      <form method="POST" action="esqueci-senha.php">
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" placeholder="seu@email.com"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
        </div>
        <button type="submit" class="btn-acao"><i class="bi bi-send me-1"></i> Enviar link de recuperação</button>
      </form>
      <a href="login.php" class="voltar-login">&larr; Voltar para o login</a>
    <?php endif; ?>
  </div>
</div>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
