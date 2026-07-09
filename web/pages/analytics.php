<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Analytics';

$receita_total = queryOne("SELECT COALESCE(SUM(total),0) AS v FROM pedidos")['v'] ?? 0;
$total_pedidos = queryOne("SELECT COUNT(*) AS v FROM pedidos")['v'] ?? 0;
$ticket_medio  = $total_pedidos > 0 ? ($receita_total / $total_pedidos) : 0;
$total_cli     = queryOne("SELECT COUNT(*) AS v FROM clientes")['v'] ?? 0;

$por_status = query("SELECT status, COUNT(*) AS qtd FROM pedidos GROUP BY status ORDER BY qtd DESC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="breadcrumb">
    <a href="<?= SITE_URL ?>">Início</a><span class="sep">›</span>
    <span class="current">Analytics</span>
  </div>
  <h1>Analytics</h1>
  <p>Visão consolidada do desempenho do negócio</p>
</div>

<!-- KPIs de Analytics -->
<div class="kpi-grid mb-24">
  <div class="kpi-card">
    <div class="kpi-icon primary">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Receita Total</div>
      <div class="kpi-value"><?= formatMoeda((float)$receita_total) ?></div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon warning">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Total de Pedidos</div>
      <div class="kpi-value"><?= $total_pedidos ?></div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon success">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Total de Clientes</div>
      <div class="kpi-value"><?= $total_cli ?></div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon info">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
    </div>
    <div class="kpi-info">
      <div class="kpi-label">Ticket Médio</div>
      <div class="kpi-value"><?= formatMoeda((float)$ticket_medio) ?></div>
    </div>
  </div>
</div>

<!-- Gráfico de Visitantes -->
<div class="grid-21 mb-24">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Visitantes & Conversões</div>
        <div class="card-subtitle">Últimos 7 dias</div>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-wrap" style="height:280px">
        <canvas id="analyticsChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Pedidos por Status -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Pedidos por Status</div>
    </div>
    <div class="card-body">
      <?php
      $total_p = array_sum(array_column($por_status, 'qtd'));
      foreach ($por_status as $row):
        $pct = $total_p > 0 ? round(($row['qtd'] / $total_p) * 100) : 0;
        $cores = ['entregue'=>'success','pendente'=>'warning','cancelado'=>'danger','processando'=>'info','enviado'=>'primary'];
        $cor = $cores[$row['status']] ?? 'secondary';
      ?>
      <div class="mb-20">
        <div class="d-flex justify-between mb-8">
          <span class="fw-600 text-small"><?= ucfirst($row['status']) ?></span>
          <span class="text-small text-muted"><?= $row['qtd'] ?> (<?= $pct ?>%)</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill <?= $cor ?>"
               data-width="<?= $pct ?>%"
               style="width:<?= $pct ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Tabela de Receita Mensal -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Receita Mensal — 2026</div>
  </div>
  <div class="card-body" style="padding-top:0">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Mês</th>
            <th>Receita</th>
            <th>Pedidos</th>
            <th>Novos Clientes</th>
            <th>Ticket Médio</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $meses_nome = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
          $rec = query("SELECT * FROM receitas_mensais WHERE ano=2026 ORDER BY mes DESC");
          foreach ($rec as $r):
            $tk = $r['pedidos'] > 0 ? ($r['receita'] / $r['pedidos']) : 0;
          ?>
          <tr>
            <td class="td-bold"><?= $meses_nome[$r['mes']-1] ?></td>
            <td class="td-bold" style="color:var(--primary)"><?= formatMoeda((float)$r['receita']) ?></td>
            <td><?= $r['pedidos'] ?></td>
            <td><?= $r['novos_clientes'] ?></td>
            <td><?= formatMoeda($tk) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
