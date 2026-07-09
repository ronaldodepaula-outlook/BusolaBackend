<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Produtos';

$busca = trim($_GET['q'] ?? '');
$where = $busca ? "WHERE p.nome LIKE ? OR p.sku LIKE ?" : '';
$params = $busca ? ["%{$busca}%", "%{$busca}%"] : [];

$produtos = query("
    SELECT p.*, c.nome AS categoria
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    {$where}
    ORDER BY p.criado_em DESC
", $params);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Início</a><span class="sep">›</span>
      <span class="current">Produtos</span>
    </div>
    <h1>Produtos</h1>
    <p><?= count($produtos) ?> produto(s) encontrado(s)</p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="showToast('Funcionalidade em desenvolvimento','info')">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    Novo Produto
  </button>
</div>

<!-- Busca -->
<div class="card mb-24">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" class="d-flex gap-12 align-center">
      <div style="flex:1">
        <input type="search" name="q" value="<?= htmlspecialchars($busca) ?>"
               class="form-control" placeholder="Buscar por nome ou SKU...">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
      <?php if ($busca): ?><a href="products.php" class="btn btn-outline btn-sm">Limpar</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding-top:0">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Produto</th>
            <th>SKU</th>
            <th>Categoria</th>
            <th>Preço</th>
            <th>Estoque</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($produtos as $p):
            $estoque_alerta = $p['estoque'] < 20;
          ?>
          <tr>
            <td class="td-bold"><?= htmlspecialchars($p['nome']) ?></td>
            <td class="td-muted"><?= htmlspecialchars($p['sku']) ?></td>
            <td><?= htmlspecialchars($p['categoria'] ?? '—') ?></td>
            <td class="fw-600" style="color:var(--primary)"><?= formatMoeda((float)$p['preco']) ?></td>
            <td>
              <span style="color:<?= $estoque_alerta ? 'var(--danger)' : 'var(--text)' ?>;font-weight:<?= $estoque_alerta ? '700' : '400' ?>">
                <?= $p['estoque'] ?> un.
              </span>
              <?php if ($estoque_alerta): ?>
              <span class="badge badge-danger" style="font-size:10px;margin-left:4px">Baixo</span>
              <?php endif; ?>
            </td>
            <td><?= statusBadge($p['status']) ?></td>
            <td>
              <button class="btn btn-ghost btn-icon btn-sm"
                      onclick="showToast('Editar: <?= htmlspecialchars($p['nome']) ?>','info')"
                      data-tip="Editar produto">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
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
