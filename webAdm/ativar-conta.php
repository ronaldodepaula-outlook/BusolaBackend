<?php
/**
 * Página pública — conclusão do Fluxo 1 (ativação de conta). Standalone,
 * fora do fluxo autenticado (sem Auth::exigirAuth()), no mesmo espírito do
 * responder.php já existente: fala com a API só via ApiClient sem token.
 */
require_once __DIR__ . '/classe/Config.php';
require_once __DIR__ . '/classe/ApiClient.php';

$token = trim($_GET['token'] ?? '');
$api = new ApiClient(); // endpoint público — sem token de autenticação

$erroToken = null;
$nomeUsuario = null;
$concluido = false;
$erroFormulario = null;

if ($token === '') {
    $erroToken = 'Link de ativação inválido. Verifique se você copiou o endereço completo enviado por e-mail.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirmacaoSenha = $_POST['confirmacao_senha'] ?? '';

    $resp = $api->post('auth/ativacao', [
        'token'             => $token,
        'senha'             => $senha,
        'confirmacao_senha' => $confirmacaoSenha,
    ]);

    if ($resp['success']) {
        $concluido = true;
    } else {
        $erroFormulario = $resp['data']['mensagem'] ?? 'Não foi possível concluir a ativação. Tente novamente.';
        $erros = $resp['data']['erros'] ?? [];
        if ($erros) {
            $erroFormulario = implode(' ', array_map(fn ($lista) => implode(' ', $lista), $erros));
        }
    }
} else {
    $respValidacao = $api->get("auth/ativacao/{$token}");
    if ($respValidacao['success']) {
        $nomeUsuario = $respValidacao['data']['dados']['nome'] ?? null;
    } else {
        $erroToken = $respValidacao['data']['mensagem'] ?? 'Este link de ativação não é mais válido.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>busola — Ativar conta</title>

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
    .req-list{font-size:.78rem;color:#7a8aac;margin:.4rem 0 1.2rem;padding-left:1.1rem}
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

    <?php if ($concluido): ?>
      <h1><i class="bi bi-check-circle-fill text-success me-1"></i> Conta ativada!</h1>
      <p class="subtitle">Sua senha foi definida com sucesso. Você já pode entrar na plataforma.</p>
      <a href="login.php" class="btn-acao text-center d-block" style="text-decoration:none;">Ir para o login</a>

    <?php elseif ($erroToken): ?>
      <h1>Link inválido</h1>
      <div class="alert alert-danger"><?php echo htmlspecialchars($erroToken); ?></div>
      <p class="subtitle mb-0">Solicite ao administrador do sistema um novo convite de ativação.</p>
      <a href="login.php" class="voltar-login">&larr; Voltar para o login</a>

    <?php else: ?>
      <h1>Ative sua conta</h1>
      <p class="subtitle">
        Olá<?php echo $nomeUsuario ? ', ' . htmlspecialchars($nomeUsuario) : ''; ?>! Defina sua senha para começar a usar a plataforma.
      </p>

      <?php if ($erroFormulario): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erroFormulario); ?></div>
      <?php endif; ?>

      <form method="POST" action="ativar-conta.php?token=<?php echo urlencode($token); ?>" id="formAtivar">
        <div class="mb-3">
          <label class="form-label">Nova senha</label>
          <input type="password" name="senha" id="senha" class="form-control" required minlength="8" autofocus>
        </div>
        <div class="mb-2">
          <label class="form-label">Confirmar senha</label>
          <input type="password" name="confirmacao_senha" id="confirmacao_senha" class="form-control" required minlength="8">
        </div>
        <ul class="req-list">
          <li>Mínimo de 8 caracteres</li>
          <li>Letras maiúsculas e minúsculas</li>
          <li>Ao menos um número e um símbolo</li>
        </ul>
        <button type="submit" class="btn-acao"><i class="bi bi-shield-check me-1"></i> Ativar minha conta</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  const forms = document.getElementById('formAtivar');
  if (forms) {
    forms.addEventListener('submit', function (e) {
      const senha = document.getElementById('senha').value;
      const confirmacao = document.getElementById('confirmacao_senha').value;
      const politica = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/;

      if (senha !== confirmacao) {
        e.preventDefault();
        alert('A confirmação não coincide com a nova senha.');
        return;
      }
      if (!politica.test(senha)) {
        e.preventDefault();
        alert('A senha precisa ter no mínimo 8 caracteres, com letras maiúsculas, minúsculas, número e símbolo.');
      }
    });
  }
</script>
</body>
</html>
