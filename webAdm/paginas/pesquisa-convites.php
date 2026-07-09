<?php
if (!Auth::hasPermission('pesquisa.visualizar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ?paginas=pesquisas');
    exit;
}

$api = new ApiClient(Auth::getToken());

$respPesquisa = $api->get("pesquisa-psicossocial/pesquisas/{$id}");
if (!$respPesquisa['success']) {
    echo '<div class="alert alert-danger">Campanha não encontrada. <a href="?paginas=pesquisas">Voltar</a></div>';
    return;
}
$pesquisa = $respPesquisa['data']['dados'];

$respConvites = $api->get("pesquisa-psicossocial/pesquisas/{$id}/convites");
$convites = $respConvites['data']['dados'] ?? [];

$totalRespondidos = count(array_filter($convites, fn ($c) => $c['respondido']));
$total = count($convites);
$percentual = $total > 0 ? round($totalRespondidos / $total * 100, 1) : 0;
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Convites — <?php echo htmlspecialchars($pesquisa['nome'] ?? ('Campanha #' . $id)); ?></h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item"><a href="?paginas=pesquisas">Campanhas</a></li>
        <li class="breadcrumb-item active">Convites</li>
      </ol>
    </nav>
  </div>
  <a href="?paginas=pesquisas" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>

<section class="section">
  <?php if (!empty($pesquisa['link_publico_token'])): ?>
    <?php $linkGlobal = WEBADM_URL . 'responder.php?campanha=' . $pesquisa['link_publico_token']; ?>
    <div class="card mb-3 border-primary">
      <div class="card-body">
        <h6 class="card-title"><i class="bi bi-broadcast me-1 text-primary"></i> Link Global da Empresa</h6>
        <p class="text-muted small mb-2">
          Um único link, compartilhável livremente (intranet, WhatsApp, e-mail em massa etc.) — qualquer colaborador
          pode responder, liberado apenas pelo período da campanha. Cada dispositivo/navegador só consegue enviar
          uma resposta (controle por sessão local, sem identificar a pessoa).
        </p>
        <div class="input-group">
          <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($linkGlobal); ?>" id="link-global">
          <button class="btn btn-outline-primary" type="button" onclick="copiarLinkGlobal()">
            <i class="bi bi-clipboard me-1"></i> Copiar
          </button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card text-center"><div class="card-body">
        <h3 class="mb-0"><?php echo $total; ?></h3><small class="text-muted">Convites enviados</small>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card text-center"><div class="card-body">
        <h3 class="mb-0 text-success"><?php echo $totalRespondidos; ?></h3><small class="text-muted">Já responderam</small>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card text-center"><div class="card-body">
        <h3 class="mb-0 text-primary"><?php echo $percentual; ?>%</h3><small class="text-muted">Taxa de resposta</small>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="card-title mb-0">Links individuais</h5>
      <a class="btn btn-outline-secondary btn-sm" href="exportar-convites.php?id=<?php echo (int)$id; ?>">
        <i class="bi bi-download me-1"></i> Exportar CSV
      </a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Colaborador</th><th>E-mail</th><th>Status</th><th>Link</th></tr>
        </thead>
        <tbody>
          <?php if (empty($convites)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum convite gerado ainda.</td></tr>
          <?php else: ?>
            <?php foreach ($convites as $c): ?>
              <?php $link = WEBADM_URL . 'responder.php?token=' . $c['token']; ?>
              <tr>
                <td><?php echo htmlspecialchars($c['nome']); ?></td>
                <td class="small text-muted"><?php echo htmlspecialchars($c['email']); ?></td>
                <td>
                  <?php if ($c['respondido']): ?>
                    <span class="badge bg-success">Respondido</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Pendente</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="input-group input-group-sm" style="max-width:360px">
                    <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($link); ?>" id="link-<?php echo (int)$c['id']; ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="copiarLink(<?php echo (int)$c['id']; ?>, this)">
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
function copiarLink(id, btn) {
  const input = document.getElementById('link-' + id);
  navigator.clipboard.writeText(input.value).then(() => {
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check-lg"></i>';
    setTimeout(() => { btn.innerHTML = original; }, 1200);
  });
}

function copiarLinkGlobal() {
  const input = document.getElementById('link-global');
  navigator.clipboard.writeText(input.value).then(() => bslToast('Link global copiado!', 'success'));
}
</script>
