<?php
if (!Auth::hasPermission('padrao_formulario.listar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$isSuperAdmin = Auth::isSuperAdmin();
$empresaId    = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;

$empresas = [];
if ($isSuperAdmin) {
    $empApi   = new ApiClient(Auth::getToken());
    $respEmp  = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

$api = new ApiClient(Auth::getToken());
$filters = [];
if ($empresaId) $filters['empresa_id'] = $empresaId;
$padroes = $api->get('pesquisa-psicossocial/padroes-formulario', $filters)['data']['dados'] ?? [];

$empresasPorId = [];
foreach ($empresas as $e) { $empresasPorId[$e['id']] = $e['nome']; }

$podeCriar   = Auth::hasPermission('padrao_formulario.criar');
$podeEditar  = Auth::hasPermission('padrao_formulario.editar');
$podeExcluir = Auth::hasPermission('padrao_formulario.excluir');
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Padrões de Formulário</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Pesquisas Psicossociais</li>
        <li class="breadcrumb-item active">Padrões de Formulário</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="alert alert-light border small">
    <i class="bi bi-info-circle me-1"></i>
    Um <strong>Padrão de Formulário</strong> identifica a norma/metodologia que um formulário segue
    (ex.: <strong>COPSOQ II</strong>, <strong>NR-1</strong>, <strong>ISO 45003</strong>), ou um padrão próprio de uma empresa.
    Padrões <strong>globais</strong> ficam disponíveis para seleção em todas as empresas; padrões de uma empresa só aparecem
    para ela.
  </div>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        Padrões cadastrados
        <span class="badge bg-secondary ms-2"><?php echo count($padroes); ?></span>
      </h5>
      <?php if ($podeCriar): ?>
      <button class="btn btn-primary btn-sm" onclick="abrirModalPadrao()"><i class="bi bi-plus-circle me-1"></i> Novo Padrão</button>
      <?php endif; ?>
    </div>

    <?php if ($isSuperAdmin): ?>
    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="padroes-formulario">
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Empresa</label>
          <select name="empresa_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todas (globais + de cada empresa)</option>
            <?php foreach ($empresas as $emp): ?>
              <option value="<?php echo (int)$emp['id']; ?>" <?php echo $empresaId === (int)$emp['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($emp['nome']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Escopo</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($padroes)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum padrão cadastrado.</td></tr>
          <?php else: foreach ($padroes as $p): ?>
            <tr>
              <td class="fw-semibold"><?php echo htmlspecialchars($p['nome']); ?></td>
              <td class="small text-muted"><?php echo htmlspecialchars($p['descricao'] ?? '—'); ?></td>
              <td class="small">
                <?php if ($p['empresa_id'] === null): ?>
                  <span class="badge bg-info text-dark">Global</span>
                <?php else: ?>
                  <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($empresasPorId[$p['empresa_id']] ?? ('Empresa #' . $p['empresa_id'])); ?></span>
                <?php endif; ?>
              </td>
              <td><span class="badge <?php echo $p['ativo'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $p['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <?php if ($podeEditar): ?>
                    <button class="btn btn-sm btn-outline-primary" title="Editar" onclick='abrirModalPadrao(<?php echo json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
                  <?php endif; ?>
                  <?php if ($podeExcluir): ?>
                    <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="excluirPadrao(<?php echo (int)$p['id']; ?>)"><i class="bi bi-trash"></i></button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- Modal Padrão -->
<div class="modal fade" id="modalPadrao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloModalPadrao">Novo Padrão de Formulário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="padrao-id">
        <div class="mb-3">
          <label class="form-label">Nome <span class="text-danger">*</span></label>
          <input type="text" id="padrao-nome" class="form-control" placeholder="Ex.: COPSOQ II, NR-1, Personalizado XPTO">
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <textarea id="padrao-descricao" class="form-control" rows="2"></textarea>
        </div>
        <div class="row g-3" id="padrao-escopo-wrapper">
          <div class="col-md-6">
            <label class="form-label">Tipo <span class="text-danger">*</span></label>
            <select id="padrao-tipo" class="form-select" onchange="togglePadraoEmpresa()">
              <option value="empresa">Empresa (só a minha empresa usa)</option>
              <?php if ($isSuperAdmin): ?><option value="global">Global (todas as empresas podem usar)</option><?php endif; ?>
            </select>
          </div>
          <?php if ($isSuperAdmin): ?>
          <div class="col-md-6" id="padrao-empresa-wrapper">
            <label class="form-label">Empresa <span class="text-danger">*</span></label>
            <select id="padrao-empresa-id" class="form-select">
              <option value="">Selecione...</option>
              <?php foreach ($empresas as $emp): ?>
                <option value="<?php echo (int)$emp['id']; ?>"><?php echo htmlspecialchars($emp['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </div>
        <div class="form-check mt-3" id="padrao-ativo-wrapper" style="display:none">
          <input class="form-check-input" type="checkbox" id="padrao-ativo" checked>
          <label class="form-check-label" for="padrao-ativo">Ativo</label>
        </div>
        <p class="text-muted small mb-0 mt-2" id="padrao-imutavel-aviso" style="display:none">
          <i class="bi bi-info-circle me-1"></i>Tipo e empresa não podem ser alterados após a criação.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarPadrao(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<script>
function togglePadraoEmpresa() {
  const tipo = document.getElementById('padrao-tipo')?.value;
  const wrapper = document.getElementById('padrao-empresa-wrapper');
  if (!wrapper) return;
  wrapper.style.display = tipo === 'global' ? 'none' : '';
}

function abrirModalPadrao(p) {
  document.getElementById('padrao-id').value = p ? p.id : '';
  document.getElementById('padrao-nome').value = p ? p.nome : '';
  document.getElementById('padrao-descricao').value = p ? (p.descricao || '') : '';
  document.getElementById('tituloModalPadrao').textContent = p ? 'Editar Padrão de Formulário' : 'Novo Padrão de Formulário';

  const escopoWrapper = document.getElementById('padrao-escopo-wrapper');
  const ativoWrapper = document.getElementById('padrao-ativo-wrapper');
  const aviso = document.getElementById('padrao-imutavel-aviso');

  if (p) {
    // Edição: tipo/empresa são imutáveis — não expõe os campos de escopo, só ativo/inativo.
    escopoWrapper.style.display = 'none';
    ativoWrapper.style.display = '';
    aviso.style.display = '';
    document.getElementById('padrao-ativo').checked = !!p.ativo;
  } else {
    escopoWrapper.style.display = '';
    ativoWrapper.style.display = 'none';
    aviso.style.display = 'none';
    document.getElementById('padrao-tipo').value = 'empresa';
    const empresaSel = document.getElementById('padrao-empresa-id');
    if (empresaSel) empresaSel.value = '';
    togglePadraoEmpresa();
  }

  new bootstrap.Modal(document.getElementById('modalPadrao')).show();
}

async function salvarPadrao(btn) {
  const id = document.getElementById('padrao-id').value;
  const nome = document.getElementById('padrao-nome').value.trim();
  if (!nome) { bslToast('Informe o nome do padrão.', 'warning'); return; }

  bslSetLoading(btn, true);
  let body;
  let res;
  if (id) {
    body = { nome, descricao: document.getElementById('padrao-descricao').value, ativo: document.getElementById('padrao-ativo').checked };
    res = await apiFetch('PUT', 'pesquisa-psicossocial/padroes-formulario/' + id, body);
  } else {
    const empresaSel = document.getElementById('padrao-empresa-id');
    body = {
      nome,
      descricao: document.getElementById('padrao-descricao').value,
      tipo: document.getElementById('padrao-tipo').value,
      empresa_id: empresaSel ? (empresaSel.value || null) : null,
    };
    res = await apiFetch('POST', 'pesquisa-psicossocial/padroes-formulario', body);
  }
  bslSetLoading(btn, false);

  if (res.sucesso) {
    bslToast('Padrão salvo com sucesso!', 'success');
    bslCloseModal('modalPadrao');
    setTimeout(() => location.reload(), 700);
  } else {
    bslToast(res.mensagem || 'Erro ao salvar padrão.', 'danger');
  }
}

async function excluirPadrao(id) {
  if (!confirm('Excluir este padrão de formulário? Formulários que o usam ficarão sem padrão associado.')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/padroes-formulario/' + id);
  if (res.sucesso) { bslToast('Padrão removido.', 'success'); setTimeout(() => location.reload(), 600); }
  else { bslToast(res.mensagem || 'Erro ao remover padrão.', 'danger'); }
}
</script>
