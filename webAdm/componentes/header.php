<?php
// Auth deve estar inicializado antes de incluir este componente
?>
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="?paginas=home" class="logo d-flex align-items-center w-auto text-decoration-none">

      <!-- Logo oficial da Busola (ícone + nome + tagline já compostos na imagem) -->
      <img src="assets/img/logo-header.png" alt="busola" class="logo-icon">
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
          <img src="<?php echo htmlspecialchars(Auth::getFotoUrl() ?? 'assets/img/profile-img.jpg'); ?>" alt="Perfil" class="rounded-circle" id="headerFotoPerfil">
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
