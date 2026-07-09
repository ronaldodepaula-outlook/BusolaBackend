<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Clientes';

$busca  = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$where = 'WHERE 1=1';
$params = [];
if ($busca)  { $where .= ' AND (nome LIKE ? OR email LIKE ?)'; $params[] = "%{$busca}%"; $params[] = "%{$busca}%"; }
if ($status) { $where .= ' AND status = ?'; $params[] = $status; }

$clientes = query("SELECT * FROM clientes {$where} ORDER BY criado_em DESC", $params);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Início</a><span class="sep">›</span>
      <span class="current">Clientes</span>
    </div>
    <h1>Clientes</h1>
    <p><?= count($clientes) ?> cliente(s) encontrado(s)</p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="showToast('Funcionalidade em desenvolvimento','info')">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    Novo Cliente
  </button>
</div>

<!-- Filtros -->
<div class="card mb-24">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" class="d-flex gap-12 align-center" style="flex-wrap:wrap">
      <div style="flex:1;min-width:200px">
        <input type="search" name="q" value="<?= htmlspecialchars($busca) ?>"
               class="form-control" placeholder="Buscar por nome ou e-mail...">
      </div>
      <select name="status" class="form-control" style="min-width:160px">
        <option value="">Todos os status</option>
        <?php foreach (['ativo','inativo','bloqueado'] as $s): ?>
        <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
      <?php if ($busca || $status): ?>
      <a href="customers.php" class="btn btn-outline btn-sm">Limpar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding-top:0">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Telefone</th>
            <th>Cidade/UF</th>
            <th>Status</th>
            <th>Pedidos</th>
            <th>Total Gasto</th>
            <th>Desde</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clientes as $c):
            $iniciais = strtoupper(substr($c['nome'], 0, 1));
          ?>
          <tr>
            <td>
              <div class="d-flex align-center gap-12">
                <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);
                            display:flex;align-items:center;justify-content:center;
                            color:var(--primary);font-weight:700;font-size:13px;flex-shrink:0">
                  <?= $iniciais ?>
                </div>
                <div>
                  <div class="fw-600"><?= htmlspecialchars($c['nome']) ?></div>
                  <div class="td-muted"><?= htmlspecialchars($c['email']) ?></div>
                </div>
              </div>
            </td>
            <td class="td-muted"><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
            <td class="td-muted">
              <?= htmlspecialchars($c['cidade'] ?? '—') ?>
              <?= $c['estado'] ? '/' . $c['estado'] : '' ?>
            </td>
            <td><?= statusBadge($c['status']) ?></td>
            <td><?= $c['total_pedidos'] ?></td>
            <td class="fw-600" style="color:var(--primary)"><?= formatMoeda((float)$c['total_gasto']) ?></td>
            <td class="td-muted"><?= formatData($c['criado_em'], 'd/m/Y') ?></td>
            <td>
              <button class="btn btn-ghost btn-icon btn-sm"
                      onclick="showToast('Ver cliente: <?= htmlspecialchars($c['nome']) ?>','info')"
                      data-tip="Ver detalhes">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
