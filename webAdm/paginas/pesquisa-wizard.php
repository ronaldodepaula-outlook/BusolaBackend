<?php
if (!Auth::hasPermission('pesquisa.criar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para criar campanhas.</div>';
    return;
}

$isSuperAdmin = Auth::isSuperAdmin();
$id   = (int)($_GET['id'] ?? 0);
$step = max(1, min(4, (int)($_GET['step'] ?? 1)));
if (!$id) {
    $step = 1;
}

$api = new ApiClient(Auth::getToken());
$pesquisa = null;

if ($id) {
    $resp = $api->get("pesquisa-psicossocial/pesquisas/{$id}");
    if (!$resp['success']) {
        echo '<div class="alert alert-danger">Campanha não encontrada. <a href="?paginas=pesquisa-wizard">Recomeçar</a></div>';
        return;
    }
    $pesquisa = $resp['data']['dados'];
    if ($pesquisa['status'] !== 'rascunho') {
        echo '<div class="alert alert-info">Esta campanha já foi publicada. <a href="?paginas=pesquisas">Ver campanhas</a></div>';
        return;
    }
}

$empresaContexto = $pesquisa['empresa_id'] ?? null;

// ---- Dados para o Step 1 (lista de formulários elegíveis) ----
$formularios = [];
if ($step === 1) {
    $respForm    = $api->get('pesquisa-psicossocial/formularios', ['status' => 'publicado']);
    $formularios = $respForm['data']['dados']['data'] ?? [];

    $empresas = [];
    if ($isSuperAdmin) {
        $respEmp  = $api->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
        $empresas = $respEmp['data']['dados']['data'] ?? [];
    }
}

// ---- Dados para o Step 3 (filiais/colaboradores) ----
$filiais = [];
$colaboradores = [];
if ($step === 3 && $empresaContexto) {
    $apiEmp   = new ApiClient(Auth::getToken(), $empresaContexto);
    $respFil  = $apiEmp->get('filiais', ['per_page' => 100]);
    $filiais  = $respFil['data']['dados']['data'] ?? [];
    $respCol  = $apiEmp->get('pesquisa-psicossocial/colaboradores', ['per_page' => 200, 'ativo' => 1]);
    $colaboradores = $respCol['data']['dados']['data'] ?? [];
}

$publicoAtual = $pesquisa['publico'] ?? ['tipo' => 'toda_empresa', 'filial_ids' => [], 'colaborador_ids' => []];

function stepUrl(int $n, ?int $id): string
{
    return $id ? "?paginas=pesquisa-wizard&id={$id}&step={$n}" : '?paginas=pesquisa-wizard';
}
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Nova Pesquisa</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item"><a href="?paginas=pesquisas">Campanhas</a></li>
        <li class="breadcrumb-item active">Assistente</li>
      </ol>
    </nav>
  </div>
  <a href="?paginas=pesquisas" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Sair do assistente</a>
</div>

<section class="section">
  <ul class="nav nav-pills justify-content-center gap-2 mb-4">
    <?php
    $etapas = [1 => 'Formulário', 2 => 'Dados da Campanha', 3 => 'Público-alvo', 4 => 'Revisão e Publicação'];
    foreach ($etapas as $n => $label):
        $ativo = $n === $step;
        $habilitado = $id || $n === 1;
    ?>
      <li class="nav-item">
        <?php if ($habilitado): ?>
          <a class="nav-link <?php echo $ativo ? 'active' : 'bg-light text-dark'; ?>" href="<?php echo stepUrl($n, $id); ?>">
            <?php echo $n; ?>. <?php echo $label; ?>
          </a>
        <?php else: ?>
          <span class="nav-link disabled bg-light text-muted"><?php echo $n; ?>. <?php echo $label; ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="card">
    <div class="card-body">

      <?php if ($step === 1): ?>
        <h5 class="mb-3">Passo 1 — Escolha o formulário</h5>

        <?php if ($isSuperAdmin): ?>
          <div class="mb-3">
            <label class="form-label">Empresa da campanha <span class="text-danger">*</span></label>
            <select id="wizardEmpresaId" class="form-select" style="max-width:320px">
              <option value="">Selecione...</option>
              <?php foreach ($empresas as $emp): ?>
                <option value="<?php echo (int)$emp['id']; ?>"><?php echo htmlspecialchars($emp['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if (empty($formularios)): ?>
          <div class="alert alert-light border">Nenhum formulário publicado disponível. Publique um formulário antes de criar uma campanha.</div>
        <?php endif; ?>

        <div class="row g-3">
          <?php foreach ($formularios as $f): ?>
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 formulario-card" style="cursor:pointer" onclick="escolherFormulario(<?php echo (int)$f['id']; ?>, this)">
                <div class="card-body">
                  <h6 class="card-title"><?php echo htmlspecialchars($f['nome']); ?></h6>
                  <p class="text-muted small mb-1"><code><?php echo htmlspecialchars($f['codigo']); ?></code></p>
                  <?php if ($f['tipo'] === 'global'): ?><span class="badge bg-info text-dark">Global</span><?php endif; ?>
                  <span class="badge bg-secondary"><?php echo (int)($f['total_categorias'] ?? 0); ?> categorias</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif ($step === 2): ?>
        <h5 class="mb-3">Passo 2 — Dados da campanha</h5>
        <form id="formDados">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nome da campanha <span class="text-danger">*</span></label>
              <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($pesquisa['nome'] ?? ''); ?>" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="anonima" id="wizAnonima" <?php echo ($pesquisa['anonima'] ?? true) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="wizAnonima">Respostas anônimas</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Descrição</label>
              <textarea name="descricao" class="form-control" rows="2"><?php echo htmlspecialchars($pesquisa['descricao'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Data de início</label>
              <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($pesquisa['data_inicio'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Data de término</label>
              <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($pesquisa['data_fim'] ?? ''); ?>">
            </div>
          </div>
        </form>
        <div class="d-flex justify-content-between mt-4">
          <a href="?paginas=pesquisa-wizard" class="btn btn-outline-secondary">Voltar</a>
          <button class="btn btn-primary" onclick="salvarDados(this)">Avançar <i class="bi bi-arrow-right ms-1"></i></button>
        </div>

      <?php elseif ($step === 3): ?>
        <h5 class="mb-3">Passo 3 — Público-alvo</h5>
        <?php $tipoAtual = $publicoAtual['tipo'] ?? 'toda_empresa'; ?>
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoPublico" id="tipoTodaEmpresa" value="toda_empresa" <?php echo $tipoAtual === 'toda_empresa' ? 'checked' : ''; ?> onchange="toggleTipoPublico()">
            <label class="form-check-label" for="tipoTodaEmpresa"><strong>Toda a empresa</strong> — todos os colaboradores poderão responder</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoPublico" id="tipoFiliais" value="filiais" <?php echo $tipoAtual === 'filiais' ? 'checked' : ''; ?> onchange="toggleTipoPublico()">
            <label class="form-check-label" for="tipoFiliais"><strong>Filiais específicas</strong></label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoPublico" id="tipoColaboradores" value="colaboradores" <?php echo $tipoAtual === 'colaboradores' ? 'checked' : ''; ?> onchange="toggleTipoPublico()">
            <label class="form-check-label" for="tipoColaboradores"><strong>Colaboradores específicos</strong></label>
          </div>
        </div>

        <div id="blocoFiliais" class="border rounded p-3 mb-3" style="display:none">
          <div class="row g-2">
            <?php foreach ($filiais as $fil): ?>
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input publico-filial" type="checkbox" value="<?php echo (int)$fil['id']; ?>"
                         <?php echo in_array($fil['id'], $publicoAtual['filial_ids'] ?? [], true) ? 'checked' : ''; ?>>
                  <label class="form-check-label"><?php echo htmlspecialchars($fil['nome']); ?></label>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($filiais)): ?><p class="text-muted small mb-0">Nenhuma filial cadastrada.</p><?php endif; ?>
          </div>
        </div>

        <div id="blocoColaboradores" class="border rounded p-3 mb-3" style="display:none;max-height:300px;overflow:auto">
          <div class="row g-2">
            <?php foreach ($colaboradores as $col): ?>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input publico-colaborador" type="checkbox" value="<?php echo (int)$col['id']; ?>"
                         <?php echo in_array($col['id'], $publicoAtual['colaborador_ids'] ?? [], true) ? 'checked' : ''; ?>>
                  <label class="form-check-label"><?php echo htmlspecialchars($col['nome']); ?> <span class="text-muted small">(<?php echo htmlspecialchars($col['email'] ?? 'sem e-mail'); ?>)</span></label>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($colaboradores)): ?><p class="text-muted small mb-0">Nenhum colaborador cadastrado. <a href="?paginas=colaboradores">Cadastre colaboradores</a> antes de restringir o público-alvo.</p><?php endif; ?>
          </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?php echo stepUrl(2, $id); ?>" class="btn btn-outline-secondary">Voltar</a>
          <button class="btn btn-primary" onclick="salvarPublico(this)">Avançar <i class="bi bi-arrow-right ms-1"></i></button>
        </div>

      <?php elseif ($step === 4): ?>
        <h5 class="mb-3">Passo 4 — Revisão</h5>
        <table class="table table-borderless">
          <tr><th style="width:200px">Formulário</th><td><?php echo htmlspecialchars($pesquisa['formulario']['nome'] ?? '—'); ?></td></tr>
          <tr><th>Nome da campanha</th><td><?php echo htmlspecialchars($pesquisa['nome'] ?? '—'); ?></td></tr>
          <tr><th>Descrição</th><td><?php echo htmlspecialchars($pesquisa['descricao'] ?? '—'); ?></td></tr>
          <tr><th>Período</th><td><?php echo htmlspecialchars($pesquisa['data_inicio'] ?? '—'); ?> até <?php echo htmlspecialchars($pesquisa['data_fim'] ?? '—'); ?></td></tr>
          <tr><th>Anônima</th><td><?php echo !empty($pesquisa['anonima']) ? 'Sim' : 'Não'; ?></td></tr>
          <tr>
            <th>Público-alvo</th>
            <td>
              <?php if (($publicoAtual['tipo'] ?? '') === 'filiais'): ?>
                Filiais específicas (<?php echo count($publicoAtual['filial_ids'] ?? []); ?> selecionada(s))
              <?php elseif (($publicoAtual['tipo'] ?? '') === 'colaboradores'): ?>
                Colaboradores específicos (<?php echo count($publicoAtual['colaborador_ids'] ?? []); ?> selecionado(s))
              <?php else: ?>
                Toda a empresa
              <?php endif; ?>
            </td>
          </tr>
        </table>
        <div class="alert alert-light border">
          <i class="bi bi-info-circle me-1"></i> Ao publicar, a campanha fica <strong>ativa</strong> imediatamente. Você poderá encerrá-la a qualquer momento na lista de campanhas.
        </div>
        <div class="d-flex justify-content-between mt-4">
          <a href="<?php echo stepUrl(3, $id); ?>" class="btn btn-outline-secondary">Voltar</a>
          <div class="d-flex gap-2">
            <a href="?paginas=pesquisas" class="btn btn-outline-secondary">Salvar como rascunho e sair</a>
            <button class="btn btn-success" onclick="publicarCampanha(this)"><i class="bi bi-send-check me-1"></i> Publicar Campanha</button>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<script>
const WIZARD_ID = <?php echo $id ? (int)$id : 'null'; ?>;

async function escolherFormulario(formularioId, cardEl) {
  <?php if ($isSuperAdmin): ?>
  const empresaId = document.getElementById('wizardEmpresaId').value;
  if (!empresaId) { bslToast('Selecione a empresa da campanha primeiro.', 'warning'); return; }
  <?php else: ?>
  const empresaId = null;
  <?php endif; ?>

  document.querySelectorAll('.formulario-card').forEach(el => el.classList.remove('border-primary'));
  cardEl.classList.add('border-primary');

  const payload = { formulario_id: formularioId };
  if (empresaId) payload.empresa_id = empresaId;

  const res = await apiFetch('POST', 'pesquisa-psicossocial/pesquisas', payload);
  if (res.sucesso) {
    window.location.href = '?paginas=pesquisa-wizard&id=' + res.dados.id + '&step=2';
  } else {
    bslToast(res.mensagem || 'Erro ao iniciar campanha.', 'danger');
  }
}

async function salvarDados(btn) {
  bslSetLoading(btn, true);
  const data = bslFormData('formDados');
  data.anonima = document.getElementById('wizAnonima').checked;
  const res = await apiFetch('PUT', 'pesquisa-psicossocial/pesquisas/' + WIZARD_ID, data);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    window.location.href = '?paginas=pesquisa-wizard&id=' + WIZARD_ID + '&step=3';
  } else {
    bslToast(res.mensagem || 'Erro ao salvar dados da campanha.', 'danger');
  }
}

function toggleTipoPublico() {
  const tipo = document.querySelector('input[name="tipoPublico"]:checked')?.value;
  document.getElementById('blocoFiliais').style.display = tipo === 'filiais' ? '' : 'none';
  document.getElementById('blocoColaboradores').style.display = tipo === 'colaboradores' ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleTipoPublico);

async function salvarPublico(btn) {
  bslSetLoading(btn, true);
  const tipo = document.querySelector('input[name="tipoPublico"]:checked')?.value || 'toda_empresa';
  let ids = [];
  if (tipo === 'filiais') {
    ids = Array.from(document.querySelectorAll('.publico-filial:checked')).map(el => parseInt(el.value));
  } else if (tipo === 'colaboradores') {
    ids = Array.from(document.querySelectorAll('.publico-colaborador:checked')).map(el => parseInt(el.value));
  }
  const res = await apiFetch('POST', 'pesquisa-psicossocial/pesquisas/' + WIZARD_ID + '/publico', { tipo, ids });
  bslSetLoading(btn, false);
  if (res.sucesso) {
    window.location.href = '?paginas=pesquisa-wizard&id=' + WIZARD_ID + '&step=4';
  } else {
    bslToast(res.mensagem || 'Erro ao salvar público-alvo.', 'danger');
  }
}

async function publicarCampanha(btn) {
  bslSetLoading(btn, true);
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/pesquisas/' + WIZARD_ID + '/publicar');
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Campanha publicada com sucesso!', 'success');
    setTimeout(() => { window.location.href = '?paginas=pesquisas'; }, 1000);
  } else {
    bslToast(res.mensagem || 'Erro ao publicar campanha.', 'danger');
  }
}
</script>
