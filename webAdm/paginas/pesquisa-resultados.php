<?php
if (!Auth::hasPermission('resultado.consultar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para consultar resultados.</div>';
    return;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ?paginas=pesquisas');
    exit;
}

$api = new ApiClient(Auth::getToken());
$resp = $api->get("pesquisa-psicossocial/pesquisas/{$id}/resultados");

if (!$resp['success']) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($resp['data']['mensagem'] ?? 'Não foi possível carregar os resultados.') . ' <a href="?paginas=pesquisas">Voltar</a></div>';
    return;
}

$dados = $resp['data']['dados'];
$taxa = $dados['taxa_resposta'];
$acessosGlobais = $dados['acessos_globais'] ?? ['total_sessoes' => 0, 'respondidas' => 0];
$categorias = $dados['categorias'];
$resumoRisco = $dados['resumo_risco'] ?? [];
$matrizRisco = $dados['matriz_risco'] ?? null;
$minimoRespondentes = $dados['pesquisa']['minimo_respondentes'] ?? 5;

$celulasMatrizPorChave = [];
if ($matrizRisco) {
    foreach ($matrizRisco['celulas'] as $celula) {
        $celulasMatrizPorChave["{$celula['probabilidade']}-{$celula['severidade']}"] = $celula;
    }
}

$tipoLabels = [
    'escala' => 'Escala', 'texto' => 'Texto', 'numero' => 'Número', 'data' => 'Data',
    'sim_nao' => 'Sim/Não', 'multipla_escolha' => 'Múltipla Escolha', 'unica_escolha' => 'Única Escolha',
];
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Resultados — <?php echo htmlspecialchars($dados['pesquisa']['nome'] ?? ('Campanha #' . $id)); ?></h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item"><a href="?paginas=pesquisas">Campanhas</a></li>
        <li class="breadcrumb-item active">Resultados</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if (Auth::hasPermission('plano_acao.gerar')): ?>
      <a href="?paginas=pesquisa-plano-acao&id=<?php echo $id; ?>" class="btn btn-outline-dark btn-sm"><i class="bi bi-list-check me-1"></i>Plano de Ação</a>
    <?php endif; ?>
    <?php if (Auth::hasPermission('relatorio.gerar') || Auth::hasPermission('relatorio.listar')): ?>
      <a href="?paginas=pesquisa-relatorios&id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Relatório Técnico</a>
    <?php endif; ?>
    <a href="?paginas=pesquisas" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<section class="section">
  <?php if (!empty($resumoRisco)): ?>
    <?php $farolPorNivel = ['nao_significativo' => '⚪', 'trivial' => '🔵', 'toleravel' => '🟢', 'moderado' => '🟡', 'substancial' => '🟠', 'intoleravel' => '🔴'];
          $labelPorNivel = ['nao_significativo' => 'Não significativo', 'trivial' => 'Trivial', 'toleravel' => 'Tolerável', 'moderado' => 'Moderado', 'substancial' => 'Substancial', 'intoleravel' => 'Intolerável']; ?>
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3">Resumo de classificação de risco por categoria</h6>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach ($resumoRisco as $nivel => $qtd): ?>
            <div class="border rounded px-3 py-2 text-center">
              <div style="font-size:1.4rem;"><?php echo $farolPorNivel[$nivel] ?? '•'; ?></div>
              <div class="small text-muted"><?php echo $labelPorNivel[$nivel] ?? $nivel; ?></div>
              <div class="fw-bold"><?php echo (int)$qtd; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="text-muted small mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i>Grupos com menos de <?php echo (int)$minimoRespondentes; ?> respondente(s) são combinados em um "Grupo agregado" para preservar o anonimato.</p>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($matrizRisco && !empty($matrizRisco['celulas'])): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-grid-3x3-gap-fill me-1"></i>Matriz de Risco (Probabilidade × Severidade)</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered text-center mb-2" style="max-width:640px;">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">P \ S</th>
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <th>S<?php echo $s; ?></th>
                <?php endfor; ?>
              </tr>
            </thead>
            <tbody>
              <?php for ($p = 5; $p >= 1; $p--): ?>
                <tr>
                  <th class="table-light">P<?php echo $p; ?></th>
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <?php $celula = $celulasMatrizPorChave["{$p}-{$s}"] ?? null; ?>
                    <td style="background-color: <?php echo htmlspecialchars($celula['farol_cor'] ?? '#eee'); ?>; color:#fff; font-weight:600;"
                        title="<?php echo htmlspecialchars($celula['nivel_label'] ?? ''); ?> (P<?php echo $p; ?> × S<?php echo $s; ?>)">
                      <?php echo ($celula && $celula['quantidade'] > 0) ? (int)$celula['quantidade'] : '·'; ?>
                    </td>
                  <?php endfor; ?>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>
        <p class="text-muted small mb-0">
          Cada célula mostra quantas avaliações de risco (Categoria × GHE) caíram naquela combinação de Probabilidade × Severidade —
          a cor é a classificação que o motor de cálculo desta campanha atribui à célula.
          <?php if (($matrizRisco['nao_significativo'] ?? 0) > 0): ?>
            <?php echo (int)$matrizRisco['nao_significativo']; ?> avaliação(ões) ficaram abaixo do limite de materialidade (sem exposição significativa) e não aparecem na grade.
          <?php endif; ?>
        </p>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <canvas id="chartTaxaResposta" height="160"></canvas>
          <p class="mt-2 mb-0"><strong><?php echo $taxa['percentual']; ?>%</strong> de taxa de resposta
            (<?php echo $taxa['total_respondidos']; ?> de <?php echo $taxa['total_convites']; ?> convites individuais)</p>
          <?php if ($acessosGlobais['total_sessoes'] > 0): ?>
            <p class="text-muted small mb-0 mt-1">
              <i class="bi bi-link-45deg me-1"></i>+ <?php echo $acessosGlobais['respondidas']; ?> de <?php echo $acessosGlobais['total_sessoes']; ?> via link global
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="card-title">Média por categoria (escala 1-5)</h6>
          <canvas id="chartMediaCategoria" height="140"></canvas>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($categorias)): ?>
    <div class="alert alert-light border text-center py-4">Nenhuma pergunta neste formulário.</div>
  <?php endif; ?>

  <?php foreach ($categorias as $categoria): ?>
    <?php $risco = $categoria['risco'] ?? null; ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><?php echo htmlspecialchars($categoria['nome']); ?></h6>
        <div class="d-flex align-items-center gap-2">
          <?php if ($categoria['media'] !== null): ?>
            <span class="badge bg-primary">Média: <?php echo $categoria['media']; ?></span>
          <?php endif; ?>
          <?php if ($risco): ?>
            <span class="badge" style="background-color: <?php echo htmlspecialchars($risco['farol_cor']); ?>; color:#1a1a1a;">
              <?php echo $risco['farol']; ?> <?php echo htmlspecialchars($risco['nivel_label']); ?>
            </span>
            <span class="badge bg-light text-dark border" title="Probabilidade × Severidade">P<?php echo $risco['probabilidade'] ?? '—'; ?> × S<?php echo $risco['severidade']; ?></span>
          <?php elseif (!empty($categoria['categoria_referencia'])): ?>
            <span class="badge bg-light text-dark border">Sem exposição significativa</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!empty($categoria['grupos_ghe'])): ?>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr><th>GHE</th><th class="text-center">Respostas</th><th class="text-center">Média</th><th class="text-center">P × S</th><th>Nível</th><th class="text-center">Farol</th></tr>
            </thead>
            <tbody>
              <?php foreach ($categoria['grupos_ghe'] as $grupo): ?>
                <tr>
                  <td><?php echo htmlspecialchars($grupo['nome']); ?></td>
                  <td class="text-center"><?php echo (int)$grupo['total_respostas']; ?></td>
                  <td class="text-center"><?php echo $grupo['media'] ?? '—'; ?></td>
                  <td class="text-center"><?php echo $grupo['risco'] ? ('P'.($grupo['risco']['probabilidade'] ?? '—').' × S'.$grupo['risco']['severidade']) : '—'; ?></td>
                  <td><?php echo $grupo['risco']['nivel_label'] ?? '—'; ?></td>
                  <td class="text-center"><?php echo $grupo['risco']['farol'] ?? '—'; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ($categoria['perguntas'] as $pergunta): ?>
            <div class="col-md-6">
              <div class="border rounded p-3 h-100">
                <p class="fw-semibold small mb-1"><?php echo htmlspecialchars($pergunta['texto']); ?></p>
                <p class="text-muted small mb-2">
                  <?php echo $tipoLabels[$pergunta['tipo']] ?? $pergunta['tipo']; ?> ·
                  <?php echo (int)$pergunta['total_respostas']; ?> resposta(s)
                  <?php if ($pergunta['media'] !== null): ?> · média <?php echo $pergunta['media']; ?><?php endif; ?>
                </p>

                <?php if (isset($pergunta['distribuicao']) && count($pergunta['distribuicao'])): ?>
                  <canvas id="chart-pergunta-<?php echo (int)$pergunta['id']; ?>" height="140"
                          data-labels='<?php echo htmlspecialchars(json_encode(array_column($pergunta['distribuicao'], 'descricao')), ENT_QUOTES); ?>'
                          data-valores='<?php echo htmlspecialchars(json_encode(array_column($pergunta['distribuicao'], 'quantidade')), ENT_QUOTES); ?>'
                          data-cores='<?php echo htmlspecialchars(json_encode(array_column($pergunta['distribuicao'], 'cor')), ENT_QUOTES); ?>'
                          class="grafico-pergunta"></canvas>
                <?php elseif (isset($pergunta['respostas_texto'])): ?>
                  <?php if (empty($pergunta['respostas_texto'])): ?>
                    <p class="text-muted small mb-0">Nenhuma resposta ainda.</p>
                  <?php else: ?>
                    <ul class="list-group list-group-flush small" style="max-height:180px;overflow:auto">
                      <?php foreach ($pergunta['respostas_texto'] as $texto): ?>
                        <li class="list-group-item px-0"><?php echo htmlspecialchars($texto); ?></li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  new Chart(document.getElementById('chartTaxaResposta'), {
    type: 'doughnut',
    data: {
      labels: ['Respondido', 'Pendente'],
      datasets: [{
        data: [<?php echo $taxa['total_respondidos']; ?>, <?php echo max(0, $taxa['total_convites'] - $taxa['total_respondidos']); ?>],
        backgroundColor: ['#22c55e', '#e5e7eb'],
      }],
    },
    options: { plugins: { legend: { position: 'bottom' } } },
  });

  new Chart(document.getElementById('chartMediaCategoria'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_column($categorias, 'nome')); ?>,
      datasets: [{
        label: 'Média',
        data: <?php echo json_encode(array_map(fn ($c) => $c['media'] ?? 0, $categorias)); ?>,
        backgroundColor: '#0d6efd',
      }],
    },
    options: { scales: { y: { beginAtZero: true, max: 5 } }, plugins: { legend: { display: false } } },
  });

  document.querySelectorAll('.grafico-pergunta').forEach(function (canvas) {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const valores = JSON.parse(canvas.dataset.valores || '[]');
    const cores = JSON.parse(canvas.dataset.cores || '[]').map(c => c || '#6c757d');

    new Chart(canvas, {
      type: 'pie',
      data: { labels, datasets: [{ data: valores, backgroundColor: cores }] },
      options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } },
    });
  });
});
</script>
