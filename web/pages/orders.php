<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Pedidos';

$status_filtro = $_GET['status'] ?? '';
$busca = trim($_GET['q'] ?? '');

$where = 'WHERE 1=1';
$params = [];

if ($status_filtro) {
    $where .= ' AND p.status = ?';
    $params[] = $status_filtro;
}
if ($busca) {
    $where .= ' AND (p.numero LIKE ? OR c.nome LIKE ?)';
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$pedidos = query("
    SELECT p.*, c.nome AS cliente, c.email AS email_cliente
    FROM pedidos p
    JOIN clientes c ON c.id = p.cliente_id
    {$where}
    ORDER BY p.criado_em DESC
", $params);

$contagem = queryOne("SELECT COUNT(*) AS v, COALESCE(SUM(total),0) AS total FROM pedidos")['v'];
$pend = queryOne("SELECT COUNT(*) AS v FROM pedidos WHERE status='pendente'")['v'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Início</a><span class="sep">›</span>
      <span class="current">Pedidos</span>
    </div>
    <h1>Pedidos</h1>
    <p><?= count($pedidos) ?> resultado(s) encontrado(s)</p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="showToast('Funcionalidade em desenvolvimento','info')">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    Novo Pedido
  </button>
</div>

<?php if ($pend > 0): ?>
<div class="alert alert-warning">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
  <span>Há <strong><?= $pend ?> pedido(s) pendente(s)</strong> aguardando processamento.</span>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-24">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" class="d-flex gap-12 align-center" style="flex-wrap:wrap">
      <div style="flex:1;min-width:200px">
        <input type="search" name="q" value="<?= htmlspecialchars($busca) ?>"
               class="form-control" placeholder="Buscar por número ou cliente...">
      </div>
      <div>
        <select name="status" class="form-control" style="min-width:160px">
          <option value="">Todos os status</option>
          <?php foreach (['pendente','processando','enviado','entregue','cancelado'] as $s): ?>
          <option value="<?= $s ?>" <?= $status_filtro===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
      <?php if ($busca || $status_filtro): ?>
      <a href="orders.php" class="btn btn-outline btn-sm">Limpar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Tabela de Pedidos -->
<div class="card">
  <div class="card-body" style="padding-top:0">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Número</th>
            <th>Cliente</th>
            <th>Status</th>
            <th>Subtotal</th>
            <th>Desconto</th>
            <th>Total</th>
            <th>Data</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pedidos)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted" style="padding:32px">
              Nenhum pedido encontrado.
            </td>
          </tr>
          <?php else: foreach ($pedidos as $p): ?>
          <tr>
            <td class="td-bold"><?= htmlspecialchars($p['numero']) ?></td>
            <td>
              <div class="fw-600"><?= htmlspecialchars($p['cliente']) ?></div>
              <div class="td-muted"><?= htmlspecialchars($p['email_cliente']) ?></div>
            </td>
            <td><?= statusBadge($p['status']) ?></td>
            <td><?= formatMoeda((float)$p['subtotal']) ?></td>
            <td class="text-muted">
              <?= $p['desconto'] > 0 ? '- '.formatMoeda((float)$p['desconto']) : '—' ?>
            </td>
            <td class="td-bold" style="color:var(--primary)"><?= formatMoeda((float)$p['total']) ?></td>
            <td class="td-muted"><?= formatData($p['criado_em'], 'd/m/Y H:i') ?></td>
            <td>
              <button class="btn btn-ghost btn-icon btn-sm"
                      onclick="showToast('Detalhes do pedido <?= htmlspecialchars($p['numero']) ?>','info')"
                      data-tip="Ver detalhes">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
