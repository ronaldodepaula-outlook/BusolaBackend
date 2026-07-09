<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    </div>
    <span class="brand-name"><?= SITE_NAME ?></span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <!-- Principal -->
    <div class="nav-group">
      <div class="nav-group-title">Principal</div>

      <a href="<?= SITE_URL ?>/index.php"
         class="nav-item <?= $current === 'index.php' ? 'active' : '' ?>"
         data-page="index.php" data-tip="Dashboard">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        </span>
        <span class="nav-label">Dashboard</span>
      </a>

      <a href="<?= SITE_URL ?>/pages/analytics.php"
         class="nav-item <?= $current === 'analytics.php' ? 'active' : '' ?>"
         data-page="analytics.php" data-tip="Analytics">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6h-6z"/></svg>
        </span>
        <span class="nav-label">Analytics</span>
      </a>
    </div>

    <!-- Comércio -->
    <div class="nav-group">
      <div class="nav-group-title">Comércio</div>

      <a href="<?= SITE_URL ?>/pages/orders.php"
         class="nav-item <?= $current === 'orders.php' ? 'active' : '' ?>"
         data-page="orders.php" data-tip="Pedidos">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
        </span>
        <span class="nav-label">Pedidos</span>
        <?php
          $total_pendentes = queryOne("SELECT COUNT(*) as c FROM pedidos WHERE status='pendente'")['c'] ?? 0;
          if ($total_pendentes > 0): ?>
          <span class="nav-badge"><?= $total_pendentes ?></span>
        <?php endif; ?>
      </a>

      <a href="<?= SITE_URL ?>/pages/products.php"
         class="nav-item <?= $current === 'products.php' ? 'active' : '' ?>"
         data-page="products.php" data-tip="Produtos">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.35C18 2.53 15.35 0 12.1 0c-1.7 0-3.23.73-4.34 1.88L7 2.62 6.24 1.88C5.13.73 3.6 0 1.9 0H1v6H.82C.36 6.55 0 7.22 0 8v12c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM9.72 3.3C10.42 2.52 11.2 2 12.1 2c1.67 0 3.9 1.35 3.9 2.65 0 .35-.08.67-.18 1H8.18C8.39 5.03 9 4.13 9.72 3.3zM3 2h-.1c.87 0 1.68.38 2.27 1.01L6.17 4H3V2zM2 20V8h20v12H2z"/></svg>
        </span>
        <span class="nav-label">Produtos</span>
      </a>

      <a href="<?= SITE_URL ?>/pages/customers.php"
         class="nav-item <?= $current === 'customers.php' ? 'active' : '' ?>"
         data-page="customers.php" data-tip="Clientes">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </span>
        <span class="nav-label">Clientes</span>
      </a>
    </div>

    <!-- Sistema -->
    <div class="nav-group">
      <div class="nav-group-title">Sistema</div>

      <a href="<?= SITE_URL ?>/pages/users.php"
         class="nav-item <?= $current === 'users.php' ? 'active' : '' ?>"
         data-page="users.php" data-tip="Usuários">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </span>
        <span class="nav-label">Usuários</span>
      </a>

      <a href="<?= SITE_URL ?>/pages/settings.php"
         class="nav-item <?= $current === 'settings.php' ? 'active' : '' ?>"
         data-page="settings.php" data-tip="Configurações">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
        </span>
        <span class="nav-label">Configurações</span>
      </a>
    </div>

  </nav>

  <!-- User Footer -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar">A</div>
      <div class="user-info">
        <div class="user-name">Administrador</div>
        <div class="user-role">Admin</div>
      </div>
    </div>
  </div>

</aside>
