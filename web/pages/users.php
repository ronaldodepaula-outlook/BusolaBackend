<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Usuários';

$usuarios = query("SELECT * FROM usuarios ORDER BY criado_em DESC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <div class="breadcrumb">
      <a href="<?= SITE_URL ?>">Início</a><span class="sep">›</span>
      <span class="current">Usuários</span>
    </div>
    <h1>Usuários do Sistema</h1>
    <p><?= count($usuarios) ?> usuário(s) cadastrado(s)</p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="showToast('Funcionalidade em desenvolvimento','info')">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    Novo Usuário
  </button>
</div>

<div class="card">
  <div class="card-body" style="padding-top:0">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Usuário</th>
            <th>Perfil</th>
            <th>Status</th>
            <th>Cadastrado em</th>
            <th>Último Acesso</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u):
            $iniciais = strtoupper(substr($u['nome'], 0, 1));
            $cores_perfil = ['admin'=>'danger','gerente'=>'warning','operador'=>'primary'];
            $cor_perfil = $cores_perfil[$u['perfil']] ?? 'secondary';
          ?>
          <tr>
            <td>
              <div class="d-flex align-center gap-12">
                <div style="width:38px;height:38px;border-radius:50%;background:var(--primary);
                            display:flex;align-items:center;justify-content:center;
                            color:#fff;font-weight:700;font-size:14px;flex-shrink:0">
                  <?= $iniciais ?>
                </div>
                <div>
                  <div class="fw-600"><?= htmlspecialchars($u['nome']) ?></div>
                  <div class="td-muted"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge badge-<?= $cor_perfil ?>"><?= ucfirst($u['perfil']) ?></span></td>
            <td><?= statusBadge($u['ativo'] ? 'ativo' : 'inativo') ?></td>
            <td class="td-muted"><?= formatData($u['criado_em'], 'd/m/Y') ?></td>
            <td class="td-muted">
              <?= $u['ultimo_acesso'] ? formatData($u['ultimo_acesso']) : '—' ?>
            </td>
            <td>
              <div class="d-flex gap-8">
                <button class="btn btn-ghost btn-icon btn-sm"
                        onclick="showToast('Editar usuário: <?= htmlspecialchars($u['nome']) ?>','info')"
                        data-tip="Editar">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                </button>
                <?php if ($u['perfil'] !== 'admin'): ?>
                <button class="btn btn-ghost btn-icon btn-sm"
                        onclick="showToast('Ação executada','warning')"
                        data-tip="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?= $u['ativo'] ? 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z' : 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z' ?>"/></svg>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
