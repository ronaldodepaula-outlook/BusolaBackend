<?php
if (!Auth::isSuperAdmin() && !Auth::hasPermission('relatorio.listar_todas')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Esta tela é exclusiva do super administrador.</div>';
    return;
}

$empresaId = (int)($_GET['empresa_id'] ?? 0);

$api = new ApiClient(Auth::getToken());

$empApi = $api->get('empresas', ['status' => 'ativo', 'per_page' => 200]);
$empresas = $empApi['data']['dados']['data'] ?? [];

$filters = $empresaId ? ['empresa_id' => $empresaId] : [];
$resp = $api->get('pesquisa-psicossocial/relatorios-tecnicos', $filters);
$relatorios = $resp['data']['dados']['data'] ?? [];
$total = $resp['data']['dados']['total'] ?? count($relatorios);
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Relatórios Técnicos — Todas as Empresas</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Super Administrador</li>
        <li class="breadcrumb-item active">Relatórios Técnicos</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="alert alert-light border small">
    <i class="bi bi-shield-check me-1"></i>
    Visão consolidada de todos os Relatórios Técnicos (PDF) gerados em todas as empresas do sistema, para fins de
    auditoria e acompanhamento de conformidade com a metodologia COPSOQ II / NR-1.
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h6 class="mb-0">Relatórios <span class="badge bg-secondary ms-1"><?php echo (int)$total; ?></span></h6>
      <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="paginas" value="relatorios-tecnicos-admin">
        <select name="empresa_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Todas as empresas</option>
          <?php foreach ($empresas as $emp): ?>
            <option value="<?php echo (int)$emp['id']; ?>" <?php echo $empresaId === (int)$emp['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['nome']); ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Empresa</th><th>Campanha</th><th>Gerado em</th><th>Responsável técnico</th><th>Gerado por</th><th></th></tr>
        </thead>
        <tbody>
          <?php if (empty($relatorios)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum relatório encontrado.</td></tr>
          <?php else: foreach ($relatorios as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['empresa']['nome'] ?? '—'); ?></td>
              <td class="small"><?php echo htmlspecialchars($r['pesquisa']['nome'] ?? ('Campanha #' . $r['pesquisa_id'])); ?></td>
              <td class="small"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['gerado_em']))); ?></td>
              <td class="small"><?php echo htmlspecialchars(trim(($r['responsavel_tecnico_nome'] ?? '—') . ' ' . ($r['responsavel_tecnico_registro'] ? '(' . $r['responsavel_tecnico_registro'] . ')' : ''))); ?></td>
              <td class="small text-muted"><?php echo htmlspecialchars($r['gerado_por']['nome'] ?? '—'); ?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="relatorio-download.php?id=<?php echo (int)$r['id']; ?>" target="_blank">
                  <i class="bi bi-download me-1"></i>Baixar
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
