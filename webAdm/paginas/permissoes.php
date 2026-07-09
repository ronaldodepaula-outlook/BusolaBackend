<?php
if (!Auth::isSuperAdmin()) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Acesso restrito ao Super Administrador.</div>';
    return;
}

$api = new ApiClient(Auth::getToken());

$respGrupos = $api->get('permissoes/por-modulo');
$grupos     = $respGrupos['data']['dados'] ?? [];

$respTodas       = $api->get('permissoes');
$todasPermissoes = $respTodas['data']['dados'] ?? [];
?>

<!-- Page Title -->
<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Permissões</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Permissões</li>
      </ol>
    </nav>
  </div>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriar">
    <i class="bi bi-plus-circle me-1"></i> Nova Permissão
  </button>
</div>

<section class="section">

  <?php if (empty($grupos)): ?>
    <div class="alert alert-info">Nenhuma permissão cadastrada.</div>
  <?php else: ?>

    <div class="accordion" id="accordionPerms">
      <?php foreach ($grupos as $idx => $grupo): ?>
        <?php
          $grupoId   = 'grupo-' . $idx;
          $modulo    = htmlspecialchars($grupo['modulo'] ?? 'Sem módulo');
          $perms     = $grupo['permissoes'] ?? [];
          $isFirst   = $idx === 0;
        ?>
        <div class="accordion-item">
          <h2 class="accordion-header" id="heading-<?php echo $grupoId; ?>">
            <button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-<?php echo $grupoId; ?>"
                    aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
                    aria-controls="collapse-<?php echo $grupoId; ?>">
              <i class="bi bi-folder me-2 text-primary"></i>
              <strong><?php echo $modulo; ?></strong>
              <span class="badge bg-secondary ms-2"><?php echo count($perms); ?></span>
            </button>
          </h2>
          <div id="collapse-<?php echo $grupoId; ?>"
               class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>"
               aria-labelledby="heading-<?php echo $grupoId; ?>"
               data-bs-parent="#accordionPerms">
            <div class="accordion-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Nome</th>
                      <th>Slug</th>
                      <th>Descrição</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($perms)): ?>
                      <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma permissão neste módulo.</td></tr>
                    <?php else: ?>
                      <?php foreach ($perms as $perm): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($perm['nome'] ?? '—'); ?></td>
                          <td><code class="badge bg-light text-dark border"><?php echo htmlspecialchars($perm['slug'] ?? '—'); ?></code></td>
                          <td class="text-muted small"><?php echo htmlspecialchars($perm['descricao'] ?? '—'); ?></td>
                          <td>
                            <div class="d-flex gap-1">
                              <button class="btn btn-xs btn-sm btn-outline-primary" title="Editar"
                                      onclick="editarPermissao(<?php echo htmlspecialchars(json_encode($perm), ENT_QUOTES); ?>)">
                                <i class="bi bi-pencil"></i>
                              </button>
                              <button class="btn btn-xs btn-sm btn-outline-danger" title="Excluir"
                                      onclick="deletarPermissao(<?php echo (int)$perm['id']; ?>, '<?php echo addslashes($perm['nome'] ?? ''); ?>')">
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
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</section>

<!-- Modal Criar -->
<div class="modal fade" id="modalCriar" tabindex="-1" aria-labelledby="modalCriarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCriarLabel"><i class="bi bi-key me-2"></i>Nova Permissão</h5>
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
              <label class="form-label">Slug <span class="text-danger">*</span></label>
              <input type="text" name="slug" class="form-control" required placeholder="modulo.acao">
            </div>
            <div class="col-12">
              <label class="form-label">Módulo <span class="text-danger">*</span></label>
              <input type="text" name="modulo" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" class="form-control" rows="2"></textarea>
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
        <h5 class="modal-title" id="modalEditarLabel"><i class="bi bi-pencil me-2"></i>Editar Permissão</h5>
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
              <label class="form-label">Slug</label>
              <input type="text" name="slug" id="editSlug" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Módulo</label>
              <input type="text" name="modulo" id="editModulo" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" id="editDescricao" class="form-control" rows="2"></textarea>
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
function editarPermissao(p) {
  document.getElementById('editId').value        = p.id        ?? '';
  document.getElementById('editNome').value      = p.nome      ?? '';
  document.getElementById('editSlug').value      = p.slug      ?? '';
  document.getElementById('editModulo').value    = p.modulo    ?? '';
  document.getElementById('editDescricao').value = p.descricao ?? '';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

async function salvarCriar(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formCriar');
  const res  = await apiFetch('POST', 'permissoes', data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Permissão criada com sucesso!', 'success');
    bslCloseModal('modalCriar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao criar permissão.', 'danger');
  }
}

async function salvarEditar(btn) {
  bslSetLoading(btn, true);
  const id   = document.getElementById('editId').value;
  const data = bslFormData('formEditar');
  delete data.id;
  const res  = await apiFetch('PUT', 'permissoes/' + id, data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Permissão atualizada com sucesso!', 'success');
    bslCloseModal('modalEditar');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao atualizar permissão.', 'danger');
  }
}

async function deletarPermissao(id, nome) {
  if (!confirm('Deseja excluir a permissão "' + nome + '"? Esta ação não pode ser desfeita.')) return;
  const res = await apiFetch('DELETE', 'permissoes/' + id);
  if (res.sucesso) {
    bslToast('Permissão excluída.', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    bslToast(res.mensagem || 'Erro ao excluir permissão.', 'danger');
  }
}
</script>
