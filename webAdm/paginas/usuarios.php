<?php
$isSuperAdmin        = Auth::isSuperAdmin();
$selectedEmpresaId   = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;

$search = trim($_GET['search'] ?? '');
$tipo   = $_GET['tipo']   ?? '';
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

// Empresas list (only for superadmin, to populate the selector)
$empresas = [];
if ($isSuperAdmin) {
    $empApi   = new ApiClient(Auth::getToken());
    $respEmp  = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

// API client scoped to the selected empresa (or null for regular admin, which uses own empresa_id)
$api = new ApiClient(Auth::getToken(), $selectedEmpresaId ?: null);

// Load users only when empresa is selected (superadmin) or always for regular admin
$usuarios = [];
$total    = 0;
$lastPage = 1;
$currPage = 1;

if (!$isSuperAdmin || $selectedEmpresaId) {
    $filters = ['page' => $page];
    if ($search !== '') $filters['search'] = $search;
    if ($tipo   !== '') $filters['tipo']   = $tipo;
    if ($status !== '') $filters['status'] = $status;

    $resp     = $api->get('usuarios', $filters);
    $usuarios = $resp['data']['dados']['data']       ?? [];
    $total    = $resp['data']['dados']['total']      ?? 0;
    $lastPage = $resp['data']['dados']['last_page']    ?? 1;
    $currPage = $resp['data']['dados']['current_page'] ?? 1;
}

// Load filiais for dropdowns (only when empresa context is available)
$filiais = [];
if (!$isSuperAdmin || $selectedEmpresaId) {
    $respFil = $api->get('filiais', ['per_page' => 100]);
    $filiais = $respFil['data']['dados']['data'] ?? ($respFil['data']['dados'] ?? []);
}
?>

<!-- Page Title -->
<div class="pagetitle">
  <h1>Usuarios</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?paginas=home">Inicio</a></li>
      <li class="breadcrumb-item active">Usuarios</li>
    </ol>
  </nav>
</div>

<section class="section">

  <?php if ($isSuperAdmin): ?>
  <!-- Empresa selector for superadmin -->
  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="GET" class="d-flex align-items-center gap-3 flex-wrap mb-0">
        <input type="hidden" name="paginas" value="usuarios">
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
          <a href="?paginas=usuarios" class="btn btn-outline-secondary btn-sm">
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
      Selecione uma empresa acima para visualizar e gerenciar os usuarios.
    </div>
  <?php else: ?>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        Lista de Usuarios
        <span class="badge bg-secondary ms-2"><?php echo (int)$total; ?></span>
      </h5>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriar">
        <i class="bi bi-plus-circle me-1"></i> Novo Usuario
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="card-body pb-0">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="paginas" value="usuarios">
        <?php if ($isSuperAdmin && $selectedEmpresaId): ?>
          <input type="hidden" name="empresa_id" value="<?php echo (int)$selectedEmpresaId; ?>">
        <?php endif; ?>
        <div class="col-12 col-md-4">
          <label class="form-label form-label-sm">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Nome ou e-mail..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm">Tipo</label>
          <select name="tipo" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="admin"   <?php echo $tipo === 'admin'   ? 'selected' : ''; ?>>Admin</option>
            <option value="gerente" <?php echo $tipo === 'gerente' ? 'selected' : ''; ?>>Gerente</option>
            <option value="usuario" <?php echo $tipo === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="ativo"     <?php echo $status === 'ativo'     ? 'selected' : ''; ?>>Ativo</option>
            <option value="inativo"   <?php echo $status === 'inativo'   ? 'selected' : ''; ?>>Inativo</option>
            <option value="bloqueado" <?php echo $status === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search"></i> Filtrar
          </button>
          <a href="?paginas=usuarios<?php echo $selectedEmpresaId ? '&empresa_id='.$selectedEmpresaId : ''; ?>"
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
              <th>E-mail</th>
              <th>Tipo</th>
              <th>Status</th>
              <th>Ultimo Login</th>
              <th>Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($usuarios)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Nenhum usuario encontrado.</td></tr>
            <?php else: ?>
              <?php foreach ($usuarios as $usr): ?>
                <?php
                  $tipoBadge = match($usr['tipo'] ?? '') {
                      'admin'   => 'bg-danger',
                      'gerente' => 'bg-warning text-dark',
                      'usuario' => 'bg-info text-dark',
                      default   => 'bg-secondary',
                  };
                  $statusBadge = match($usr['status'] ?? '') {
                      'ativo'     => 'bg-success',
                      'inativo'   => 'bg-secondary',
                      'bloqueado' => 'bg-danger',
                      default     => 'bg-light text-dark',
                  };
                  $isAtivo = ($usr['status'] ?? '') === 'ativo';
                ?>
                <tr>
                  <td><?php echo (int)$usr['id']; ?></td>
                  <td><?php echo htmlspecialchars($usr['nome'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars($usr['email'] ?? '-'); ?></td>
                  <td><span class="badge <?php echo $tipoBadge; ?>"><?php echo htmlspecialchars($usr['tipo'] ?? '-'); ?></span></td>
                  <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($usr['status'] ?? '-'); ?></span></td>
                  <td class="text-muted small"><?php echo htmlspecialchars($usr['ultimo_login'] ?? '-'); ?></td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <button class="btn btn-sm btn-outline-primary" title="Editar"
                              onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usr), ENT_QUOTES); ?>)">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm <?php echo $isAtivo ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                              title="<?php echo $isAtivo ? 'Bloquear' : 'Ativar'; ?>"
                              onclick="bloquearUsuario(<?php echo (int)$usr['id']; ?>, '<?php echo addslashes($usr['status'] ?? 'ativo'); ?>')">
                        <i class="bi <?php echo $isAtivo ? 'bi-lock' : 'bi-unlock'; ?>"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-secondary" title="Redefinir Senha"
                              onclick="resetSenha(<?php echo (int)$usr['id']; ?>, '<?php echo addslashes($usr['nome'] ?? ''); ?>')">
                        <i class="bi bi-key"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger" title="Excluir"
                              onclick="deletarUsuario(<?php echo (int)$usr['id']; ?>, '<?php echo addslashes($usr['nome'] ?? ''); ?>')">
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
              'paginas'    => 'usuarios',
              'empresa_id' => $selectedEmpresaId ?: null,
              'search'     => $search,
              'tipo'       => $tipo,
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCriarLabel"><i class="bi bi-person-plus me-2"></i>Novo Usuario</h5>
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
              <label class="form-label">E-mail <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Senha <span class="text-danger">*</span></label>
              <input type="password" name="senha" class="form-control" required minlength="8">
              <div class="form-text">Minimo de 8 caracteres.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo <span class="text-danger">*</span></label>
              <select name="tipo" class="form-select" required>
                <option value="">Selecione...</option>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="usuario">Usuario</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Filial</label>
              <select name="filial_id" id="criarFilialId" class="form-select">
                <option value="">Nenhuma</option>
                <?php foreach ($filiais as $fil): ?>
                  <option value="<?php echo (int)$fil['id']; ?>">
                    <?php echo htmlspecialchars($fil['nome'] ?? ''); ?>
                  </option>
                <?php endforeach; ?>
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
        <h5 class="modal-title" id="modalEditarLabel"><i class="bi bi-pencil me-2"></i>Editar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar">
          <input type="hidden" name="id" id="editId">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome</label>
              <input type="text" name="nome" id="editNome" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-mail</label>
              <input type="email" name="email" id="editEmail" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone</label>
              <input type="text" name="telefone" id="editTelefone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo</label>
              <select name="tipo" id="editTipo" class="form-select">
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="usuario">Usuario</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Filial</label>
              <select name="filial_id" id="editFilialId" class="form-select">
                <option value="">Nenhuma</option>
                <?php foreach ($filiais as $fil): ?>
                  <option value="<?php echo (int)$fil['id']; ?>">
                    <?php echo htmlspecialchars($fil['nome'] ?? ''); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
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
// Empresa context for superadmin tenant scoping in API calls
const EMPRESA_CTX = <?php echo json_encode($selectedEmpresaId ?: null); ?>;
const IS_SUPERADMIN_USR = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;

function editarUsuario(u) {
  document.getElementById('editId').value       = u.id        ?? '';
  document.getElementById('editNome').value     = u.nome      ?? '';
  document.getElementById('editEmail').value    = u.email     ?? '';
  document.getElementById('editTelefone').value = u.telefone  ?? '';
  document.getElementById('editTipo').value     = u.tipo      ?? 'usuario';
  document.getElementById('editStatus').value   = u.status    ?? 'ativo';

  // Load filiais for the user's empresa (superadmin) or use server-rendered ones
  if (IS_SUPERADMIN_USR && u.empresa_id) {
    bslCarregarFiliais(u.empresa_id, 'editFilialId', u.filial_id);
  } else {
    document.getElementById('editFilialId').value = u.filial_id ?? '';
  }

  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function salvarCriar(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formCriar');
  const res  = await apiFetch('POST', 'usuarios', data, EMPRESA_CTX);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Usuario criado com sucesso!', 'success');
    bslCloseModal('modalCriar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao criar usuario.', 'danger');
  }
}

async function salvarEditar(btn) {
  bslSetLoading(btn, true);
  const id   = document.getElementById('editId').value;
  const data = bslFormData('formEditar');
  delete data.id;
  const res  = await apiFetch('PUT', 'usuarios/' + id, data, EMPRESA_CTX);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Usuario atualizado com sucesso!', 'success');
    bslCloseModal('modalEditar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar usuario.', 'danger');
  }
}

async function deletarUsuario(id, nome) {
  if (!confirm('Deseja excluir o usuario "' + nome + '"? Esta acao nao pode ser desfeita.')) return;
  const res = await apiFetch('DELETE', 'usuarios/' + id, null, EMPRESA_CTX);
  if (res.sucesso) {
    bslToast('Usuario excluido.', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao excluir usuario.', 'danger');
  }
}

async function bloquearUsuario(id, status) {
  const novoStatus = status === 'ativo' ? 'bloqueado' : 'ativo';
  const acao = novoStatus === 'bloqueado' ? 'bloquear' : 'ativar';
  if (!confirm('Deseja ' + acao + ' este usuario?')) return;
  const res = await apiFetch('PATCH', 'usuarios/' + id + '/bloquear', { status: novoStatus }, EMPRESA_CTX);
  if (res.sucesso) {
    bslToast('Status do usuario atualizado.', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao alterar status.', 'danger');
  }
}

async function resetSenha(id, nome) {
  if (!confirm('Deseja redefinir a senha do usuario "' + nome + '"?')) return;
  const res = await apiFetch('POST', 'usuarios/' + id + '/resetar-senha', null, EMPRESA_CTX);
  if (res.sucesso) {
    const novaSenha = res.dados?.nova_senha ?? '';
    const msg = novaSenha
      ? 'Senha redefinida. Nova senha: <strong>' + novaSenha + '</strong>'
      : 'Senha redefinida com sucesso.';
    bslToast(msg, 'success');
  } else {
    bslToast(res.mensagem || 'Erro ao redefinir senha.', 'danger');
  }
}
</script>
