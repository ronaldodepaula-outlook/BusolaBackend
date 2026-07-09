<?php
require_once 'classe/Config.php';
require_once 'classe/ApiClient.php';
require_once 'classe/Auth.php';

if (Auth::estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro  = '';
$aviso = '';

if (isset($_GET['expired'])) {
    $aviso = 'Sua sessao expirou. Por favor, faca login novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha o e-mail e a senha.';
    } else {
        $resultado = Auth::login($email, $senha);
        if ($resultado['sucesso']) {
            header('Location: index.php');
            exit;
        }
        $erro = $resultado['mensagem'] ?? 'Erro ao autenticar.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>busola — Acesso à Plataforma</title>

  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --primary:  #0946b0;
      --dark:     #063080;
      --cyan:     #11bbce;
      --green:    #73ddb3;
      --bg:       #f3f4f8;
    }

    *, *::before, *::after { box-sizing: border-box; }

    html, body {
      height: 100%;
      margin: 0;
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
    }

    /* ── Layout split ─────────────────────────────────── */
    .login-wrapper {
      display: flex;
      min-height: 100vh;
    }

    /* Painel esquerdo — brand */
    .login-brand {
      flex: 0 0 44%;
      background: linear-gradient(145deg, var(--primary) 0%, var(--dark) 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 3rem 3.5rem;
      position: relative;
      overflow: hidden;
    }

    .login-brand::before {
      content: '';
      position: absolute;
      top: -80px; right: -80px;
      width: 320px; height: 320px;
      border-radius: 50%;
      background: rgba(17,187,206,.12);
    }

    .login-brand::after {
      content: '';
      position: absolute;
      bottom: -60px; left: -60px;
      width: 220px; height: 220px;
      border-radius: 50%;
      background: rgba(115,221,179,.1);
    }

    .brand-logo-wrap {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 2.5rem;
      position: relative;
      z-index: 1;
    }

    .brand-logo-wrap svg {
      width: 52px;
      height: 52px;
      flex-shrink: 0;
    }

    .brand-name-block .name {
      font-size: 2.2rem;
      font-weight: 800;
      color: #fff;
      line-height: 1;
      letter-spacing: -0.03em;
    }

    .brand-name-block .tagline {
      font-size: 0.6rem;
      font-weight: 700;
      color: var(--cyan);
      letter-spacing: 0.18em;
      text-transform: uppercase;
    }

    .brand-claim {
      color: rgba(255,255,255,.85);
      font-size: 1.15rem;
      font-weight: 300;
      line-height: 1.6;
      max-width: 320px;
      text-align: center;
      margin-bottom: 2.5rem;
      position: relative;
      z-index: 1;
    }

    .brand-claim strong {
      color: #fff;
      font-weight: 700;
    }

    .brand-features {
      list-style: none;
      padding: 0;
      margin: 0;
      position: relative;
      z-index: 1;
    }

    .brand-features li {
      display: flex;
      align-items: center;
      gap: 10px;
      color: rgba(255,255,255,.8);
      font-size: 0.88rem;
      margin-bottom: 0.8rem;
    }

    .brand-features li .feat-icon {
      width: 30px;
      height: 30px;
      background: rgba(255,255,255,.1);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: var(--green);
      font-size: 0.85rem;
    }

    /* Painel direito — formulário */
    .login-form-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }

    .login-card {
      width: 100%;
      max-width: 400px;
    }

    .login-card h1 {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 0.25rem;
    }

    .login-card .subtitle {
      color: #7a8aac;
      font-size: 0.88rem;
      margin-bottom: 1.8rem;
    }

    .form-label {
      font-weight: 600;
      color: #374060;
      font-size: 0.85rem;
    }

    .form-control {
      border: 1.5px solid #d4ddf0;
      border-radius: 8px;
      padding: 0.65rem 0.9rem;
      font-size: 0.92rem;
      transition: border-color .2s, box-shadow .2s;
    }

    .form-control:focus {
      border-color: var(--cyan);
      box-shadow: 0 0 0 3px rgba(17,187,206,.15);
      outline: none;
    }

    .input-group-text {
      background: #f0f5ff;
      border: 1.5px solid #d4ddf0;
      border-radius: 8px 0 0 8px;
      color: var(--primary);
    }

    .input-group .form-control {
      border-left: none;
      border-radius: 0 8px 8px 0;
    }

    .input-group:focus-within .input-group-text {
      border-color: var(--cyan);
    }

    .btn-entrar {
      background: linear-gradient(135deg, var(--primary), var(--dark));
      border: none;
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 0.75rem;
      border-radius: 8px;
      width: 100%;
      letter-spacing: 0.03em;
      transition: opacity .2s, transform .1s;
    }

    .btn-entrar:hover {
      opacity: 0.9;
      color: #fff;
    }

    .btn-entrar:active {
      transform: scale(0.99);
    }

    .alert-danger {
      background: #fff0f0;
      border: 1px solid #f5c2c7;
      border-left: 4px solid #dc3545;
      color: #842029;
      border-radius: 8px;
      font-size: 0.88rem;
    }

    .login-footer {
      margin-top: 2rem;
      text-align: center;
      color: #aab4c8;
      font-size: 0.78rem;
    }

    /* Responsivo — coluna única em mobile */
    @media (max-width: 768px) {
      .login-brand { display: none; }
      .login-form-panel { padding: 2rem 1.2rem; }
      .login-card { max-width: 100%; }
    }
  </style>
</head>

<body>
<div class="login-wrapper">

  <!-- ── Painel esquerdo: brand ─────────────────────── -->
  <div class="login-brand">
    <div class="brand-logo-wrap">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
        <defs>
          <linearGradient id="lg1" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#73ddb3"/>
            <stop offset="100%" stop-color="#11bbce"/>
          </linearGradient>
        </defs>
        <circle cx="26" cy="26" r="25" fill="rgba(255,255,255,.12)"/>
        <circle cx="26" cy="26" r="19" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="1.5"/>
        <path d="M26 10 L30.5 26 L26 22.5 L21.5 26 Z" fill="url(#lg1)"/>
        <path d="M26 42 L21.5 26 L26 29.5 L30.5 26 Z" fill="rgba(255,255,255,.3)"/>
        <circle cx="26" cy="26" r="3" fill="white"/>
      </svg>
      <div class="brand-name-block">
        <div class="name">busola</div>
        <div class="tagline">Gestão Inteligente de Riscos</div>
      </div>
    </div>

    <p class="brand-claim">
      Transformamos dados em<br>
      <strong>direção estratégica.</strong><br>
      Inteligência que orienta decisões.
    </p>

    <ul class="brand-features">
      <li>
        <span class="feat-icon"><i class="bi bi-bullseye"></i></span>
        Identifique e monitore riscos
      </li>
      <li>
        <span class="feat-icon"><i class="bi bi-graph-up-arrow"></i></span>
        Priorize com clareza
      </li>
      <li>
        <span class="feat-icon"><i class="bi bi-compass"></i></span>
        Tome decisões com confiança
      </li>
      <li>
        <span class="feat-icon"><i class="bi bi-shield-check"></i></span>
        Controle de acesso multi-nível
      </li>
    </ul>
  </div>

  <!-- ── Painel direito: formulário ───────────────── -->
  <div class="login-form-panel">
    <div class="login-card">

      <h1>Bem-vindo de volta</h1>
      <p class="subtitle">Acesse sua conta para continuar</p>

      <?php if ($aviso): ?>
      <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-clock-history flex-shrink-0"></i>
        <span><?php echo htmlspecialchars($aviso); ?></span>
      </div>
      <?php endif; ?>

      <?php if ($erro): ?>
      <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        <?php echo htmlspecialchars($erro); ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php">

        <div class="mb-3">
          <label for="email" class="form-label">E-mail</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input
              type="email"
              name="email"
              id="email"
              class="form-control"
              placeholder="seu@email.com"
              value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
              required
              autofocus
            >
          </div>
        </div>

        <div class="mb-4">
          <label for="senha" class="form-label">Senha</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input
              type="password"
              name="senha"
              id="senha"
              class="form-control"
              placeholder="••••••••"
              required
            >
          </div>
        </div>

        <button type="submit" class="btn-entrar">
          <i class="bi bi-box-arrow-in-right me-1"></i> Entrar na plataforma
        </button>

      </form>

      <div class="login-footer">
        busola &copy; <?php echo date('Y'); ?> &mdash; Gestão Inteligente de Riscos
      </div>
    </div>
  </div>

</div>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
