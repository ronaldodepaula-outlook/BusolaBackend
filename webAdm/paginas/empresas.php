<?php
if (!Auth::isSuperAdmin()) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Acesso restrito ao Super Administrador.</div>';
    return;
}

$api = new ApiClient(Auth::getToken());

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = ['page' => $page];
if ($search !== '') $filters['search'] = $search;
if ($status !== '') $filters['status'] = $status;

$resp     = $api->get('empresas', $filters);
$empresas = $resp['data']['dados']['data']  ?? [];
$total    = $resp['data']['dados']['total'] ?? 0;
$lastPage = $resp['data']['dados']['last_page']    ?? 1;
$currPage = $resp['data']['dados']['current_page'] ?? 1;
?>

<!-- Page Title -->
<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Empresas</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Empresas</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        Lista de Empresas
        <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span>
      </h5>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriar">
        <i class="bi bi-plus-circle me-1"></i> Nova Empresa
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="empresas">
        <div class="col-12 col-md-5">
          <label class="form-label form-label-sm">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Nome, CNPJ ou e-mail…" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="ativo"      <?php echo $status === 'ativo'      ? 'selected' : ''; ?>>Ativo</option>
            <option value="inativo"    <?php echo $status === 'inativo'    ? 'selected' : ''; ?>>Inativo</option>
            <option value="bloqueado"  <?php echo $status === 'bloqueado'  ? 'selected' : ''; ?>>Bloqueado</option>
          </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search"></i> Filtrar
          </button>
          <a href="?paginas=empresas" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
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
              <th>CNPJ</th>
              <th>E-mail</th>
              <th>Plano</th>
              <th>Status</th>
              <th>Filiais</th>
              <th>Usuários</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($empresas)): ?>
              <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma empresa encontrada.</td></tr>
            <?php else: ?>
              <?php foreach ($empresas as $emp): ?>
                <?php
                  $statusBadge = match($emp['status'] ?? '') {
                      'ativo'     => 'bg-success',
                      'inativo'   => 'bg-secondary',
                      'bloqueado' => 'bg-danger',
                      default     => 'bg-light text-dark',
                  };
                  $planoBadge = match($emp['plano'] ?? '') {
                      'basic'        => 'bg-secondary',
                      'professional' => 'bg-primary',
                      'enterprise'   => 'bg-warning text-dark',
                      default        => 'bg-light text-dark',
                  };
                ?>
                <tr>
                  <td><?php echo (int)$emp['id']; ?></td>
                  <td><?php echo htmlspecialchars($emp['nome'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($emp['cnpj'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($emp['email'] ?? '—'); ?></td>
                  <td><span class="badge <?php echo $planoBadge; ?>"><?php echo htmlspecialchars($emp['plano'] ?? '—'); ?></span></td>
                  <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($emp['status'] ?? '—'); ?></span></td>
                  <td><?php echo (int)($emp['total_filiais'] ?? 0); ?></td>
                  <td><?php echo (int)($emp['total_usuarios'] ?? 0); ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <button class="btn btn-sm btn-outline-primary" title="Editar"
                              onclick="editarEmpresa(<?php echo htmlspecialchars(json_encode($emp), ENT_QUOTES); ?>)">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger" title="Excluir"
                              onclick="deletarEmpresa(<?php echo (int)$emp['id']; ?>, '<?php echo addslashes($emp['nome'] ?? ''); ?>')">
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
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-end">
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
              <li class="page-item <?php echo $p === $currPage ? 'active' : ''; ?>">
                <a class="page-link"
                   href="?paginas=empresas&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&page=<?php echo $p; ?>">
                  <?php echo $p; ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>

    </div>
  </div>
</section>

<!-- Modal Criar -->
<div class="modal fade" id="modalCriar" tabindex="-1" aria-labelledby="modalCriarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCriarLabel"><i class="bi bi-building me-2"></i>Nova Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCriar">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">CNPJ</label>
              <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0001-00">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Plano</label>
              <select name="plano" class="form-select">
                <option value="basic">Basic</option>
                <option value="professional" selected>Professional</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Máx. Filiais</label>
              <input type="number" name="max_filiais" class="form-control" value="10" min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Máx. Usuários</label>
              <input type="number" name="max_usuarios" class="form-control" value="50" min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="ativo">Ativo</option>
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel"><i class="bi bi-pencil me-2"></i>Editar Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar">
          <input type="hidden" name="id" id="editId">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" id="editNome" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">CNPJ</label>
              <input type="text" name="cnpj" id="editCnpj" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" id="editEmail" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" id="editTelefone" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Plano</label>
              <select name="plano" id="editPlano" class="form-select">
                <option value="basic">Basic</option>
                <option value="professional">Professional</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Máx. Filiais</label>
              <input type="number" name="max_filiais" id="editMaxFiliais" class="form-control" min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Máx. Usuários</label>
              <input type="number" name="max_usuarios" id="editMaxUsuarios" class="form-control" min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" id="editStatus" class="form-select">
                <option value="ativo">Ativo</option>
                <option value="inativo">Inativo</option>
                <option value="bloqueado">Bloqueado</option>
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
function editarEmpresa(emp) {
  document.getElementById('editId').value          = emp.id          ?? '';
  document.getElementById('editNome').value        = emp.nome        ?? '';
  document.getElementById('editCnpj').value        = emp.cnpj        ?? '';
  document.getElementById('editEmail').value       = emp.email       ?? '';
  document.getElementById('editTelefone').value    = emp.telefone    ?? '';
  document.getElementById('editPlano').value       = emp.plano       ?? 'professional';
  document.getElementById('editMaxFiliais').value  = emp.max_filiais ?? '';
  document.getElementById('editMaxUsuarios').value = emp.max_usuarios ?? '';
  document.getElementById('editStatus').value      = emp.status      ?? 'ativo';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function salvarCriar(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formCriar');
  const res  = await apiFetch('POST', 'empresas', data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Empresa criada com sucesso!', 'success');
    bslCloseModal('modalCriar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao criar empresa.', 'danger');
  }
}

async function salvarEditar(btn) {
  bslSetLoading(btn, true);
  const id   = document.getElementById('editId').value;
  const data = bslFormData('formEditar');
  delete data.id;
  const res  = await apiFetch('PUT', 'empresas/' + id, data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Empresa atualizada com sucesso!', 'success');
    bslCloseModal('modalEditar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar empresa.', 'danger');
  }
}

async function deletarEmpresa(id, nome) {
  if (!confirm('Deseja excluir a empresa "' + nome + '"? Esta ação não pode ser desfeita.')) return;
  const res = await apiFetch('DELETE', 'empresas/' + id);
  if (res.sucesso) {
    bslToast('Empresa excluída.', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao excluir empresa.', 'danger');
  }
}
</script>
