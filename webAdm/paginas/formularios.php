<?php
if (!Auth::hasPermission('formulario.listar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$isSuperAdmin = Auth::isSuperAdmin();

$search    = trim($_GET['search'] ?? '');
$status    = $_GET['status'] ?? '';
$tipo      = $_GET['tipo'] ?? '';
$empresaId = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;
$page      = max(1, (int)($_GET['page'] ?? 1));

$empresas = [];
if ($isSuperAdmin) {
    $empApi   = new ApiClient(Auth::getToken());
    $respEmp  = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

$api = new ApiClient(Auth::getToken());

$filters = ['page' => $page];
if ($search !== '')      $filters['search']     = $search;
if ($status !== '')      $filters['status']     = $status;
if ($tipo !== '')        $filters['tipo']       = $tipo;
if ($empresaId)          $filters['empresa_id'] = $empresaId;

$resp        = $api->get('pesquisa-psicossocial/formularios', $filters);
$formularios = $resp['data']['dados']['data']        ?? [];
$total       = $resp['data']['dados']['total']       ?? 0;
$lastPage    = $resp['data']['dados']['last_page']   ?? 1;
$currPage    = $resp['data']['dados']['current_page'] ?? 1;

$empresasPorId = [];
foreach ($empresas as $e) { $empresasPorId[$e['id']] = $e['nome']; }

$podeCriar   = Auth::hasPermission('formulario.criar');
$podeEditar  = Auth::hasPermission('formulario.editar');
$podeExcluir = Auth::hasPermission('formulario.excluir');
$podeVersionar = Auth::hasPermission('formulario.versionar');
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Formulários</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Pesquisas Psicossociais</li>
        <li class="breadcrumb-item active">Formulários</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        Formulários de Pesquisa
        <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span>
      </h5>
      <?php if ($podeCriar): ?>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriar">
        <i class="bi bi-plus-circle me-1"></i> Novo Formulário
      </button>
      <?php endif; ?>
    </div>

    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="formularios">
        <div class="col-12 col-md-3">
          <label class="form-label form-label-sm">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Nome ou código…" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="rascunho"  <?php echo $status === 'rascunho'  ? 'selected' : ''; ?>>Rascunho</option>
            <option value="publicado" <?php echo $status === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
            <option value="arquivado" <?php echo $status === 'arquivado' ? 'selected' : ''; ?>>Arquivado</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm">Tipo</label>
          <select name="tipo" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="global"  <?php echo $tipo === 'global'  ? 'selected' : ''; ?>>Global</option>
            <option value="empresa" <?php echo $tipo === 'empresa' ? 'selected' : ''; ?>>Empresa</option>
          </select>
        </div>
        <?php if ($isSuperAdmin): ?>
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Empresa</label>
          <select name="empresa_id" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach ($empresas as $emp): ?>
              <option value="<?php echo (int)$emp['id']; ?>" <?php echo $empresaId === (int)$emp['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($emp['nome']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtrar</button>
          <a href="?paginas=formularios" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
        </div>
      </form>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Nome</th>
              <th>Código</th>
              <?php if ($isSuperAdmin): ?><th>Empresa</th><?php endif; ?>
              <th>Status</th>
              <th>Versão</th>
              <th>Categorias</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($formularios)): ?>
              <tr><td colspan="<?php echo $isSuperAdmin ? 7 : 6; ?>" class="text-center text-muted py-4">Nenhum formulário encontrado.</td></tr>
            <?php else: ?>
              <?php foreach ($formularios as $f): ?>
                <?php
                  $statusBadge = match ($f['status'] ?? '') {
                      'rascunho'  => 'bg-secondary',
                      'publicado' => 'bg-success',
                      'arquivado' => 'bg-dark',
                      default     => 'bg-light text-dark',
                  };
                  $ativo = (bool)($f['ativo'] ?? true);
                ?>
                <tr>
                  <td>
                    <a href="?paginas=formulario-estrutura&id=<?php echo (int)$f['id']; ?>" class="fw-semibold text-decoration-none">
                      <?php echo htmlspecialchars($f['nome']); ?>
                    </a>
                    <?php if (!$ativo): ?><span class="badge bg-light text-muted border ms-1">versão histórica</span><?php endif; ?>
                    <?php if (($f['tipo'] ?? '') === 'global'): ?><span class="badge bg-info text-dark ms-1">Global</span><?php endif; ?>
                  </td>
                  <td><code><?php echo htmlspecialchars($f['codigo']); ?></code></td>
                  <?php if ($isSuperAdmin): ?>
                    <td><?php echo $f['empresa_id'] ? htmlspecialchars($empresasPorId[$f['empresa_id']] ?? ('#'.$f['empresa_id'])) : '<span class="text-muted">—</span>'; ?></td>
                  <?php endif; ?>
                  <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($f['status']); ?></span></td>
                  <td>v<?php echo (int)$f['versao']; ?></td>
                  <td><?php echo (int)($f['total_categorias'] ?? 0); ?></td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <a class="btn btn-sm btn-outline-primary" title="Estrutura" href="?paginas=formulario-estrutura&id=<?php echo (int)$f['id']; ?>">
                        <i class="bi bi-diagram-3"></i>
                      </a>
                      <?php if ($podeEditar): ?>
                      <button class="btn btn-sm btn-outline-secondary" title="Editar dados"
                              onclick='editarFormulario(<?php echo json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                        <i class="bi bi-pencil"></i>
                      </button>
                      <?php if (($f['status'] ?? '') === 'rascunho'): ?>
                        <button class="btn btn-sm btn-outline-success" title="Publicar" onclick="publicarFormulario(<?php echo (int)$f['id']; ?>)">
                          <i class="bi bi-send-check"></i>
                        </button>
                      <?php elseif (($f['status'] ?? '') === 'publicado'): ?>
                        <button class="btn btn-sm btn-outline-dark" title="Arquivar" onclick="arquivarFormulario(<?php echo (int)$f['id']; ?>)">
                          <i class="bi bi-archive"></i>
                        </button>
                      <?php endif; ?>
                      <?php endif; ?>
                      <?php if ($podeVersionar): ?>
                      <button class="btn btn-sm btn-outline-warning" title="Nova versão manual" onclick="novaVersaoFormulario(<?php echo (int)$f['id']; ?>)">
                        <i class="bi bi-clock-history"></i>
                      </button>
                      <?php endif; ?>
                      <button class="btn btn-sm btn-outline-info" title="Ver versões" onclick="verVersoes(<?php echo (int)$f['id']; ?>)">
                        <i class="bi bi-layers"></i>
                      </button>
                      <?php if ($podeExcluir): ?>
                      <button class="btn btn-sm btn-outline-danger" title="Excluir"
                              onclick="deletarFormulario(<?php echo (int)$f['id']; ?>, '<?php echo addslashes($f['nome']); ?>')">
                        <i class="bi bi-trash"></i>
                      </button>
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
        <?php $qBase = http_build_query(array_filter(['paginas' => 'formularios', 'search' => $search, 'status' => $status, 'tipo' => $tipo, 'empresa_id' => $empresaId ?: null])); ?>
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-end">
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
              <li class="page-item <?php echo $p === $currPage ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo $qBase; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Modal Criar -->
<div class="modal fade" id="modalCriar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>Novo Formulário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCriar">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Código <span class="text-danger">*</span></label>
              <input type="text" name="codigo" class="form-control" placeholder="ex: nr1-padrao" pattern="[a-z0-9]+(-[a-z0-9]+)*" required>
              <div class="form-text">letras minúsculas, números e hífens</div>
            </div>
            <div class="col-12">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo <span class="text-danger">*</span></label>
              <select name="tipo" id="criarTipo" class="form-select" required onchange="toggleEmpresaCriar()">
                <option value="empresa">Empresa (só a minha empresa usa)</option>
                <?php if ($isSuperAdmin): ?><option value="global">Global (todas as empresas podem usar)</option><?php endif; ?>
              </select>
            </div>
            <?php if ($isSuperAdmin): ?>
            <div class="col-md-6" id="criarEmpresaWrapper">
              <label class="form-label">Empresa <span class="text-danger">*</span></label>
              <select name="empresa_id" id="criarEmpresaId" class="form-select">
                <option value="">Selecione...</option>
                <?php foreach ($empresas as $emp): ?>
                  <option value="<?php echo (int)$emp['id']; ?>"><?php echo htmlspecialchars($emp['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarCriarFormulario(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Formulário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar">
          <input type="hidden" name="id" id="editId">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" id="editNome" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" id="editDescricao" class="form-control" rows="2"></textarea>
          </div>
          <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Código, tipo e empresa não podem ser alterados após a criação.</p>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarEditarFormulario(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Versões -->
<div class="modal fade" id="modalVersoes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-layers me-2"></i>Histórico de Versões</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="listaVersoes">
        <div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span> Carregando...</div>
      </div>
    </div>
  </div>
</div>

<script>
function toggleEmpresaCriar() {
  const tipo = document.getElementById('criarTipo')?.value;
  const wrapper = document.getElementById('criarEmpresaWrapper');
  if (!wrapper) return;
  wrapper.style.display = tipo === 'global' ? 'none' : '';
}
document.addEventListener('DOMContentLoaded', toggleEmpresaCriar);

function editarFormulario(f) {
  document.getElementById('editId').value = f.id ?? '';
  document.getElementById('editNome').value = f.nome ?? '';
  document.getElementById('editDescricao').value = f.descricao ?? '';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function salvarCriarFormulario(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formCriar');
  const res = await apiFetch('POST', 'pesquisa-psicossocial/formularios', data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Formulário criado com sucesso!', 'success');
    bslCloseModal('modalCriar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao criar formulário.', 'danger');
  }
}

async function salvarEditarFormulario(btn) {
  bslSetLoading(btn, true);
  const id = document.getElementById('editId').value;
  const data = bslFormData('formEditar');
  delete data.id;
  const res = await apiFetch('PUT', 'pesquisa-psicossocial/formularios/' + id, data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    if (res.dados?.versionado) {
      bslToast('Formulário atualizado. Uma nova versão (v' + (res.dados.formulario?.versao ?? '?') + ') foi criada automaticamente.', 'warning');
    } else {
      bslToast('Formulário atualizado com sucesso!', 'success');
    }
    bslCloseModal('modalEditar');
    setTimeout(() => location.reload(), 1000);
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar formulário.', 'danger');
  }
}

async function publicarFormulario(id) {
  if (!confirm('Publicar este formulário? Ele ficará disponível para uso em campanhas.')) return;
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/formularios/' + id + '/publicar');
  if (res.sucesso) { bslToast('Formulário publicado.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao publicar.', 'danger'); }
}

async function arquivarFormulario(id) {
  if (!confirm('Arquivar este formulário? Ele deixará de poder ser usado em novas campanhas.')) return;
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/formularios/' + id + '/arquivar');
  if (res.sucesso) { bslToast('Formulário arquivado.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao arquivar.', 'danger'); }
}

async function novaVersaoFormulario(id) {
  if (!confirm('Criar uma nova versão manualmente? A versão atual será arquivada e uma cópia editável será criada.')) return;
  const res = await apiFetch('POST', 'pesquisa-psicossocial/formularios/' + id + '/nova-versao');
  if (res.sucesso) {
    bslToast('Nova versão criada (v' + res.dados.versao + ').', 'success');
    setTimeout(() => { window.location.href = '?paginas=formulario-estrutura&id=' + res.dados.id; }, 900);
  } else { bslToast(res.mensagem || 'Erro ao criar nova versão.', 'danger'); }
}

async function verVersoes(id) {
  new bootstrap.Modal(document.getElementById('modalVersoes')).show();
  const container = document.getElementById('listaVersoes');
  container.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span> Carregando...</div>';
  const res = await apiFetch('GET', 'pesquisa-psicossocial/formularios/' + id + '/versoes');
  if (!res.sucesso || !res.dados?.length) {
    container.innerHTML = '<p class="text-muted mb-0">Nenhuma versão encontrada.</p>';
    return;
  }
  container.innerHTML = '<div class="list-group">' + res.dados.map(v => `
    <a href="?paginas=formulario-estrutura&id=${v.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
      <span>Versão ${v.versao} ${v.ativo ? '<span class="badge bg-success ms-1">vigente</span>' : '<span class="badge bg-secondary ms-1">arquivada</span>'}</span>
      <small class="text-muted">${v.status}</small>
    </a>`).join('') + '</div>';
}

async function deletarFormulario(id, nome) {
  if (!confirm('Excluir o formulário "' + nome + '"? Esta ação não pode ser desfeita.')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/formularios/' + id);
  if (res.sucesso) { bslToast('Formulário excluído.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao excluir formulário.', 'danger'); }
}
</script>
