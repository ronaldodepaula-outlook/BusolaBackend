<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'Dashboard';

// KPIs
$receita_mes   = queryOne("SELECT COALESCE(SUM(total),0) AS v FROM pedidos WHERE MONTH(criado_em)=MONTH(NOW()) AND YEAR(criado_em)=YEAR(NOW())")['v'] ?? 0;
$receita_ant   = queryOne("SELECT COALESCE(SUM(total),0) AS v FROM pedidos WHERE MONTH(criado_em)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(criado_em)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))")['v'] ?? 0;
$delta_receita = $receita_ant > 0 ? round((($receita_mes - $receita_ant) / $receita_ant) * 100, 1) : 0;

$total_usuarios  = queryOne("SELECT COUNT(*) AS v FROM usuarios WHERE ativo=1")['v'] ?? 0;
$total_pedidos   = queryOne("SELECT COUNT(*) AS v FROM pedidos WHERE MONTH(criado_em)=MONTH(NOW()) AND YEAR(criado_em)=YEAR(NOW())")['v'] ?? 0;
$total_clientes  = queryOne("SELECT COUNT(*) AS v FROM clientes WHERE status='ativo'")['v'] ?? 0;

// Gráfico mensal
$grafico = query("SELECT mes, receita, pedidos FROM receitas_mensais WHERE ano=2026 ORDER BY mes ASC");
$meses_label = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
$labels   = json_encode(array_map(fn($r) => $meses_label[$r['mes']-1], $grafico));
$receitas = json_encode(array_map(fn($r) => (float)$r['receita'], $grafico));
$pedidos  = json_encode(array_map(fn($r) => (int)$r['pedidos'], $grafico));

// Últimos pedidos
$ultimos_pedidos = query("
    SELECT p.numero, p.status, p.total, p.criado_em, c.nome AS cliente
    FROM pedidos p
    JOIN clientes c ON c.id = p.cliente_id
    ORDER BY p.criado_em DESC LIMIT 8
");

// Atividades recentes
$atividades = query("
    SELECT a.descricao, a.tipo, a.criado_em, u.nome AS usuario
    FROM atividades a
    LEFT JOIN usuarios u ON u.id = a.usuario_id
    ORDER BY a.criado_em DESC LIMIT 6
");

// Metas mensais
$metas = [
    ['label'=>'Receita', 'atual'=>24180, 'meta'=>50000, 'cor'=>'primary'],
    ['label'=>'Novos Clientes', 'atual'=>10, 'meta'=>25, 'cor'=>'success'],
    ['label'=>'Pedidos', 'atual'=>35, 'meta'=>80, 'cor'=>'warning'],
    ['label'=>'Satisfação', 'atual'=>88, 'meta'=>95, 'cor'=>'info'],
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Cabeçalho da Página -->
<div class="page-header page-header-row">
  <div>
    <div class="breadcrumb">
      <span>Início</span>
      <span class="sep">›</span>
      <span class="current">Dashboard</span>
    </div>
    <h1>Visão Geral</h1>
    <p>Resumo das métricas do mês atual — <?= date('F Y') ?></p>
  </div>
  <div class="d-flex gap-8">
    <a href="pages/orders.php" class="btn btn-outline btn-sm">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14l4-4h12c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
      Ver Pedidos
    </a>
    <a href="pages/analytics.php" class="btn btn-primary btn-sm">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6h-6z"/></svg>
      Analytics
    </a>
  </div>
</div>

<!-- KPIs -->
<div class="kpi-grid">

  <div class="kpi-card">
    <div class="kpi-icon primary">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Receita do Mês</div>
      <div class="kpi-value"><?= formatMoeda((float)$receita_mes) ?></div>
      <span class="kpi-delta <?= $delta_receita >= 0 ? 'up' : 'down' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?= $delta_receita >= 0 ? 'M7 14l5-5 5 5z' : 'M7 10l5 5 5-5z' ?>"/></svg>
        <?= abs($delta_receita) ?>% vs mês anterior
      </span>
    </div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon success">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Clientes Ativos</div>
      <div class="kpi-value"><?= number_format($total_clientes) ?></div>
      <span class="kpi-delta up">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 14l5-5 5 5z"/></svg>
        +3 novos este mês
      </span>
    </div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon warning">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Pedidos no Mês</div>
      <div class="kpi-value"><?= $total_pedidos ?></div>
      <span class="kpi-delta down">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
        Meta: 80 pedidos
      </span>
    </div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon info">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Usuários do Sistema</div>
      <div class="kpi-value"><?= $total_usuarios ?></div>
      <span class="kpi-delta up">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 14l5-5 5 5z"/></svg>
        Todos ativos
      </span>
    </div>
  </div>

</div>

<!-- Gráfico + Fontes de Tráfego -->
<div class="grid-21 mb-24">

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Receita & Pedidos Mensais</div>
        <div class="card-subtitle">Desempenho acumulado em 2026</div>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-wrap" style="height:280px">
        <canvas id="revenueChart"
          data-labels='<?= $labels ?>'
          data-revenue='<?= $receitas ?>'
          data-orders='<?= $pedidos ?>'>
        </canvas>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Fontes de Tráfego</div>
        <div class="card-subtitle">Distribuição de acessos</div>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-wrap text-center mb-16" style="height:160px">
        <canvas id="trafficChart"></canvas>
      </div>
      <div>
        <?php
        $fontes = [
          ['Orgânico','#6366f1','38%'],
          ['Direto','#10b981','27%'],
          ['Social','#f59e0b','18%'],
          ['E-mail','#3b82f6','10%'],
          ['Referência','#ef4444','7%'],
        ];
        foreach ($fontes as [$nome, $cor, $pct]): ?>
        <div class="traffic-item">
          <span class="traffic-dot" style="background:<?= $cor ?>"></span>
          <span class="traffic-label"><?= $nome ?></span>
          <span class="traffic-pct"><?= $pct ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- Pedidos Recentes + Metas -->
<div class="grid-21 mb-24">

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Pedidos Recentes</div>
        <div class="card-subtitle">Últimas transações registradas</div>
      </div>
      <a href="pages/orders.php" class="btn btn-outline btn-sm">Ver todos</a>
    </div>
    <div class="card-body" style="padding-top:0">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Número</th>
              <th>Cliente</th>
              <th>Status</th>
              <th>Total</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ultimos_pedidos as $p): ?>
            <tr>
              <td class="td-bold"><?= htmlspecialchars($p['numero']) ?></td>
              <td><?= htmlspecialchars($p['cliente']) ?></td>
              <td><?= statusBadge($p['status']) ?></td>
              <td class="td-bold"><?= formatMoeda((float)$p['total']) ?></td>
              <td class="td-muted"><?= formatData($p['criado_em'], 'd/m/Y') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Metas Mensais -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Metas do Mês</div>
        <div class="card-subtitle">Junho 2026</div>
      </div>
    </div>
    <div class="card-body">
      <?php foreach ($metas as $meta):
        $pct = min(100, round(($meta['atual'] / $meta['meta']) * 100));
      ?>
      <div class="mb-20">
        <div class="d-flex justify-between mb-8">
          <span class="fw-600 text-small"><?= $meta['label'] ?></span>
          <span class="text-small text-muted"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill <?= $meta['cor'] ?>"
               data-width="<?= $pct ?>%"
               style="width:<?= $pct ?>%"></div>
        </div>
        <div class="d-flex justify-between mt-4">
          <span class="text-small text-muted">
            <?= is_int($meta['atual']) ? number_format($meta['atual']) : formatMoeda($meta['atual']) ?>
          </span>
          <span class="text-small text-light">
            Meta: <?= is_int($meta['meta']) ? number_format($meta['meta']) : formatMoeda($meta['meta']) ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Atividades Recentes -->
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Atividades Recentes</div>
      <div class="card-subtitle">Log de eventos do sistema</div>
    </div>
  </div>
  <div class="card-body">
    <div class="activity-list">
      <?php
      $icons = [
        'pedido'  => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/>',
        'usuario' => '<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>',
        'produto' => '<path d="M12 2l-5.5 9h11L12 2zm0 3.84L13.93 9h-3.87L12 5.84zM17.5 13c-2.49 0-4.5 2.01-4.5 4.5S15.01 22 17.5 22s4.5-2.01 4.5-4.5-2.01-4.5-4.5-4.5zm0 7c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zM3 21.5h8v-8H3v8zm2-6h4v4H5v-4z"/>',
        'sistema' => '<path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>',
      ];
      foreach ($atividades as $at):
        $ico = $icons[$at['tipo']] ?? $icons['sistema'];
      ?>
      <div class="activity-item">
        <div class="activity-icon <?= htmlspecialchars($at['tipo']) ?>">
          <svg viewBox="0 0 24 24" fill="currentColor"><?= $ico ?></svg>
        </div>
        <div class="activity-content">
          <div class="activity-desc"><?= htmlspecialchars($at['descricao']) ?></div>
          <div class="activity-time">
            <?= $at['usuario'] ? htmlspecialchars($at['usuario']) . ' • ' : '' ?>
            <?= formatData($at['criado_em']) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
