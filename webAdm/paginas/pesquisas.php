<?php
if (!Auth::hasPermission('pesquisa.listar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$isSuperAdmin = Auth::isSuperAdmin();
$status       = $_GET['status'] ?? '';
$empresaId    = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;
$page         = max(1, (int)($_GET['page'] ?? 1));

$empresas = [];
if ($isSuperAdmin) {
    $empApi  = new ApiClient(Auth::getToken());
    $respEmp = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

$api = new ApiClient(Auth::getToken());
$filters = ['page' => $page];
if ($status !== '') $filters['status'] = $status;
if ($empresaId)      $filters['empresa_id'] = $empresaId;

$resp      = $api->get('pesquisa-psicossocial/pesquisas', $filters);
$pesquisas = $resp['data']['dados']['data']        ?? [];
$total     = $resp['data']['dados']['total']       ?? 0;
$lastPage  = $resp['data']['dados']['last_page']   ?? 1;
$currPage  = $resp['data']['dados']['current_page'] ?? 1;

$podeCriar     = Auth::hasPermission('pesquisa.criar');
$podeEditar    = Auth::hasPermission('pesquisa.editar');
$podeExcluir   = Auth::hasPermission('pesquisa.excluir');
$podePublicar  = Auth::hasPermission('pesquisa.publicar');
$podeEncerrar  = Auth::hasPermission('pesquisa.encerrar');

$statusLabels = [
    'rascunho'  => ['Rascunho', 'bg-secondary'],
    'ativa'     => ['Ativa', 'bg-success'],
    'encerrada' => ['Encerrada', 'bg-dark'],
    'cancelada' => ['Cancelada', 'bg-danger'],
];
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Campanhas de Pesquisa</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Pesquisas Psicossociais</li>
        <li class="breadcrumb-item active">Campanhas</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        Campanhas
        <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span>
      </h5>
      <?php if ($podeCriar): ?>
        <a href="?paginas=pesquisa-wizard" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> Nova Pesquisa</a>
      <?php endif; ?>
    </div>

    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="pesquisas">
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Todos</option>
            <?php foreach ($statusLabels as $val => [$lbl, $badge]): ?>
              <option value="<?php echo $val; ?>" <?php echo $status === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($isSuperAdmin): ?>
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Empresa</label>
          <select name="empresa_id" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach ($empresas as $emp): ?>
              <option value="<?php echo (int)$emp['id']; ?>" <?php echo $empresaId === (int)$emp['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['nome']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
          <a href="?paginas=pesquisas" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
        </div>
      </form>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Campanha</th>
              <th>Formulário</th>
              <th>Status</th>
              <th>Período</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pesquisas)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma campanha encontrada.</td></tr>
            <?php else: ?>
              <?php foreach ($pesquisas as $p): ?>
                <?php [$stLabel, $stBadge] = $statusLabels[$p['status']] ?? [$p['status'], 'bg-light text-dark']; ?>
                <tr>
                  <td>
                    <?php echo htmlspecialchars($p['nome'] ?? ('Campanha #' . $p['id'])); ?>
                    <?php if ($p['anonima']): ?><span class="badge bg-light text-dark border ms-1" title="Respostas anônimas"><i class="bi bi-incognito"></i></span><?php endif; ?>
                  </td>
                  <td class="small"><?php echo htmlspecialchars($p['formulario']['nome'] ?? '—'); ?></td>
                  <td><span class="badge <?php echo $stBadge; ?>"><?php echo $stLabel; ?></span></td>
                  <td class="small text-muted">
                    <?php echo $p['data_inicio'] ? htmlspecialchars($p['data_inicio']) : '—'; ?> — <?php echo $p['data_fim'] ? htmlspecialchars($p['data_fim']) : '—'; ?>
                  </td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <?php if ($p['status'] === 'rascunho' && $podeEditar): ?>
                        <a class="btn btn-sm btn-outline-primary" href="?paginas=pesquisa-wizard&id=<?php echo (int)$p['id']; ?>&step=2">
                          <i class="bi bi-arrow-right-circle me-1"></i>Continuar
                        </a>
                      <?php endif; ?>
                      <?php if ($p['status'] === 'rascunho' && $podePublicar): ?>
                        <button class="btn btn-sm btn-outline-success" title="Publicar" onclick="publicarPesquisa(<?php echo (int)$p['id']; ?>)"><i class="bi bi-send-check"></i></button>
                      <?php endif; ?>
                      <?php if ($p['status'] === 'ativa' && $podeEncerrar): ?>
                        <button class="btn btn-sm btn-outline-dark" title="Encerrar" onclick="encerrarPesquisa(<?php echo (int)$p['id']; ?>)"><i class="bi bi-flag"></i></button>
                      <?php endif; ?>
                      <button class="btn btn-sm btn-outline-info" title="Ver público-alvo" onclick="verPublico(<?php echo (int)$p['id']; ?>)"><i class="bi bi-people"></i></button>
                      <?php if ($p['status'] !== 'rascunho'): ?>
                        <a class="btn btn-sm btn-outline-secondary" title="Convites (links individuais)" href="?paginas=pesquisa-convites&id=<?php echo (int)$p['id']; ?>">
                          <i class="bi bi-link-45deg"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($p['status'] !== 'rascunho' && Auth::hasPermission('resultado.consultar')): ?>
                        <a class="btn btn-sm btn-outline-primary" title="Resultados" href="?paginas=pesquisa-resultados&id=<?php echo (int)$p['id']; ?>">
                          <i class="bi bi-bar-chart-line"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($p['status'] === 'rascunho' && $podeExcluir): ?>
                        <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="deletarPesquisa(<?php echo (int)$p['id']; ?>)"><i class="bi bi-trash"></i></button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($lastPage > 1): ?>
        <?php $qBase = http_build_query(array_filter(['paginas' => 'pesquisas', 'status' => $status, 'empresa_id' => $empresaId ?: null])); ?>
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-end">
            <?php for ($p2 = 1; $p2 <= $lastPage; $p2++): ?>
              <li class="page-item <?php echo $p2 === $currPage ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo $qBase; ?>&page=<?php echo $p2; ?>"><?php echo $p2; ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Modal Público-alvo -->
<div class="modal fade" id="modalPublico" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-people me-2"></i>Público-alvo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="publicoConteudo">
        <div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span> Carregando...</div>
      </div>
    </div>
  </div>
</div>

<script>
async function publicarPesquisa(id) {
  if (!confirm('Publicar esta campanha? Ela ficará ativa e disponível para os respondentes.')) return;
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/pesquisas/' + id + '/publicar');
  if (res.sucesso) { bslToast('Campanha publicada.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao publicar campanha.', 'danger'); }
}

async function encerrarPesquisa(id) {
  if (!confirm('Encerrar esta campanha? Não será mais possível receber respostas, e o formulário vinculado passará a ser protegido (versionado automaticamente em futuras edições).')) return;
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/pesquisas/' + id + '/encerrar');
  if (res.sucesso) { bslToast('Campanha encerrada.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao encerrar campanha.', 'danger'); }
}

async function deletarPesquisa(id) {
  if (!confirm('Excluir esta campanha em rascunho?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/pesquisas/' + id);
  if (res.sucesso) { bslToast('Campanha excluída.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao excluir campanha.', 'danger'); }
}

async function verPublico(id) {
  new bootstrap.Modal(document.getElementById('modalPublico')).show();
  const container = document.getElementById('publicoConteudo');
  container.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span> Carregando...</div>';
  const res = await apiFetch('GET', 'pesquisa-psicossocial/pesquisas/' + id);
  if (!res.sucesso) { container.innerHTML = '<p class="text-danger">Erro ao carregar.</p>'; return; }
  const pub = res.dados.publico || { tipo: 'toda_empresa', filial_ids: [], colaborador_ids: [] };
  let html = '';
  if (pub.tipo === 'toda_empresa') {
    html = '<p class="mb-0"><i class="bi bi-building me-1"></i> Toda a empresa é o público-alvo desta campanha.</p>';
  } else if (pub.tipo === 'filiais') {
    html = '<p><i class="bi bi-diagram-3 me-1"></i> Filiais selecionadas (' + pub.filial_ids.length + '):</p><p class="text-muted small">IDs: ' + pub.filial_ids.join(', ') + '</p>';
  } else {
    html = '<p><i class="bi bi-people me-1"></i> Colaboradores selecionados (' + pub.colaborador_ids.length + '):</p><p class="text-muted small">IDs: ' + pub.colaborador_ids.join(', ') + '</p>';
  }
  container.innerHTML = html;
}
</script>
