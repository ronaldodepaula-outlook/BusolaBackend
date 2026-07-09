<?php
if (!Auth::hasPermission('resultado.consultar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ?paginas=pesquisas'); exit; }

$api = new ApiClient(Auth::getToken());
$pesquisaResp = $api->get("pesquisa-psicossocial/pesquisas/{$id}");
$pesquisa = $pesquisaResp['data']['dados'] ?? null;

$resp = $api->get("pesquisa-psicossocial/pesquisas/{$id}/plano-acao");
$acoes = $resp['data']['dados'] ?? [];

$podeGerar = Auth::hasPermission('plano_acao.gerar');
$podeEditar = Auth::hasPermission('plano_acao.editar');

$fases = [
    'planejar'  => ['Planejar', 'bi-clipboard-check', '#6c757d'],
    'executar'  => ['Executar', 'bi-play-circle', '#0d6efd'],
    'verificar' => ['Verificar', 'bi-search', '#fd7e14'],
    'agir'      => ['Agir', 'bi-flag', '#198754'],
];

$porFase = ['planejar' => [], 'executar' => [], 'verificar' => [], 'agir' => []];
foreach ($acoes as $acao) {
    $porFase[$acao['fase_pdca']['value']][] = $acao;
}
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Plano de Ação PDCA — <?php echo htmlspecialchars($pesquisa['nome'] ?? ('Campanha #' . $id)); ?></h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item"><a href="?paginas=pesquisas">Campanhas</a></li>
        <li class="breadcrumb-item"><a href="?paginas=pesquisa-resultados&id=<?php echo $id; ?>">Resultados</a></li>
        <li class="breadcrumb-item active">Plano de Ação</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex gap-2">
    <?php if ($podeGerar): ?>
      <button class="btn btn-primary btn-sm" onclick="gerarPlano()"><i class="bi bi-arrow-repeat me-1"></i>Gerar/Atualizar a partir do resultado</button>
    <?php endif; ?>
    <a href="?paginas=pesquisa-resultados&id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<section class="section">
  <div class="alert alert-light border small">
    <i class="bi bi-arrow-repeat me-1"></i>
    Cada ação percorre o ciclo <strong>Planejar → Executar → Verificar → Agir</strong> (Seção 3.1 da metodologia).
    Uma ação só avança de coluna com a evidência ou o parecer da etapa anterior registrado. Se, ao "Agir", a ação
    não for considerada eficaz, um novo ciclo é aberto automaticamente — a ação volta para "Planejar" com o
    contador de ciclo incrementado.
  </div>

  <?php if (empty($acoes)): ?>
    <div class="alert alert-light border text-center py-4">
      Nenhuma ação gerada ainda. Categorias vinculadas a um fator de risco oficial com classificação Tolerável ou pior
      geram ações automaticamente ao clicar em "Gerar/Atualizar".
    </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($fases as $faseValue => [$faseLabel, $icone, $cor]): ?>
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center justify-content-between" style="border-top: 3px solid <?php echo $cor; ?>;">
            <span><i class="bi <?php echo $icone; ?> me-1"></i><?php echo $faseLabel; ?></span>
            <span class="badge bg-secondary"><?php echo count($porFase[$faseValue]); ?></span>
          </div>
          <div class="card-body p-2" style="min-height: 120px;">
            <?php foreach ($porFase[$faseValue] as $acao): ?>
              <div class="card mb-2 shadow-sm">
                <div class="card-body p-2">
                  <div class="d-flex justify-content-between align-items-start">
                    <span class="fw-semibold small"><?php echo htmlspecialchars($acao['categoria']['nome'] ?? '—'); ?></span>
                    <?php if ($acao['ciclo_pdca'] > 1): ?>
                      <span class="badge bg-warning text-dark" title="Ciclo PDCA">C<?php echo (int)$acao['ciclo_pdca']; ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="small text-muted mb-1">
                    <?php echo htmlspecialchars($acao['ghe']['nome'] ?? 'Grupo agregado'); ?> ·
                    <?php echo htmlspecialchars($acao['tipo_controle']['label']); ?>
                  </div>
                  <p class="small mb-1" style="max-height:54px;overflow:hidden;"><?php echo htmlspecialchars($acao['acao']); ?></p>
                  <div class="small text-muted mb-2">
                    <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($acao['responsavel'] ?? '—'); ?>
                    <br><i class="bi bi-calendar-event me-1"></i><?php echo htmlspecialchars($acao['prazo'] ?? '—'); ?>
                  </div>

                  <?php if ($podeEditar): ?>
                    <div class="d-flex gap-1 flex-wrap">
                      <?php if ($faseValue === 'planejar'): ?>
                        <button class="btn btn-sm btn-outline-primary w-100" onclick="avancarFase(<?php echo (int)$acao['id']; ?>, 'executar')">
                          <i class="bi bi-play-fill me-1"></i>Iniciar execução
                        </button>
                      <?php elseif ($faseValue === 'executar'): ?>
                        <button class="btn btn-sm btn-outline-warning w-100" onclick='abrirModalEvidencia(<?php echo (int)$acao["id"]; ?>)'>
                          <i class="bi bi-clipboard-check me-1"></i>Registrar evidência
                        </button>
                      <?php elseif ($faseValue === 'verificar'): ?>
                        <button class="btn btn-sm btn-outline-info w-100" onclick='abrirModalParecer(<?php echo (int)$acao["id"]; ?>)'>
                          <i class="bi bi-check2-square me-1"></i>Validar
                        </button>
                      <?php elseif ($faseValue === 'agir'): ?>
                        <button class="btn btn-sm btn-outline-success w-100" onclick='abrirModalEficacia(<?php echo (int)$acao["id"]; ?>)'>
                          <i class="bi bi-flag-fill me-1"></i>Concluir ciclo
                        </button>
                      <?php endif; ?>
                      <button class="btn btn-sm btn-outline-secondary" title="Editar responsável/prazo" onclick='abrirModalAcao(<?php echo json_encode($acao, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                        <i class="bi bi-pencil"></i>
                      </button>
                      <?php if (!empty($acao['historico_pdca'])): ?>
                        <button class="btn btn-sm btn-outline-dark" title="Histórico de ciclos" onclick='verHistorico(<?php echo json_encode($acao['historico_pdca'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                          <i class="bi bi-clock-history"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- Modal editar ação (responsável/prazo/status/observações) -->
<div class="modal fade" id="modalAcao" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar ação</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="acao-id">
        <p class="small text-muted" id="acao-texto"></p>
        <div class="mb-3"><label class="form-label">Responsável</label><input type="text" id="acao-responsavel" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Prazo</label><input type="text" id="acao-prazo" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Observações</label><textarea id="acao-observacoes" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarAcao()">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Executar -> Verificar (evidência) -->
<div class="modal fade" id="modalEvidencia" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Registrar evidência de execução</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="evidencia-acao-id">
        <label class="form-label">O que foi feito? (evidência)</label>
        <textarea id="evidencia-texto" class="form-control" rows="4" placeholder="Ex.: escalas revisadas e publicadas em reunião com a equipe em 10/01/2026."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning" onclick="enviarEvidencia()">Avançar para Verificar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Verificar -> Agir (parecer) -->
<div class="modal fade" id="modalParecer" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Validar execução</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="parecer-acao-id">
        <label class="form-label">Parecer de verificação</label>
        <textarea id="parecer-texto" class="form-control" rows="4" placeholder="Ex.: validado com a liderança da área em reunião de acompanhamento."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-info" onclick="enviarParecer()">Avançar para Agir</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Agir -> Concluir ciclo (eficácia) -->
<div class="modal fade" id="modalEficacia" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Concluir ciclo PDCA</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="eficacia-acao-id">
        <label class="form-label">A ação foi eficaz?</label>
        <select id="eficacia-select" class="form-select mb-3">
          <option value="eficaz">Eficaz — encerrar a ação</option>
          <option value="parcialmente_eficaz">Parcialmente eficaz — abrir novo ciclo</option>
          <option value="ineficaz">Ineficaz — abrir novo ciclo</option>
        </select>
        <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Se não for totalmente eficaz, um novo ciclo PDCA é aberto automaticamente para esta ação (volta para "Planejar").</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" onclick="enviarEficacia()">Concluir ciclo</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: histórico de ciclos -->
<div class="modal fade" id="modalHistorico" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Histórico de ciclos PDCA</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="historico-conteudo"></div>
    </div>
  </div>
</div>

<script>
async function gerarPlano() {
  const res = await apiFetch('POST', 'pesquisa-psicossocial/pesquisas/<?php echo $id; ?>/plano-acao/gerar');
  if (res.sucesso) {
    bslToast((res.dados.geradas || 0) + ' ação(ões) gerada(s), ' + (res.dados.atualizadas || 0) + ' atualizada(s).', 'success');
    setTimeout(() => location.reload(), 900);
  } else {
    bslToast(res.mensagem || 'Erro ao gerar plano de ação.', 'danger');
  }
}

async function avancarFase(id, fase, extra) {
  const body = Object.assign({ fase }, extra || {});
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/plano-acao/' + id + '/avancar-fase', body);
  if (res.sucesso) { bslToast(res.mensagem || 'Ação avançada.', 'success'); setTimeout(() => location.reload(), 700); }
  else { bslToast(res.mensagem || 'Erro ao avançar a ação.', 'danger'); }
}

function abrirModalEvidencia(id) {
  document.getElementById('evidencia-acao-id').value = id;
  document.getElementById('evidencia-texto').value = '';
  new bootstrap.Modal(document.getElementById('modalEvidencia')).show();
}
function enviarEvidencia() {
  const id = document.getElementById('evidencia-acao-id').value;
  const texto = document.getElementById('evidencia-texto').value;
  if (!texto.trim()) { bslToast('Descreva a evidência da execução.', 'warning'); return; }
  bslCloseModal('modalEvidencia');
  avancarFase(id, 'verificar', { evidencia_execucao: texto });
}

function abrirModalParecer(id) {
  document.getElementById('parecer-acao-id').value = id;
  document.getElementById('parecer-texto').value = '';
  new bootstrap.Modal(document.getElementById('modalParecer')).show();
}
function enviarParecer() {
  const id = document.getElementById('parecer-acao-id').value;
  const texto = document.getElementById('parecer-texto').value;
  if (!texto.trim()) { bslToast('Descreva o parecer de verificação.', 'warning'); return; }
  bslCloseModal('modalParecer');
  avancarFase(id, 'agir', { parecer_verificacao: texto });
}

function abrirModalEficacia(id) {
  document.getElementById('eficacia-acao-id').value = id;
  document.getElementById('eficacia-select').value = 'eficaz';
  new bootstrap.Modal(document.getElementById('modalEficacia')).show();
}
async function enviarEficacia() {
  const id = document.getElementById('eficacia-acao-id').value;
  const eficacia = document.getElementById('eficacia-select').value;
  bslCloseModal('modalEficacia');
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/plano-acao/' + id + '/concluir-ciclo', { eficacia });
  if (res.sucesso) { bslToast(res.mensagem || 'Ciclo concluído.', 'success'); setTimeout(() => location.reload(), 900); }
  else { bslToast(res.mensagem || 'Erro ao concluir o ciclo.', 'danger'); }
}

function verHistorico(historico) {
  const container = document.getElementById('historico-conteudo');
  container.innerHTML = historico.map(function (h) {
    return '<div class="border rounded p-2 mb-2">' +
      '<div class="small fw-semibold">Ciclo ' + h.ciclo + ' — eficácia: ' + h.eficacia + '</div>' +
      '<div class="small text-muted">Evidência: ' + (h.evidencia_execucao || '—') + '</div>' +
      '<div class="small text-muted">Parecer: ' + (h.parecer_verificacao || '—') + '</div>' +
      '</div>';
  }).join('') || '<p class="text-muted">Sem ciclos anteriores.</p>';
  new bootstrap.Modal(document.getElementById('modalHistorico')).show();
}

function abrirModalAcao(acao) {
  document.getElementById('acao-id').value = acao.id;
  document.getElementById('acao-texto').textContent = acao.acao;
  document.getElementById('acao-responsavel').value = acao.responsavel || '';
  document.getElementById('acao-prazo').value = acao.prazo || '';
  document.getElementById('acao-observacoes').value = acao.observacoes || '';
  new bootstrap.Modal(document.getElementById('modalAcao')).show();
}

async function salvarAcao() {
  const id = document.getElementById('acao-id').value;
  const body = {
    responsavel: document.getElementById('acao-responsavel').value,
    prazo: document.getElementById('acao-prazo').value,
    observacoes: document.getElementById('acao-observacoes').value,
  };
  const res = await apiFetch('PATCH', 'pesquisa-psicossocial/plano-acao/' + id, body);
  if (res.sucesso) { bslToast('Ação atualizada.', 'success'); setTimeout(() => location.reload(), 600); }
  else { bslToast(res.mensagem || 'Erro ao atualizar ação.', 'danger'); }
}
</script>
