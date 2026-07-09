<?php
if (!Auth::hasPermission('conceito.listar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$isSuperAdmin = Auth::isSuperAdmin();

$search    = trim($_GET['search'] ?? '');
$tipo      = $_GET['tipo'] ?? '';
$empresaId = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;
$page      = max(1, (int)($_GET['page'] ?? 1));

$empresas = [];
if ($isSuperAdmin) {
    $empApi  = new ApiClient(Auth::getToken());
    $respEmp = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

$api = new ApiClient(Auth::getToken());
$filters = ['page' => $page];
if ($search !== '') $filters['search'] = $search;
if ($tipo !== '')   $filters['tipo']   = $tipo;
if ($empresaId)     $filters['empresa_id'] = $empresaId;

$resp      = $api->get('pesquisa-psicossocial/conceitos', $filters);
$conceitos = $resp['data']['dados']['data']        ?? [];
$total     = $resp['data']['dados']['total']       ?? 0;
$lastPage  = $resp['data']['dados']['last_page']   ?? 1;
$currPage  = $resp['data']['dados']['current_page'] ?? 1;

$empresasPorId = [];
foreach ($empresas as $e) { $empresasPorId[$e['id']] = $e['nome']; }

$tipoLabels = [
    'escala_likert'  => 'Escala Likert',
    'frequencia'     => 'Frequência',
    'numerica'       => 'Numérica',
    'personalizado'  => 'Personalizado',
];

$podeCriar   = Auth::hasPermission('conceito.criar');
$podeEditar  = Auth::hasPermission('conceito.editar');
$podeExcluir = Auth::hasPermission('conceito.excluir');
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Conceitos de Avaliação</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Pesquisas Psicossociais</li>
        <li class="breadcrumb-item active">Conceitos</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        Escalas de Avaliação
        <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span>
      </h5>
      <?php if ($podeCriar): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriar">
          <i class="bi bi-plus-circle me-1"></i> Novo Conceito
        </button>
      <?php endif; ?>
    </div>

    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="conceitos">
        <div class="col-12 col-md-4">
          <label class="form-label form-label-sm">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome...">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Tipo</label>
          <select name="tipo" class="form-select form-select-sm">
            <option value="">Todos</option>
            <?php foreach ($tipoLabels as $val => $lbl): ?>
              <option value="<?php echo $val; ?>" <?php echo $tipo === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
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
          <a href="?paginas=conceitos" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
        </div>
      </form>
    </div>

    <div class="card-body">
      <div class="row g-3">
        <?php if (empty($conceitos)): ?>
          <div class="col-12 text-center text-muted py-4">Nenhum conceito cadastrado.</div>
        <?php endif; ?>
        <?php foreach ($conceitos as $c): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <h6 class="card-title mb-1"><?php echo htmlspecialchars($c['nome']); ?></h6>
                  <?php if (!$c['empresa_id']): ?><span class="badge bg-info text-dark">Global</span><?php endif; ?>
                </div>
                <p class="text-muted small mb-2"><?php echo $tipoLabels[$c['tipo']] ?? htmlspecialchars($c['tipo']); ?></p>
                <?php if ($c['descricao']): ?><p class="small mb-2"><?php echo htmlspecialchars($c['descricao']); ?></p><?php endif; ?>
                <span class="badge bg-light text-dark border"><?php echo (int)($c['total_itens'] ?? 0); ?> item(ns)</span>
              </div>
              <div class="card-footer bg-transparent d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="gerenciarItens(<?php echo (int)$c['id']; ?>, '<?php echo addslashes($c['nome']); ?>')">
                  <i class="bi bi-sliders me-1"></i>Itens
                </button>
                <?php if ($podeEditar): ?>
                  <button class="btn btn-sm btn-outline-secondary" title="Editar"
                          onclick='editarConceito(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
                <?php endif; ?>
                <?php if ($podeExcluir): ?>
                  <button class="btn btn-sm btn-outline-danger" title="Excluir"
                          onclick="deletarConceito(<?php echo (int)$c['id']; ?>, '<?php echo addslashes($c['nome']); ?>')"><i class="bi bi-trash"></i></button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($lastPage > 1): ?>
        <?php $qBase = http_build_query(array_filter(['paginas' => 'conceitos', 'search' => $search, 'tipo' => $tipo, 'empresa_id' => $empresaId ?: null])); ?>
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
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-rulers me-2"></i>Novo Conceito de Avaliação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCriar">
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="nome" class="form-control" required placeholder="ex: Escala de Satisfação">
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo <span class="text-danger">*</span></label>
            <select name="tipo" class="form-select" required>
              <?php foreach ($tipoLabels as $val => $lbl): ?>
                <option value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($isSuperAdmin): ?>
          <div class="mb-3">
            <label class="form-label">Empresa <span class="text-muted">(vazio = conceito global)</span></label>
            <select name="empresa_id" class="form-select">
              <option value="">Global (todas as empresas)</option>
              <?php foreach ($empresas as $emp): ?>
                <option value="<?php echo (int)$emp['id']; ?>"><?php echo htmlspecialchars($emp['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarCriarConceito(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Conceito</h5>
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
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarEditarConceito(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Itens -->
<div class="modal fade" id="modalItens" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Itens de <span id="itensConceitoNome"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="itensPreview" class="d-flex flex-wrap gap-1 mb-3"></div>
        <div id="itensLista" class="list-group mb-3"></div>
        <div class="border rounded p-2 bg-light-subtle">
          <div class="row g-2 align-items-end">
            <div class="col-5">
              <label class="form-label form-label-sm mb-0">Descrição</label>
              <input type="text" id="novoItemDescricao" class="form-control form-control-sm" placeholder="ex: Muito satisfeito">
            </div>
            <div class="col-3">
              <label class="form-label form-label-sm mb-0">Valor</label>
              <input type="number" id="novoItemValor" step="0.01" class="form-control form-control-sm" placeholder="5">
            </div>
            <div class="col-2">
              <label class="form-label form-label-sm mb-0">Cor</label>
              <input type="color" id="novoItemCor" class="form-control form-control-sm form-control-color" value="#0d6efd">
            </div>
            <div class="col-2">
              <button class="btn btn-success btn-sm w-100" onclick="adicionarItemConceito()"><i class="bi bi-plus-lg"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
let conceitoAtualId = null;

function editarConceito(c) {
  document.getElementById('editId').value = c.id;
  document.getElementById('editNome').value = c.nome ?? '';
  document.getElementById('editDescricao').value = c.descricao ?? '';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function salvarCriarConceito(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formCriar');
  const res = await apiFetch('POST', 'pesquisa-psicossocial/conceitos', data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Conceito criado com sucesso!', 'success');
    bslCloseModal('modalCriar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao criar conceito.', 'danger');
  }
}

async function salvarEditarConceito(btn) {
  bslSetLoading(btn, true);
  const id = document.getElementById('editId').value;
  const data = bslFormData('formEditar');
  delete data.id;
  const res = await apiFetch('PUT', 'pesquisa-psicossocial/conceitos/' + id, data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Conceito atualizado com sucesso!', 'success');
    bslCloseModal('modalEditar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar conceito.', 'danger');
  }
}

async function deletarConceito(id, nome) {
  if (!confirm('Excluir o conceito "' + nome + '"?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/conceitos/' + id);
  if (res.sucesso) { bslToast('Conceito excluído.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao excluir conceito.', 'danger'); }
}

async function gerenciarItens(id, nome) {
  conceitoAtualId = id;
  document.getElementById('itensConceitoNome').textContent = nome;
  new bootstrap.Modal(document.getElementById('modalItens')).show();
  await recarregarItens();
}

async function recarregarItens() {
  const preview = document.getElementById('itensPreview');
  const lista = document.getElementById('itensLista');
  preview.innerHTML = '<span class="text-muted small">Carregando...</span>';
  lista.innerHTML = '';

  const res = await apiFetch('GET', 'pesquisa-psicossocial/conceitos/' + conceitoAtualId);
  if (!res.sucesso) { preview.innerHTML = '<span class="text-danger small">Erro ao carregar itens.</span>'; return; }

  const itens = (res.dados.itens || []).slice().sort((a, b) => a.ordem - b.ordem);

  preview.innerHTML = itens.length
    ? itens.map(i => `<span class="badge" style="background-color:${i.cor || '#6c757d'}">${i.descricao} (${i.valor})</span>`).join('')
    : '<span class="text-muted small">Nenhum item ainda — adicione abaixo.</span>';

  lista.innerHTML = itens.map(i => `
    <div class="list-group-item d-flex align-items-center gap-2" data-id="${i.id}">
      <i class="bi bi-grip-vertical drag-handle-item text-muted" style="cursor:grab"></i>
      <input type="text" class="form-control form-control-sm" style="max-width:200px" value="${i.descricao.replace(/"/g, '&quot;')}" data-field="descricao">
      <input type="number" step="0.01" class="form-control form-control-sm" style="max-width:90px" value="${i.valor}" data-field="valor">
      <input type="color" class="form-control form-control-sm form-control-color" value="${i.cor || '#6c757d'}" data-field="cor">
      <button class="btn btn-sm btn-outline-success" onclick="salvarItemConceito(${i.id}, this)" title="Salvar"><i class="bi bi-check-lg"></i></button>
      <button class="btn btn-sm btn-outline-danger" onclick="removerItemConceito(${i.id})" title="Remover"><i class="bi bi-trash"></i></button>
    </div>`).join('');

  const sortableEl = lista;
  if (sortableEl.dataset.sortableInit !== '1') {
    new Sortable(sortableEl, {
      handle: '.drag-handle-item',
      animation: 150,
      onEnd: async function () {
        const ids = Array.from(sortableEl.children).map(el => el.dataset.id);
        await apiFetch('PATCH', 'pesquisa-psicossocial/conceitos/' + conceitoAtualId + '/itens/reordenar', { ids });
      }
    });
    sortableEl.dataset.sortableInit = '1';
  }
}

async function adicionarItemConceito() {
  const descricao = document.getElementById('novoItemDescricao').value.trim();
  const valor = document.getElementById('novoItemValor').value;
  const cor = document.getElementById('novoItemCor').value;
  if (!descricao || valor === '') { bslToast('Preencha descrição e valor.', 'warning'); return; }
  const res = await apiFetch('POST', 'pesquisa-psicossocial/conceitos/' + conceitoAtualId + '/itens', { descricao, valor, cor });
  if (res.sucesso) {
    document.getElementById('novoItemDescricao').value = '';
    document.getElementById('novoItemValor').value = '';
    await recarregarItens();
  } else {
    bslToast(res.mensagem || 'Erro ao adicionar item.', 'danger');
  }
}

async function salvarItemConceito(itemId, btn) {
  const row = btn.closest('.list-group-item');
  const data = {
    descricao: row.querySelector('[data-field="descricao"]').value,
    valor: row.querySelector('[data-field="valor"]').value,
    cor: row.querySelector('[data-field="cor"]').value,
  };
  const res = await apiFetch('PUT', 'pesquisa-psicossocial/conceitos/' + conceitoAtualId + '/itens/' + itemId, data);
  if (res.sucesso) { bslToast('Item atualizado.', 'success'); await recarregarItens(); }
  else { bslToast(res.mensagem || 'Erro ao atualizar item.', 'danger'); }
}

async function removerItemConceito(itemId) {
  if (!confirm('Remover este item?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/conceitos/' + conceitoAtualId + '/itens/' + itemId);
  if (res.sucesso) { await recarregarItens(); }
  else { bslToast(res.mensagem || 'Erro ao remover item.', 'danger'); }
}
</script>
