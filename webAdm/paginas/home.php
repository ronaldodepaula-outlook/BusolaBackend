<?php
$isSuperAdmin = Auth::isSuperAdmin();
$nome = Auth::getNome();
$api = new ApiClient(Auth::getToken());

$dados = [];
if ($isSuperAdmin) {
    $resp = $api->get('dashboard/super-admin');
    $dados = $resp['data']['dados'] ?? [];
} else {
    $resp = $api->get('dashboard/empresa');
    $dados = $resp['data']['dados'] ?? [];
}

// Superadmin stats
$totalEmpresas         = $dados['total_empresas']        ?? 0;
$totalEmpresasAtivas   = $dados['total_empresas_ativas'] ?? 0;
$totalEmpresasInativas = $totalEmpresas - $totalEmpresasAtivas;
$totalUsuarios         = $dados['total_usuarios']        ?? 0;
$totalFiliais          = $dados['total_filiais']         ?? 0;

// Admin stats
$totalFiliaisAtivas = $dados['total_filiais_ativas'] ?? 0;
$totalRoles         = $dados['total_roles']          ?? 0;

$ultimosAcessos     = $dados['ultimos_acessos'] ?? [];
$ultimosLogs        = $dados['ultimos_logs']    ?? ($dados['logs_recentes'] ?? []);
$empresasBloqueadas = $dados['empresas_bloqueadas'] ?? [];
?>

<!-- Page Title -->
<div class="pagetitle">
  <h1>Dashboard</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
      <li class="breadcrumb-item active">Dashboard</li>
    </ol>
  </nav>
</div><!-- End Page Title -->

<section class="section dashboard">

  <!-- Stats Row -->
  <div class="row">

    <?php if ($isSuperAdmin): ?>

      <!-- Empresas Total -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card sales-card">
          <div class="card-body">
            <h5 class="card-title">Empresas <span>| Total</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-building"></i>
              </div>
              <div class="ps-3">
                <h6><?php echo (int)$totalEmpresas; ?></h6>
                <span class="text-success small fw-bold"><?php echo (int)$totalEmpresasAtivas; ?> ativas</span>
                <span class="text-muted small"> | Total de empresas</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empresas Inativas -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card revenue-card">
          <div class="card-body">
            <h5 class="card-title">Empresas <span>| Inativas</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-building-x"></i>
              </div>
              <div class="ps-3">
                <h6><?php echo (int)$totalEmpresasInativas; ?></h6>
                <span class="text-muted small">Inativas ou bloqueadas</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Usuários -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card customers-card">
          <div class="card-body">
            <h5 class="card-title">Usuários <span>| Total</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-people"></i>
              </div>
              <div class="ps-3">
                <h6><?php echo (int)$totalUsuarios; ?></h6>
                <span class="text-muted small">Total de usuários cadastrados</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filiais -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card">
          <div class="card-body">
            <h5 class="card-title">Filiais <span>| Total</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-diagram-3"></i>
              </div>
              <div class="ps-3">
                <h6><?php echo (int)$totalFiliais; ?></h6>
                <span class="text-muted small">Total de filiais cadastradas</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    <?php else: ?>

      <!-- Usuários -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card customers-card">
          <div class="card-body">
            <h5 class="card-title">Usuários <span>| Total</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-people"></i>
              </div>
              <div class="ps-3">
                <h6><?php echo (int)$totalUsuarios; ?></h6>
                <span class="text-muted small">Usuários na empresa</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filiais -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card sales-card">
          <div class="card-body">
            <h5 class="card-title">Filiais <span>| Total</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-diagram-3"></i>
              </div>
              <div class="ps-3">
                <h6><?php echo (int)$totalFiliais; ?></h6>
                <span class="text-success small"><?php echo (int)$totalFiliaisAtivas; ?> ativas</span>
                <span class="text-muted small"> | Total</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Perfis -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card revenue-card">
          <div class="card-body">
            <h5 class="card-title">Perfis <span>| Acesso</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-shield-check"></i>
              </div>
              <div class="ps-3">
                <h6><?php echo (int)$totalRoles; ?></h6>
                <span class="text-muted small">Perfis de acesso configurados</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bem-vindo -->
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card">
          <div class="card-body">
            <h5 class="card-title">Bem-vindo!</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-person-circle"></i>
              </div>
              <div class="ps-3">
                <h6 class="fs-6"><?php echo htmlspecialchars($nome); ?></h6>
                <span class="text-muted small">Painel administrativo</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>

  </div><!-- End Stats Row -->

  <div class="row">

    <!-- Últimos Acessos -->
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Últimos Acessos</h5>
        </div>
        <div class="card-body pt-3">
          <?php if (empty($ultimosAcessos)): ?>
            <p class="text-muted small mb-0">Nenhum acesso registrado.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Último Login</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ultimosAcessos as $acesso): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($acesso['nome'] ?? '—'); ?></td>
                      <td>
                        <span class="badge bg-secondary">
                          <?php echo htmlspecialchars($acesso['tipo'] ?? '—'); ?>
                        </span>
                      </td>
                      <td class="text-muted small">
                        <?php echo htmlspecialchars($acesso['ultimo_login'] ?? '—'); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- End Últimos Acessos -->

    <!-- Atividade Recente -->
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Atividade Recente</h5>
        </div>
        <div class="card-body pt-3">
          <?php if (empty($ultimosLogs)): ?>
            <p class="text-muted small mb-0">Nenhuma atividade registrada.</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($ultimosLogs as $log): ?>
                <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2">
                  <span class="badge bg-light text-dark border mt-1 flex-shrink-0">
                    <?php echo htmlspecialchars($log['metodo'] ?? 'INFO'); ?>
                  </span>
                  <div>
                    <div class="small fw-semibold">
                      <?php echo htmlspecialchars($log['acao'] ?? ($log['descricao'] ?? '—')); ?>
                    </div>
                    <div class="text-muted" style="font-size:.78rem;">
                      <?php echo htmlspecialchars($log['modulo'] ?? ''); ?>
                      <?php if (!empty($log['criado_em'])): ?>
                        &mdash; <?php echo htmlspecialchars($log['criado_em']); ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- End Atividade Recente -->

  </div><!-- End Row -->

  <?php if ($isSuperAdmin && !empty($empresasBloqueadas)): ?>
  <!-- Empresas Bloqueadas Warning -->
  <div class="row">
    <div class="col-12">
      <div class="card border-warning">
        <div class="card-header bg-warning bg-opacity-10 d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-triangle-fill text-warning"></i>
          <h5 class="card-title mb-0 text-warning">
            Empresas Bloqueadas
            <span class="badge bg-warning text-dark ms-2"><?php echo count($empresasBloqueadas); ?></span>
          </h5>
        </div>
        <div class="card-body pt-3">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Nome</th>
                  <th>CNPJ</th>
                  <th>Plano</th>
                  <th>Ação</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($empresasBloqueadas as $emp): ?>
                  <tr>
                    <td><?php echo (int)($emp['id'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars($emp['nome'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($emp['cnpj'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($emp['plano'] ?? '—'); ?></td>
                    <td>
                      <a href="?paginas=empresas" class="btn btn-sm btn-outline-warning">
                        Gerenciar
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</section>
