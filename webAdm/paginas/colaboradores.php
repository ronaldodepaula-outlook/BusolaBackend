<?php
if (!Auth::hasPermission('colaborador.listar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$isSuperAdmin      = Auth::isSuperAdmin();
$selectedEmpresaId = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;

$search   = trim($_GET['search'] ?? '');
$setorId  = (int)($_GET['setor_id'] ?? 0);
$ativo    = $_GET['ativo'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));

$empresas = [];
if ($isSuperAdmin) {
    $empApi   = new ApiClient(Auth::getToken());
    $respEmp  = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

$api = new ApiClient(Auth::getToken(), $selectedEmpresaId ?: null);

$colaboradores = [];
$total = 0; $lastPage = 1; $currPage = 1;
$setores = []; $filiais = [];

$podeOperar = !$isSuperAdmin || $selectedEmpresaId;

if ($podeOperar) {
    $filters = ['page' => $page];
    if ($search !== '') $filters['search'] = $search;
    if ($setorId) $filters['setor_id'] = $setorId;
    if ($ativo !== '') $filters['ativo'] = $ativo === '1' ? 1 : 0;

    $resp = $api->get('pesquisa-psicossocial/colaboradores', $filters);
    $colaboradores = $resp['data']['dados']['data'] ?? [];
    $total = $resp['data']['dados']['total'] ?? 0;
    $lastPage = $resp['data']['dados']['last_page'] ?? 1;
    $currPage = $resp['data']['dados']['current_page'] ?? 1;

    $setores = $api->get('pesquisa-psicossocial/setores')['data']['dados'] ?? [];
    $filiais = $api->get('filiais', ['per_page' => 100])['data']['dados']['data'] ?? [];
}

$podeCriar = Auth::hasPermission('colaborador.criar');
$podeEditar = Auth::hasPermission('colaborador.editar');
$podeExcluir = Auth::hasPermission('colaborador.excluir');
$podeImportar = Auth::hasPermission('colaborador.importar');
$podeVerSensivel = Auth::hasPermission('colaborador.visualizar_dados_sensiveis');
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Colaboradores</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Pesquisas Psicossociais</li>
        <li class="breadcrumb-item active">Colaboradores</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="alert alert-light border small">
    <i class="bi bi-shield-lock me-1"></i>
    Colaboradores são as pessoas da empresa que recebem o link individual da pesquisa — não precisam de conta de
    acesso ao sistema. CPF e data de nascimento são dados sensíveis: ficam sempre cifrados no banco e só aparecem
    mascarados nesta tela; ver o valor completo exige permissão específica e fica registrado no log de auditoria.
  </div>

  <?php if ($isSuperAdmin): ?>
  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="GET" class="d-flex align-items-center gap-3 flex-wrap mb-0">
        <input type="hidden" name="paginas" value="colaboradores">
        <label class="form-label mb-0 fw-semibold"><i class="bi bi-building me-1 text-primary"></i> Empresa:</label>
        <select name="empresa_id" class="form-select form-select-sm" style="width:auto;min-width:220px" onchange="this.form.submit()">
          <option value="">-- Selecione uma empresa --</option>
          <?php foreach ($empresas as $emp): ?>
            <option value="<?php echo (int)$emp['id']; ?>" <?php echo $selectedEmpresaId === (int)$emp['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['nome'] ?? ''); ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!$podeOperar): ?>
    <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Selecione uma empresa acima para visualizar e gerenciar os colaboradores.</div>
  <?php else: ?>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">Colaboradores <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span></h5>
      <div class="d-flex gap-2">
        <?php if ($podeImportar): ?>
          <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportar">
            <i class="bi bi-upload me-1"></i>Importar CSV
          </button>
        <?php endif; ?>
        <?php if ($podeCriar): ?>
          <button class="btn btn-primary btn-sm" onclick="abrirModalCriar()"><i class="bi bi-plus-circle me-1"></i>Novo Colaborador</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="colaboradores">
        <?php if ($selectedEmpresaId): ?><input type="hidden" name="empresa_id" value="<?php echo (int)$selectedEmpresaId; ?>"><?php endif; ?>
        <div class="col-12 col-md-4">
          <label class="form-label form-label-sm">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Nome, e-mail ou matrícula..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Setor</label>
          <select name="setor_id" class="form-select form-select-sm">
            <option value="">Todos</option>
            <?php foreach ($setores as $s): ?>
              <option value="<?php echo (int)$s['id']; ?>" <?php echo $setorId === (int)$s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['nome']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm">Status</label>
          <select name="ativo" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="1" <?php echo $ativo === '1' ? 'selected' : ''; ?>>Ativo</option>
            <option value="0" <?php echo $ativo === '0' ? 'selected' : ''; ?>>Inativo</option>
          </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtrar</button>
          <a href="?paginas=colaboradores<?php echo $selectedEmpresaId ? '&empresa_id='.$selectedEmpresaId : ''; ?>" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
        </div>
      </form>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr><th>Nome</th><th>E-mail</th><th>Cargo</th><th>CPF</th><th>Setor</th><th>Status</th><th>Origem</th><th>Ações</th></tr>
          </thead>
          <tbody>
            <?php if (empty($colaboradores)): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">Nenhum colaborador encontrado.</td></tr>
            <?php else: foreach ($colaboradores as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($c['nome']); ?><?php if (!empty($c['matricula'])): ?><br><span class="text-muted small">Mat. <?php echo htmlspecialchars($c['matricula']); ?></span><?php endif; ?></td>
                <td class="small"><?php echo htmlspecialchars($c['email'] ?? '—'); ?></td>
                <td class="small"><?php echo htmlspecialchars($c['cargo'] ?? '—'); ?></td>
                <td class="small">
                  <span id="cpf-<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['cpf_mascarado'] ?? '—'); ?></span>
                  <?php if ($podeVerSensivel && $c['cpf_mascarado']): ?>
                    <button class="btn btn-sm btn-link p-0 ms-1" title="Revelar CPF (fica registrado no log de auditoria)" onclick="revelarCpf(<?php echo (int)$c['id']; ?>)"><i class="bi bi-eye"></i></button>
                  <?php endif; ?>
                </td>
                <td class="small"><?php echo htmlspecialchars($c['setor']['nome'] ?? '—'); ?></td>
                <td><span class="badge <?php echo $c['ativo'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $c['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                <td class="small text-muted"><?php echo $c['origem'] === 'importacao_csv' ? 'CSV' : 'Manual'; ?></td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <?php if ($podeEditar): ?>
                      <button class="btn btn-sm btn-outline-primary" title="Editar" onclick='abrirModalEditar(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
                    <?php endif; ?>
                    <?php if ($podeExcluir): ?>
                      <button class="btn btn-sm btn-outline-warning" title="Anonimizar dados pessoais (LGPD)" onclick="anonimizarColaborador(<?php echo (int)$c['id']; ?>, '<?php echo addslashes($c['nome']); ?>')"><i class="bi bi-incognito"></i></button>
                      <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="deletarColaborador(<?php echo (int)$c['id']; ?>, '<?php echo addslashes($c['nome']); ?>')"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($lastPage > 1): ?>
        <?php $qBase = http_build_query(array_filter(['paginas' => 'colaboradores', 'empresa_id' => $selectedEmpresaId ?: null, 'search' => $search, 'setor_id' => $setorId ?: null, 'ativo' => $ativo])); ?>
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-end">
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
              <li class="page-item <?php echo $p === $currPage ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo $qBase; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a></li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- Modal Criar/Editar -->
<div class="modal fade" id="modalColaborador" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloModalColaborador">Novo Colaborador</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formColaborador">
          <input type="hidden" name="id" id="colId">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" id="colNome" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" id="colEmail" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">CPF</label>
              <input type="text" name="cpf" id="colCpf" class="form-control" placeholder="000.000.000-00">
              <div class="form-text">Armazenado sempre cifrado — exibido mascarado depois de salvo.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Data de nascimento</label>
              <input type="date" name="data_nascimento" id="colDataNascimento" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Matrícula</label>
              <input type="text" name="matricula" id="colMatricula" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cargo</label>
              <input type="text" name="cargo" id="colCargo" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Setor</label>
              <select name="setor_id" id="colSetorId" class="form-select">
                <option value="">Nenhum</option>
                <?php foreach ($setores as $s): ?>
                  <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Filial</label>
              <select name="filial_id" id="colFilialId" class="form-select">
                <option value="">Nenhuma</option>
                <?php foreach ($filiais as $f): ?>
                  <option value="<?php echo (int)$f['id']; ?>"><?php echo htmlspecialchars($f['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="ativo" id="colAtivo" checked>
                <label class="form-check-label" for="colAtivo">Ativo</label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarColaborador(this)"><i class="bi bi-save me-1"></i>Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Importar CSV -->
<div class="modal fade" id="modalImportar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Importar Colaboradores via CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted">
          Colunas reconhecidas: <code>nome</code> (obrigatória), <code>cpf</code>, <code>data_nascimento</code>,
          <code>email</code>, <code>cargo</code>, <code>matricula</code>, <code>setor</code>, <code>filial</code>.
          Setor/filial são casados pelo nome já cadastrado na empresa. Colaboradores com o mesmo CPF (ou matrícula,
          quando não houver CPF) já existentes são atualizados, não duplicados.
        </p>
        <input type="file" id="arquivoCsv" class="form-control" accept=".csv,text/csv">
        <div id="resultadoImportacao" class="mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" onclick="importarCsv(this)"><i class="bi bi-upload me-1"></i>Importar</button>
      </div>
    </div>
  </div>
</div>

<script>
const EMPRESA_CTX_COL = <?php echo json_encode($selectedEmpresaId ?: null); ?>;

function abrirModalCriar() {
  document.getElementById('formColaborador').reset();
  document.getElementById('colId').value = '';
  document.getElementById('colAtivo').checked = true;
  document.getElementById('tituloModalColaborador').textContent = 'Novo Colaborador';
  new bootstrap.Modal(document.getElementById('modalColaborador')).show();
}

function abrirModalEditar(c) {
  document.getElementById('colId').value = c.id;
  document.getElementById('colNome').value = c.nome || '';
  document.getElementById('colEmail').value = c.email || '';
  document.getElementById('colCpf').value = '';
  document.getElementById('colCpf').placeholder = c.cpf_mascarado || '000.000.000-00';
  document.getElementById('colDataNascimento').value = '';
  document.getElementById('colMatricula').value = c.matricula || '';
  document.getElementById('colCargo').value = c.cargo || '';
  document.getElementById('colSetorId').value = c.setor_id || '';
  document.getElementById('colFilialId').value = c.filial_id || '';
  document.getElementById('colAtivo').checked = !!c.ativo;
  document.getElementById('tituloModalColaborador').textContent = 'Editar Colaborador';
  new bootstrap.Modal(document.getElementById('modalColaborador')).show();
}

async function salvarColaborador(btn) {
  bslSetLoading(btn, true);
  const id = document.getElementById('colId').value;
  const data = bslFormData('formColaborador');
  delete data.id;
  data.ativo = document.getElementById('colAtivo').checked;
  if (!data.cpf) delete data.cpf; // não sobrescreve CPF já salvo se o campo ficou em branco na edição

  const res = id
    ? await apiFetch('PUT', 'pesquisa-psicossocial/colaboradores/' + id, data, EMPRESA_CTX_COL)
    : await apiFetch('POST', 'pesquisa-psicossocial/colaboradores', data, EMPRESA_CTX_COL);
  bslSetLoading(btn, false);
  if (res.sucesso) { bslToast('Colaborador salvo com sucesso.', 'success'); bslCloseModal('modalColaborador'); setTimeout(() => location.reload(), 700); }
  else { bslToast(res.mensagem || 'Erro ao salvar colaborador.', 'danger'); }
}

async function deletarColaborador(id, nome) {
  if (!confirm('Excluir o colaborador "' + nome + '"?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/colaboradores/' + id, null, EMPRESA_CTX_COL);
  if (res.sucesso) { bslToast('Colaborador removido.', 'success'); setTimeout(() => location.reload(), 700); }
  else { bslToast(res.mensagem || 'Erro ao remover colaborador.', 'danger'); }
}

async function anonimizarColaborador(id, nome) {
  if (!confirm('Anonimizar os dados pessoais de "' + nome + '"? O nome, CPF, e-mail e data de nascimento serão apagados permanentemente (LGPD). Esta ação não pode ser desfeita.')) return;
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/colaboradores/' + id + '/anonimizar', null, EMPRESA_CTX_COL);
  if (res.sucesso) { bslToast('Dados pessoais anonimizados.', 'success'); setTimeout(() => location.reload(), 700); }
  else { bslToast(res.mensagem || 'Erro ao anonimizar colaborador.', 'danger'); }
}

async function revelarCpf(id) {
  const res = await apiFetch('GET', 'pesquisa-psicossocial/colaboradores/' + id + '/dados-sensiveis', null, EMPRESA_CTX_COL);
  if (res.sucesso) {
    document.getElementById('cpf-' + id).textContent = res.dados.cpf || '—';
    bslToast('Este acesso ao CPF em claro foi registrado no log de auditoria.', 'warning');
  } else {
    bslToast(res.mensagem || 'Erro ao revelar dado sensível.', 'danger');
  }
}

function importarCsv(btn) {
  const arquivo = document.getElementById('arquivoCsv').files[0];
  if (!arquivo) { bslToast('Selecione um arquivo CSV.', 'warning'); return; }

  const leitor = new FileReader();
  leitor.onload = async function (e) {
    bslSetLoading(btn, true);
    const res = await apiFetch('POST', 'pesquisa-psicossocial/colaboradores/importar', {
      conteudo_csv: e.target.result,
    }, EMPRESA_CTX_COL);
    bslSetLoading(btn, false);

    const container = document.getElementById('resultadoImportacao');
    if (res.sucesso) {
      let html = '<div class="alert alert-success py-2 mb-2">' + res.mensagem + '</div>';
      if (res.dados.avisos && res.dados.avisos.length) {
        html += '<div class="alert alert-warning py-2 mb-2"><strong>Avisos:</strong><ul class="mb-0">' + res.dados.avisos.map(a => '<li>' + a + '</li>').join('') + '</ul></div>';
      }
      if (res.dados.erros && res.dados.erros.length) {
        html += '<div class="alert alert-danger py-2 mb-0"><strong>Erros:</strong><ul class="mb-0">' + res.dados.erros.map(er => '<li>Linha ' + er.linha + ' (' + er.nome + '): ' + er.motivo + '</li>').join('') + '</ul></div>';
      }
      container.innerHTML = html;
      setTimeout(() => location.reload(), 2500);
    } else {
      container.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + (res.mensagem || 'Erro ao importar arquivo.') + '</div>';
    }
  };
  leitor.readAsText(arquivo, 'UTF-8');
}
</script>
