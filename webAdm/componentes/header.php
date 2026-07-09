<?php
// Auth deve estar inicializado antes de incluir este componente
?>
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="?paginas=home" class="logo d-flex align-items-center w-auto text-decoration-none">

      <!-- Ícone SVG da Busola -->
      <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36">
        <defs>
          <linearGradient id="bsl-grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#11bbce"/>
            <stop offset="100%" stop-color="#0946b0"/>
          </linearGradient>
        </defs>
        <circle cx="18" cy="18" r="17" fill="url(#bsl-grad)"/>
        <circle cx="18" cy="18" r="13" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="1"/>
        <path d="M18 7 L21.5 18 L18 15.5 L14.5 18 Z" fill="white"/>
        <path d="M18 29 L14.5 18 L18 20.5 L21.5 18 Z" fill="rgba(255,255,255,.35)"/>
        <circle cx="18" cy="18" r="2.2" fill="white"/>
      </svg>

      <!-- Nome e tagline -->
      <span class="d-none d-lg-flex logo-text flex-column">
        <span class="brand-name">busola</span>
        <span class="brand-tagline">Gestão Inteligente de Riscos</span>
      </span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>

  <div class="search-bar ms-3">
    <form class="search-form d-flex align-items-center" method="GET" action="?">
      <input type="hidden" name="paginas" value="busca">
      <input type="text" name="q" placeholder="Buscar..." title="Digite para buscar">
      <button type="submit" title="Buscar"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <!-- Notificações -->
      <li class="nav-item dropdown d-none d-md-block">
        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-bell"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
          <li class="dropdown-header">
            Nenhuma notificação nova
          </li>
        </ul>
      </li>

      <!-- Perfil -->
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <img src="assets/img/profile-img.jpg" alt="Perfil" class="rounded-circle">
          <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo htmlspecialchars(Auth::getNome()); ?></span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6><?php echo htmlspecialchars(Auth::getNome()); ?></h6>
            <span><?php echo htmlspecialchars(Auth::getTipoLabel()); ?></span>
          </li>
          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="?paginas=perfil">
              <i class="bi bi-person"></i>
              <span>Meu Perfil</span>
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Sair</span>
            </a>
          </li>
        </ul>
      </li>

    </ul>
  </nav>

</header>
