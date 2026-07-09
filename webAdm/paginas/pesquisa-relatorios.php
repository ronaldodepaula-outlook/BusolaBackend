<?php
if (!Auth::hasPermission('relatorio.gerar') && !Auth::hasPermission('relatorio.listar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ?paginas=pesquisas'); exit; }

$api = new ApiClient(Auth::getToken());
$pesquisaResp = $api->get("pesquisa-psicossocial/pesquisas/{$id}");
$pesquisa = $pesquisaResp['data']['dados'] ?? null;

$resp = $api->get("pesquisa-psicossocial/pesquisas/{$id}/relatorios-tecnicos");
$relatorios = $resp['data']['dados'] ?? [];

$podeGerar = Auth::hasPermission('relatorio.gerar');
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1>Relatórios Técnicos — <?php echo htmlspecialchars($pesquisa['nome'] ?? ('Campanha #' . $id)); ?></h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item"><a href="?paginas=pesquisas">Campanhas</a></li>
        <li class="breadcrumb-item"><a href="?paginas=pesquisa-resultados&id=<?php echo $id; ?>">Resultados</a></li>
        <li class="breadcrumb-item active">Relatórios Técnicos</li>
      </ol>
    </nav>
  </div>
  <a href="?paginas=pesquisa-resultados&id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>

<section class="section">
  <?php if ($podeGerar): ?>
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title">Gerar novo relatório técnico</h6>
        <p class="text-muted small">Consolida o resultado atual da campanha (categorias, GHEs, classificação de risco e plano de ação) em um PDF formatado.</p>
        <div class="row g-2">
          <div class="col-md-4">
            <input type="text" id="resp-nome" class="form-control form-control-sm" placeholder="Responsável técnico (nome)">
          </div>
          <div class="col-md-4">
            <input type="text" id="resp-registro" class="form-control form-control-sm" placeholder="Registro profissional (ex.: CRP 11/15242)">
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary btn-sm w-100" onclick="gerarRelatorio(this)"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar relatório</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h6 class="mb-0">Relatórios gerados</h6></div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Gerado em</th><th>Responsável técnico</th><th>Gerado por</th><th>Tamanho</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($relatorios)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum relatório gerado ainda.</td></tr>
          <?php else: foreach ($relatorios as $r): ?>
            <tr>
              <td class="small"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['gerado_em']))); ?></td>
              <td class="small"><?php echo htmlspecialchars(trim(($r['responsavel_tecnico_nome'] ?? '—') . ' ' . ($r['responsavel_tecnico_registro'] ? '(' . $r['responsavel_tecnico_registro'] . ')' : ''))); ?></td>
              <td class="small text-muted"><?php echo htmlspecialchars($r['gerado_por']['nome'] ?? '—'); ?></td>
              <td class="small text-muted"><?php echo $r['tamanho_bytes'] ? round($r['tamanho_bytes'] / 1024) . ' KB' : '—'; ?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="relatorio-download.php?id=<?php echo (int)$r['id']; ?>" target="_blank">
                  <i class="bi bi-download me-1"></i>Baixar
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
async function gerarRelatorio(btn) {
  bslSetLoading(btn, true);
  const body = {
    responsavel_tecnico_nome: document.getElementById('resp-nome').value || null,
    responsavel_tecnico_registro: document.getElementById('resp-registro').value || null,
  };
  const res = await apiFetch('POST', 'pesquisa-psicossocial/pesquisas/<?php echo $id; ?>/relatorios-tecnicos', body);
  bslSetLoading(btn, false);
  if (res.sucesso) { bslToast('Relatório técnico gerado com sucesso.', 'success'); setTimeout(() => location.reload(), 800); }
  else { bslToast(res.mensagem || 'Erro ao gerar relatório.', 'danger'); }
}
</script>
