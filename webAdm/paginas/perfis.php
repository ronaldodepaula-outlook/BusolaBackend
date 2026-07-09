<?php
$isSuperAdmin      = Auth::isSuperAdmin();
$selectedEmpresaId = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;

// Empresas list for superadmin selector
$empresas = [];
if ($isSuperAdmin) {
    $empApi  = new ApiClient(Auth::getToken());
    $respEmp = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

// Superadmin without empresa sees only system roles; with empresa sees empresa + system roles
$api   = new ApiClient(Auth::getToken(), $selectedEmpresaId ?: null);
$resp  = $api->get('roles');
$roles = $resp['data']['dados'] ?? [];
?>

<!-- Page Title -->
<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Perfis de Acesso</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Inicio</a></li>
        <li class="breadcrumb-item active">Perfis de Acesso</li>
      </ol>
    </nav>
  </div>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriar">
    <i class="bi bi-plus-circle me-1"></i> Novo Perfil
  </button>
</div>

<?php if ($isSuperAdmin): ?>
<!-- Empresa selector for superadmin -->
<div class="card mb-3">
  <div class="card-body py-3">
    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap mb-0">
      <input type="hidden" name="paginas" value="perfis">
      <label class="form-label mb-0 fw-semibold">
        <i class="bi bi-building me-1 text-primary"></i> Empresa (perfis customizados):
      </label>
      <select name="empresa_id" class="form-select form-select-sm" style="width:auto;min-width:220px" onchange="this.form.submit()">
        <option value="">Apenas perfis do sistema</option>
        <?php foreach ($empresas as $emp): ?>
          <option value="<?php echo (int)$emp['id']; ?>"
            <?php echo $selectedEmpresaId === (int)$emp['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($emp['nome'] ?? ''); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">
        Perfis de Acesso
        <span class="badge bg-secondary ms-2"><?php echo count($roles); ?></span>
      </h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Slug</th>
              <th>Descricao</th>
              <th>Status</th>
              <th>Sistema?</th>
              <th>Permissoes</th>
              <th>Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($roles)): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">Nenhum perfil encontrado.</td></tr>
            <?php else: ?>
              <?php foreach ($roles as $role): ?>
                <?php
                  $statusBadge = ($role['status'] ?? '') === 'ativo' ? 'bg-success' : 'bg-secondary';
                  $isSistema   = !empty($role['sistema']);
                ?>
                <tr>
                  <td><?php echo (int)$role['id']; ?></td>
                  <td><?php echo htmlspecialchars($role['nome'] ?? '-'); ?></td>
                  <td><small class="text-muted"><code><?php echo htmlspecialchars($role['slug'] ?? '-'); ?></code></small></td>
                  <td class="text-muted small"><?php echo htmlspecialchars($role['descricao'] ?? '-'); ?></td>
                  <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($role['status'] ?? '-'); ?></span></td>
                  <td>
                    <?php if ($isSistema): ?>
                      <span class="badge bg-info text-dark"><i class="bi bi-lock-fill me-1"></i>Sim</span>
                    <?php else: ?>
                      <span class="badge bg-light text-dark">Nao</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?php echo count($role['permissoes'] ?? []); ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <button class="btn btn-sm btn-outline-primary" title="Editar"
                              onclick="editarPerfil(<?php echo htmlspecialchars(json_encode($role), ENT_QUOTES); ?>)">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <?php if (!$isSistema): ?>
                        <button class="btn btn-sm btn-outline-danger" title="Excluir"
                                onclick="deletarPerfil(<?php echo (int)$role['id']; ?>, '<?php echo addslashes($role['nome'] ?? ''); ?>')">
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
    </div>
  </div>
</section>

<!-- Modal Criar -->
<div class="modal fade" id="modalCriar" tabindex="-1" aria-labelledby="modalCriarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCriarLabel"><i class="bi bi-shield-plus me-2"></i>Novo Perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCriar">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nome <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Descricao</label>
              <textarea name="descricao" class="form-control" rows="3"></textarea>
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
        <h5 class="modal-title" id="modalEditarLabel"><i class="bi bi-pencil me-2"></i>Editar Perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar">
          <input type="hidden" name="id" id="editId">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nome</label>
              <input type="text" name="nome" id="editNome" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Descricao</label>
              <textarea name="descricao" id="editDescricao" class="form-control" rows="3"></textarea>
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
const EMPRESA_CTX_PERFIS = <?php echo json_encode($selectedEmpresaId ?: null); ?>;

function editarPerfil(r) {
  document.getElementById('editId').value        = r.id        ?? '';
  document.getElementById('editNome').value      = r.nome      ?? '';
  document.getElementById('editDescricao').value = r.descricao ?? '';
  document.getElementById('editStatus').value    = r.status    ?? 'ativo';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function salvarCriar(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formCriar');
  const res  = await apiFetch('POST', 'roles', data, EMPRESA_CTX_PERFIS);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Perfil criado com sucesso!', 'success');
    bslCloseModal('modalCriar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao criar perfil.', 'danger');
  }
}

async function salvarEditar(btn) {
  bslSetLoading(btn, true);
  const id   = document.getElementById('editId').value;
  const data = bslFormData('formEditar');
  delete data.id;
  const res  = await apiFetch('PUT', 'roles/' + id, data, EMPRESA_CTX_PERFIS);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Perfil atualizado com sucesso!', 'success');
    bslCloseModal('modalEditar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar perfil.', 'danger');
  }
}

async function deletarPerfil(id, nome) {
  if (!confirm('Deseja excluir o perfil "' + nome + '"? Esta acao nao pode ser desfeita.')) return;
  const res = await apiFetch('DELETE', 'roles/' + id, null, EMPRESA_CTX_PERFIS);
  if (res.sucesso) {
    bslToast('Perfil excluido.', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao excluir perfil.', 'danger');
  }
}
</script>
