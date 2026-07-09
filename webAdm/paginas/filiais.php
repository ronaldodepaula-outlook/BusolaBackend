<?php
$isSuperAdmin      = Auth::isSuperAdmin();
$selectedEmpresaId = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

// Empresas list for superadmin selector
$empresas = [];
if ($isSuperAdmin) {
    $empApi  = new ApiClient(Auth::getToken());
    $respEmp = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

// API client scoped to selected empresa
$api = new ApiClient(Auth::getToken(), $selectedEmpresaId ?: null);

$filiais  = [];
$total    = 0;
$lastPage = 1;
$currPage = 1;

if (!$isSuperAdmin || $selectedEmpresaId) {
    $filters = ['page' => $page];
    if ($search !== '') $filters['search'] = $search;
    if ($status !== '') $filters['status'] = $status;

    $resp    = $api->get('filiais', $filters);
    $filiais = $resp['data']['dados']['data']       ?? [];
    $total   = $resp['data']['dados']['total']      ?? 0;
    $lastPage = $resp['data']['dados']['last_page']    ?? 1;
    $currPage = $resp['data']['dados']['current_page'] ?? 1;
}
?>

<!-- Page Title -->
<div class="pagetitle">
  <h1>Filiais</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?paginas=home">Inicio</a></li>
      <li class="breadcrumb-item active">Filiais</li>
    </ol>
  </nav>
</div>

<section class="section">

  <?php if ($isSuperAdmin): ?>
  <!-- Empresa selector for superadmin -->
  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="GET" class="d-flex align-items-center gap-3 flex-wrap mb-0">
        <input type="hidden" name="paginas" value="filiais">
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
          <a href="?paginas=filiais" class="btn btn-outline-secondary btn-sm">
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
      Selecione uma empresa acima para visualizar e gerenciar as filiais.
    </div>
  <?php else: ?>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        Lista de Filiais
        <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span>
      </h5>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriar">
        <i class="bi bi-plus-circle me-1"></i> Nova Filial
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="filiais">
        <?php if ($isSuperAdmin && $selectedEmpresaId): ?>
          <input type="hidden" name="empresa_id" value="<?php echo (int)$selectedEmpresaId; ?>">
        <?php endif; ?>
        <div class="col-12 col-md-5">
          <label class="form-label form-label-sm">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Nome, codigo ou responsavel..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="ativo"   <?php echo $status === 'ativo'   ? 'selected' : ''; ?>>Ativo</option>
            <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
          </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search"></i> Filtrar
          </button>
          <a href="?paginas=filiais<?php echo $selectedEmpresaId ? '&empresa_id='.$selectedEmpresaId : ''; ?>"
             class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Codigo</th>
              <th>Responsavel</th>
              <th>Status</th>
              <th>Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($filiais)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma filial encontrada.</td></tr>
            <?php else: ?>
              <?php foreach ($filiais as $fil): ?>
                <?php
                  $statusBadge = ($fil['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary';
                ?>
                <tr>
                  <td><?php echo (int)$fil['id']; ?></td>
                  <td><?php echo htmlspecialchars($fil['nome'] ?? '-'); ?></td>
                  <td><code><?php echo htmlspecialchars($fil['codigo'] ?? '-'); ?></code></td>
                  <td><?php echo htmlspecialchars($fil['responsavel'] ?? '-'); ?></td>
                  <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($fil['status'] ?? '-'); ?></span></td>
                  <td>
                    <div class="d-flex gap-1">
                      <button class="btn btn-sm btn-outline-primary" title="Editar"
                              onclick="editarFilial(<?php echo htmlspecialchars(json_encode($fil), ENT_QUOTES); ?>)">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger" title="Excluir"
                              onclick="deletarFilial(<?php echo (int)$fil['id']; ?>, '<?php echo addslashes($fil['nome'] ?? ''); ?>')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
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
              'paginas'    => 'filiais',
              'empresa_id' => $selectedEmpresaId ?: null,
              'search'     => $search,
              'status'     => $status,
          ]));
        ?>
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-end">
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
              <li class="page-item <?php echo $p === $currPage ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo $qBase; ?>&page=<?php echo $p; ?>">
                  <?php echo $p; ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>

  <?php endif; ?>

</section>

<!-- Modal Criar -->
<div class="modal fade" id="modalCriar" tabindex="-1" aria-labelledby="modalCriarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCriarLabel"><i class="bi bi-diagram-3 me-2"></i>Nova Filial</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCriar">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Codigo</label>
              <input type="text" name="codigo" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsavel</label>
              <input type="text" name="responsavel" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="ativo" selected>Ativo</option>
                <option value="inativo">Inativo</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarCriar(this)">
          <i class="bi bi-save me-1"></i> Salvar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel"><i class="bi bi-pencil me-2"></i>Editar Filial</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar">
          <input type="hidden" name="id" id="editId">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" id="editNome" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Codigo</label>
              <input type="text" name="codigo" id="editCodigo" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsavel</label>
              <input type="text" name="responsavel" id="editResponsavel" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" id="editTelefone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" id="editEmail" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" id="editStatus" class="form-select">
                <option value="ativo">Ativo</option>
                <option value="inativo">Inativo</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarEditar(this)">
          <i class="bi bi-save me-1"></i> Salvar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const EMPRESA_CTX_FIL = <?php echo json_encode($selectedEmpresaId ?: null); ?>;

function editarFilial(f) {
  document.getElementById('editId').value          = f.id          ?? '';
  document.getElementById('editNome').value        = f.nome        ?? '';
  document.getElementById('editCodigo').value      = f.codigo      ?? '';
  document.getElementById('editResponsavel').value = f.responsavel ?? '';
  document.getElementById('editTelefone').value    = f.telefone    ?? '';
  document.getElementById('editEmail').value       = f.email       ?? '';
  document.getElementById('editStatus').value      = f.status      ?? 'ativo';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function salvarCriar(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formCriar');
  const res  = await apiFetch('POST', 'filiais', data, EMPRESA_CTX_FIL);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Filial criada com sucesso!', 'success');
    bslCloseModal('modalCriar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao criar filial.', 'danger');
  }
}

async function salvarEditar(btn) {
  bslSetLoading(btn, true);
  const id   = document.getElementById('editId').value;
  const data = bslFormData('formEditar');
  delete data.id;
  const res  = await apiFetch('PUT', 'filiais/' + id, data, EMPRESA_CTX_FIL);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Filial atualizada com sucesso!', 'success');
    bslCloseModal('modalEditar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar filial.', 'danger');
  }
}

async function deletarFilial(id, nome) {
  if (!confirm('Deseja excluir a filial "' + nome + '"? Esta acao nao pode ser desfeita.')) return;
  const res = await apiFetch('DELETE', 'filiais/' + id, null, EMPRESA_CTX_FIL);
  if (res.sucesso) {
    bslToast('Filial excluida.', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao excluir filial.', 'danger');
  }
}
</script>
