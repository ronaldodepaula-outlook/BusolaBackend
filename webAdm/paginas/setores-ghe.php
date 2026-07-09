<?php
if (!Auth::hasPermission('setor.listar') && !Auth::hasPermission('ghe.listar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$api = new ApiClient(Auth::getToken());
$ghes = $api->get('pesquisa-psicossocial/ghes')['data']['dados'] ?? [];
$setores = $api->get('pesquisa-psicossocial/setores')['data']['dados'] ?? [];

$usuariosResp = $api->get('usuarios', ['per_page' => 200]);
$usuarios = $usuariosResp['data']['dados']['data'] ?? [];

$podeGerirGhe = Auth::hasPermission('ghe.criar') || Auth::hasPermission('ghe.editar');
$podeGerirSetor = Auth::hasPermission('setor.criar') || Auth::hasPermission('setor.editar');
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Setores e GHE</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item active">Pesquisas Psicossociais</li>
        <li class="breadcrumb-item active">Setores e GHE</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="alert alert-light border small">
    <i class="bi bi-info-circle me-1"></i>
    Um <strong>GHE (Grupo Homogêneo de Exposição)</strong> agrupa um ou mais <strong>Setores</strong> por similaridade de
    exposição ocupacional. Os resultados das campanhas são tabulados por GHE — grupos com poucos respondentes são
    combinados automaticamente para preservar o anonimato.
  </div>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ghe">GHEs <span class="badge bg-secondary ms-1"><?php echo count($ghes); ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-setor">Setores <span class="badge bg-secondary ms-1"><?php echo count($setores); ?></span></button></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-ghe">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Grupos Homogêneos de Exposição</h6>
          <?php if ($podeGerirGhe): ?>
            <button class="btn btn-primary btn-sm" onclick="abrirModalGhe()"><i class="bi bi-plus-circle me-1"></i>Novo GHE</button>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Nome</th><th>Descrição</th><th>Setores</th><th>Ações</th></tr></thead>
            <tbody>
              <?php if (empty($ghes)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum GHE cadastrado.</td></tr>
              <?php else: foreach ($ghes as $ghe): ?>
                <tr>
                  <td><?php echo htmlspecialchars($ghe['nome']); ?></td>
                  <td class="small text-muted"><?php echo htmlspecialchars($ghe['descricao'] ?? '—'); ?></td>
                  <td class="small"><?php echo htmlspecialchars(implode(', ', array_column($ghe['setores'] ?? [], 'nome')) ?: '—'); ?></td>
                  <td>
                    <?php if ($podeGerirGhe): ?>
                      <button class="btn btn-sm btn-outline-primary" onclick='abrirModalGhe(<?php echo json_encode($ghe, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
                    <?php endif; ?>
                    <?php if (Auth::hasPermission('ghe.excluir')): ?>
                      <button class="btn btn-sm btn-outline-danger" onclick="excluirGhe(<?php echo (int)$ghe['id']; ?>)"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-setor">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Setores</h6>
          <?php if ($podeGerirSetor): ?>
            <button class="btn btn-primary btn-sm" onclick="abrirModalSetor()"><i class="bi bi-plus-circle me-1"></i>Novo Setor</button>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Nome</th><th>GHE</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
              <?php if (empty($setores)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum setor cadastrado.</td></tr>
              <?php else: foreach ($setores as $setor): ?>
                <tr>
                  <td><?php echo htmlspecialchars($setor['nome']); ?></td>
                  <td class="small text-muted"><?php echo htmlspecialchars($setor['ghe']['nome'] ?? '—'); ?></td>
                  <td><span class="badge <?php echo $setor['ativo'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $setor['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                  <td>
                    <?php if ($podeGerirSetor): ?>
                      <button class="btn btn-sm btn-outline-primary" onclick='abrirModalSetor(<?php echo json_encode($setor, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
                      <button class="btn btn-sm btn-outline-secondary" onclick='abrirModalUsuario(<?php echo (int)$setor['id']; ?>, "<?php echo htmlspecialchars($setor['nome'], ENT_QUOTES); ?>")' title="Atribuir colaborador"><i class="bi bi-person-plus"></i></button>
                    <?php endif; ?>
                    <?php if (Auth::hasPermission('setor.excluir')): ?>
                      <button class="btn btn-sm btn-outline-danger" onclick="excluirSetor(<?php echo (int)$setor['id']; ?>)"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Modal GHE -->
<div class="modal fade" id="modalGhe" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">GHE</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="ghe-id">
        <div class="mb-3"><label class="form-label">Nome</label><input type="text" id="ghe-nome" class="form-control" placeholder="Ex.: GHE 01 – Comercial e Relacionamento"></div>
        <div class="mb-3"><label class="form-label">Descrição</label><textarea id="ghe-descricao" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarGhe()">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Setor -->
<div class="modal fade" id="modalSetor" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Setor</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="setor-id">
        <div class="mb-3"><label class="form-label">Nome</label><input type="text" id="setor-nome" class="form-control" placeholder="Ex.: Comercial"></div>
        <div class="mb-3">
          <label class="form-label">GHE</label>
          <select id="setor-ghe" class="form-select">
            <option value="">— Nenhum —</option>
            <?php foreach ($ghes as $ghe): ?>
              <option value="<?php echo (int)$ghe['id']; ?>"><?php echo htmlspecialchars($ghe['nome']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="setor-ativo" checked>
          <label class="form-check-label" for="setor-ativo">Ativo</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarSetor()">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Atribuir usuário -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Atribuir colaborador a <span id="usuario-setor-nome"></span></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="usuario-setor-id">
        <label class="form-label">Colaborador</label>
        <select id="usuario-user-id" class="form-select">
          <option value="">Selecione...</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['nome']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarUsuarioSetor()">Atribuir</button>
      </div>
    </div>
  </div>
</div>

<script>
function abrirModalGhe(ghe) {
  document.getElementById('ghe-id').value = ghe ? ghe.id : '';
  document.getElementById('ghe-nome').value = ghe ? ghe.nome : '';
  document.getElementById('ghe-descricao').value = ghe ? (ghe.descricao || '') : '';
  new bootstrap.Modal(document.getElementById('modalGhe')).show();
}

async function salvarGhe() {
  const id = document.getElementById('ghe-id').value;
  const body = { nome: document.getElementById('ghe-nome').value, descricao: document.getElementById('ghe-descricao').value };
  const res = id
    ? await apiFetch('PUT', 'pesquisa-psicossocial/ghes/' + id, body)
    : await apiFetch('POST', 'pesquisa-psicossocial/ghes', body);
  if (res.sucesso) { bslToast('GHE salvo.', 'success'); setTimeout(() => location.reload(), 600); }
  else { bslToast(res.mensagem || 'Erro ao salvar GHE.', 'danger'); }
}

async function excluirGhe(id) {
  if (!confirm('Excluir este GHE? Os setores vinculados ficarão sem GHE.')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/ghes/' + id);
  if (res.sucesso) { bslToast('GHE removido.', 'success'); setTimeout(() => location.reload(), 600); }
  else { bslToast(res.mensagem || 'Erro ao remover GHE.', 'danger'); }
}

function abrirModalSetor(setor) {
  document.getElementById('setor-id').value = setor ? setor.id : '';
  document.getElementById('setor-nome').value = setor ? setor.nome : '';
  document.getElementById('setor-ghe').value = setor && setor.ghe_id ? setor.ghe_id : '';
  document.getElementById('setor-ativo').checked = setor ? !!setor.ativo : true;
  new bootstrap.Modal(document.getElementById('modalSetor')).show();
}

async function salvarSetor() {
  const id = document.getElementById('setor-id').value;
  const body = {
    nome: document.getElementById('setor-nome').value,
    ghe_id: document.getElementById('setor-ghe').value || null,
    ativo: document.getElementById('setor-ativo').checked,
  };
  const res = id
    ? await apiFetch('PUT', 'pesquisa-psicossocial/setores/' + id, body)
    : await apiFetch('POST', 'pesquisa-psicossocial/setores', body);
  if (res.sucesso) { bslToast('Setor salvo.', 'success'); setTimeout(() => location.reload(), 600); }
  else { bslToast(res.mensagem || 'Erro ao salvar setor.', 'danger'); }
}

async function excluirSetor(id) {
  if (!confirm('Excluir este setor?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/setores/' + id);
  if (res.sucesso) { bslToast('Setor removido.', 'success'); setTimeout(() => location.reload(), 600); }
  else { bslToast(res.mensagem || 'Erro ao remover setor.', 'danger'); }
}

function abrirModalUsuario(setorId, setorNome) {
  document.getElementById('usuario-setor-id').value = setorId;
  document.getElementById('usuario-setor-nome').textContent = setorNome;
  document.getElementById('usuario-user-id').value = '';
  new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}

async function salvarUsuarioSetor() {
  const setorId = document.getElementById('usuario-setor-id').value;
  const userId = document.getElementById('usuario-user-id').value;
  if (!userId) { bslToast('Selecione um colaborador.', 'warning'); return; }
  const res = await apiFetch('POST', 'pesquisa-psicossocial/setores/' + setorId + '/usuario', { user_id: userId });
  if (res.sucesso) { bslToast('Colaborador atribuído ao setor.', 'success'); setTimeout(() => location.reload(), 600); }
  else { bslToast(res.mensagem || 'Erro ao atribuir colaborador.', 'danger'); }
}
</script>
