<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Configurações';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="breadcrumb">
    <a href="<?= SITE_URL ?>">Início</a><span class="sep">›</span>
    <span class="current">Configurações</span>
  </div>
  <h1>Configurações</h1>
  <p>Gerencie as preferências do sistema</p>
</div>

<div class="grid-13">

  <!-- Menu lateral de seções -->
  <div class="card" style="align-self:start">
    <div class="card-body" style="padding:8px">
      <?php
      $secoes = [
        ['id'=>'geral',       'label'=>'Geral',            'icon'=>'M12 2l-5.5 9h11L12 2zm0 3.84L13.93 9h-3.87L12 5.84zM17.5 13c-2.49 0-4.5 2.01-4.5 4.5S15.01 22 17.5 22s4.5-2.01 4.5-4.5-2.01-4.5-4.5-4.5zm0 7c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zM3 21.5h8v-8H3v8zm2-6h4v4H5v-4z'],
        ['id'=>'seguranca',   'label'=>'Segurança',        'icon'=>'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5zm-1 5v2h2v-2h-2zm0-4v3h2V6h-2z'],
        ['id'=>'notificacoes','label'=>'Notificações',     'icon'=>'M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z'],
        ['id'=>'aparencia',   'label'=>'Aparência',        'icon'=>'M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z'],
        ['id'=>'banco',       'label'=>'Banco de Dados',   'icon'=>'M12 3C7.58 3 4 4.79 4 7s3.58 4 8 4 8-1.79 8-4-3.58-4-8-4zM4 9v3c0 2.21 3.58 4 8 4s8-1.79 8-4V9c0 2.21-3.58 4-8 4s-8-1.79-8-4zm0 5v3c0 2.21 3.58 4 8 4s8-1.79 8-4v-3c0 2.21-3.58 4-8 4s-8-1.79-8-4z'],
      ];
      foreach ($secoes as $i => $s): ?>
      <a href="#sec-<?= $s['id'] ?>"
         class="nav-item <?= $i===0?'active':'' ?>"
         onclick="document.querySelectorAll('.sidebar-settings .nav-item').forEach(x=>x.classList.remove('active'));this.classList.add('active')"
         style="color:var(--text);margin:2px 0">
        <span class="nav-icon" style="color:var(--primary)">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?= $s['icon'] ?>"/></svg>
        </span>
        <span><?= $s['label'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Conteúdo das Configurações -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Geral -->
    <div class="card" id="sec-geral">
      <div class="card-header"><div class="card-title">Configurações Gerais</div></div>
      <div class="card-body">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Nome do Sistema</label>
            <input type="text" class="form-control" value="<?= SITE_NAME ?>">
          </div>
          <div class="form-group">
            <label class="form-label">URL do Sistema</label>
            <input type="text" class="form-control" value="<?= SITE_URL ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Idioma</label>
            <select class="form-control">
              <option selected>Português (Brasil)</option>
              <option>English</option>
              <option>Español</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Fuso Horário</label>
            <select class="form-control">
              <option selected>America/Sao_Paulo (UTC-3)</option>
              <option>America/Manaus (UTC-4)</option>
              <option>America/Belem (UTC-3)</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Moeda Padrão</label>
          <select class="form-control" style="max-width:300px">
            <option selected>BRL — Real Brasileiro</option>
            <option>USD — Dólar Americano</option>
            <option>EUR — Euro</option>
          </select>
        </div>
        <button class="btn btn-primary" onclick="showToast('Configurações salvas com sucesso!','success')">
          Salvar Configurações
        </button>
      </div>
    </div>

    <!-- Segurança -->
    <div class="card" id="sec-seguranca">
      <div class="card-header"><div class="card-title">Segurança</div></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Senha Atual</label>
          <input type="password" class="form-control" style="max-width:400px" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label class="form-label">Nova Senha</label>
          <input type="password" class="form-control" style="max-width:400px" placeholder="••••••••">
          <div class="form-hint">Mínimo 8 caracteres, com letras e números.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirmar Nova Senha</label>
          <input type="password" class="form-control" style="max-width:400px" placeholder="••••••••">
        </div>
        <button class="btn btn-primary" onclick="showToast('Senha alterada com sucesso!','success')">
          Alterar Senha
        </button>
      </div>
    </div>

    <!-- Notificações -->
    <div class="card" id="sec-notificacoes">
      <div class="card-header"><div class="card-title">Notificações</div></div>
      <div class="card-body">
        <?php
        $notifs = [
          ['Novo pedido recebido',          true],
          ['Pedido enviado',                true],
          ['Estoque baixo',                 true],
          ['Novo cliente cadastrado',        false],
          ['Relatório semanal por e-mail',   true],
          ['Alertas de segurança',           true],
        ];
        foreach ($notifs as [$label, $checked]):
        ?>
        <div class="d-flex align-center justify-between mb-16"
             style="padding:12px 0;border-bottom:1px solid var(--border)">
          <span class="fw-600 text-small"><?= $label ?></span>
          <label style="display:flex;align-items:center;cursor:pointer;gap:8px">
            <input type="checkbox" <?= $checked?'checked':'' ?>
                   style="width:16px;height:16px;accent-color:var(--primary)"
                   onchange="showToast('Preferência atualizada','success')">
          </label>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Banco de Dados -->
    <div class="card" id="sec-banco">
      <div class="card-header"><div class="card-title">Banco de Dados</div></div>
      <div class="card-body">
        <div class="alert alert-info">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
          <span>Conexão ativa com <strong><?= DB_HOST ?> / <?= DB_NAME ?></strong></span>
        </div>
        <div class="d-flex gap-12">
          <button class="btn btn-outline btn-sm" onclick="showToast('Backup iniciado...','info')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
            Gerar Backup
          </button>
          <button class="btn btn-outline btn-sm" onclick="showToast('Otimização concluída!','success')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
            Otimizar Tabelas
          </button>
        </div>
      </div>
    </div>

  </div><!-- /.settings-content -->
</div><!-- /.grid-13 -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
