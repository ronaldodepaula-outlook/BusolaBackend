<?php
$isSuperAdmin      = Auth::isSuperAdmin();
$selectedEmpresaId = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;

$acao       = trim($_GET['acao']        ?? '');
$modulo     = trim($_GET['modulo']      ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim    = trim($_GET['data_fim']    ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));

// Empresas list for superadmin selector
$empresas = [];
if ($isSuperAdmin) {
    $empApi  = new ApiClient(Auth::getToken());
    $respEmp = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

// API client scoped to selected empresa
$api = new ApiClient(Auth::getToken(), $selectedEmpresaId ?: null);

$logs     = [];
$total    = 0;
$currPage = 1;
$lastPage = 1;

if (!$isSuperAdmin || $selectedEmpresaId) {
    $filters = ['page' => $page];
    if ($acao       !== '') $filters['acao']        = $acao;
    if ($modulo     !== '') $filters['modulo']      = $modulo;
    if ($dataInicio !== '') $filters['data_inicio'] = $dataInicio;
    if ($dataFim    !== '') $filters['data_fim']    = $dataFim;

    $resp     = $api->get('logs', $filters);
    $logs     = $resp['data']['dados']['data']         ?? [];
    $total    = $resp['data']['dados']['total']        ?? 0;
    $currPage = $resp['data']['dados']['current_page'] ?? 1;
    $lastPage = $resp['data']['dados']['last_page']    ?? 1;
}

function metodoBadge(string $m): string {
    return match(strtoupper($m)) {
        'GET'    => 'bg-info text-dark',
        'POST'   => 'bg-success',
        'PUT'    => 'bg-primary',
        'PATCH'  => 'bg-warning text-dark',
        'DELETE' => 'bg-danger',
        default  => 'bg-secondary',
    };
}
?>

<!-- Page Title -->
<div class="pagetitle">
  <h1>Logs de Auditoria</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?paginas=home">Inicio</a></li>
      <li class="breadcrumb-item active">Logs de Auditoria</li>
    </ol>
  </nav>
</div>

<section class="section">

  <?php if ($isSuperAdmin): ?>
  <!-- Empresa selector for superadmin -->
  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="GET" class="d-flex align-items-center gap-3 flex-wrap mb-0">
        <input type="hidden" name="paginas" value="logs">
        <label class="form-label mb-0 fw-semibold">
          <i class="bi bi-building me-1 text-primary"></i> Empresa:
        </label>
        <select name="empresa_id" class="form-select form-select-sm" style="width:auto;min-width:220px" onchange="this.form.submit()">
          <option value="">-- Selecione uma empresa --</option>
          <?php foreach ($empresas as $emp): ?>
            <option value="<?php echo (int)$emp['id']; ?>"
              <?php echo $selectedEmpresaId === (int)$emp['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($emp['nome'] ?? ''); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($selectedEmpresaId): ?>
          <a href="?paginas=logs" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> Limpar
          </a>
        <?php endif; ?>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($isSuperAdmin && !$selectedEmpresaId): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      Selecione uma empresa acima para visualizar os logs de auditoria.
    </div>
  <?php else: ?>

  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">
        Registros de Auditoria
        <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span>
      </h5>
    </div>

    <!-- Filter Bar -->
    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="logs">
        <?php if ($isSuperAdmin && $selectedEmpresaId): ?>
          <input type="hidden" name="empresa_id" value="<?php echo (int)$selectedEmpresaId; ?>">
        <?php endif; ?>
        <div class="col-12 col-md-3">
          <label class="form-label form-label-sm">Acao</label>
          <input type="text" name="acao" class="form-control form-control-sm"
                 placeholder="criar, atualizar..." value="<?php echo htmlspecialchars($acao); ?>">
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label form-label-sm">Modulo</label>
          <input type="text" name="modulo" class="form-control form-control-sm"
                 placeholder="empresas, usuarios..." value="<?php echo htmlspecialchars($modulo); ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm">Data inicio</label>
          <input type="date" name="data_inicio" class="form-control form-control-sm"
                 value="<?php echo htmlspecialchars($dataInicio); ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm">Data fim</label>
          <input type="date" name="data_fim" class="form-control form-control-sm"
                 value="<?php echo htmlspecialchars($dataFim); ?>">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search"></i> Filtrar
          </button>
          <a href="?paginas=logs<?php echo $selectedEmpresaId ? '&empresa_id='.$selectedEmpresaId : ''; ?>"
             class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Acao</th>
              <th>Modulo</th>
              <th>Metodo</th>
              <th>IP</th>
              <th>Usuario</th>
              <th>Data/Hora</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td><?php echo (int)($log['id'] ?? 0); ?></td>
                  <td><?php echo htmlspecialchars($log['acao'] ?? '-'); ?></td>
                  <td><span class="text-muted small"><?php echo htmlspecialchars($log['modulo'] ?? '-'); ?></span></td>
                  <td>
                    <span class="badge <?php echo metodoBadge($log['metodo'] ?? ''); ?>">
                      <?php echo htmlspecialchars(strtoupper($log['metodo'] ?? '-')); ?>
                    </span>
                  </td>
                  <td><code class="small"><?php echo htmlspecialchars($log['ip'] ?? '-'); ?></code></td>
                  <td><?php echo htmlspecialchars($log['usuario'] ?? ($log['usuario_nome'] ?? '-')); ?></td>
                  <td class="text-muted small"><?php echo htmlspecialchars($log['criado_em'] ?? ($log['created_at'] ?? '-')); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($lastPage > 1): ?>
        <?php
          $qBase = http_build_query(array_filter([
              'paginas'     => 'logs',
              'empresa_id'  => $selectedEmpresaId ?: null,
              'acao'        => $acao,
              'modulo'      => $modulo,
              'data_inicio' => $dataInicio,
              'data_fim'    => $dataFim,
          ]));
        ?>
        <nav class="mt-3 d-flex align-items-center justify-content-between">
          <span class="text-muted small">Pagina <?php echo $currPage; ?> de <?php echo $lastPage; ?></span>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?php echo $currPage <= 1 ? 'disabled' : ''; ?>">
              <a class="page-link" href="?<?php echo $qBase; ?>&page=<?php echo max(1, $currPage - 1); ?>">
                <i class="bi bi-chevron-left"></i> Anterior
              </a>
            </li>
            <li class="page-item disabled">
              <span class="page-link"><?php echo $currPage; ?> / <?php echo $lastPage; ?></span>
            </li>
            <li class="page-item <?php echo $currPage >= $lastPage ? 'disabled' : ''; ?>">
              <a class="page-link" href="?<?php echo $qBase; ?>&page=<?php echo min($lastPage, $currPage + 1); ?>">
                Proximo <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>

  <?php endif; ?>

</section>
