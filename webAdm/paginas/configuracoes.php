<?php
$isSuperAdmin      = Auth::isSuperAdmin();
$selectedEmpresaId = $isSuperAdmin ? (int)($_GET['empresa_id'] ?? 0) : null;

// Empresas list for superadmin selector
$empresas = [];
if ($isSuperAdmin) {
    $empApi  = new ApiClient(Auth::getToken());
    $respEmp = $empApi->get('empresas', ['status' => 'ativo', 'per_page' => 100]);
    $empresas = $respEmp['data']['dados']['data'] ?? [];
}

$api    = new ApiClient(Auth::getToken(), $selectedEmpresaId ?: null);
$grupos = [];

if (!$isSuperAdmin || $selectedEmpresaId) {
    $resp   = $api->get('configuracoes');
    $grupos = $resp['data']['dados'] ?? [];
}
?>

<!-- Page Title -->
<div class="pagetitle">
  <h1>Configuracoes</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?paginas=home">Inicio</a></li>
      <li class="breadcrumb-item active">Configuracoes</li>
    </ol>
  </nav>
</div>

<section class="section">

  <?php if ($isSuperAdmin): ?>
  <!-- Empresa selector for superadmin -->
  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="GET" class="d-flex align-items-center gap-3 flex-wrap mb-0">
        <input type="hidden" name="paginas" value="configuracoes">
        <label class="form-label mb-0 fw-semibold">
          <i class="bi bi-building me-1 text-primary"></i> Empresa:
        </label>
        <select name="empresa_id" class="form-select form-select-sm" style="width:auto;min-width:220px" onchange="this.form.submit()">
          <option value="">-- Selecione uma empresa --</option>
          <?php foreach ($empresas as $emp): ?>
            <option value="<?php echo (int)$emp['id']; ?>"
              <?php echo $selectedEmpresaId === (int)$emp['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($emp['nome'] ?? ''); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($selectedEmpresaId): ?>
          <a href="?paginas=configuracoes" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> Limpar
          </a>
        <?php endif; ?>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($isSuperAdmin && !$selectedEmpresaId): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      Selecione uma empresa acima para visualizar e editar as configuracoes.
    </div>
  <?php elseif (empty($grupos)): ?>
    <div class="alert alert-info">Nenhuma configuracao disponivel para esta empresa.</div>
  <?php else: ?>

    <div class="accordion" id="accordionConfigs">
      <?php foreach ($grupos as $idx => $grupo): ?>
        <?php
          $grupoId = 'cfg-' . $idx;
          $titulo  = htmlspecialchars($grupo['grupo'] ?? 'Geral');
          $cfgs    = $grupo['configuracoes'] ?? [];
          $isFirst = $idx === 0;
        ?>
        <div class="accordion-item">
          <h2 class="accordion-header" id="heading-<?php echo $grupoId; ?>">
            <button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-<?php echo $grupoId; ?>"
                    aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
                    aria-controls="collapse-<?php echo $grupoId; ?>">
              <i class="bi bi-gear me-2 text-primary"></i>
              <strong><?php echo $titulo; ?></strong>
              <span class="badge bg-secondary ms-2"><?php echo count($cfgs); ?></span>
            </button>
          </h2>
          <div id="collapse-<?php echo $grupoId; ?>"
               class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>"
               aria-labelledby="heading-<?php echo $grupoId; ?>"
               data-bs-parent="#accordionConfigs">
            <div class="accordion-body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width:25%">Chave</th>
                      <th style="width:40%">Valor</th>
                      <th>Descricao</th>
                      <th style="width:80px">Acao</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($cfgs as $cfg): ?>
                      <?php
                        $chave = $cfg['chave'] ?? '';
                        $tipo  = $cfg['tipo']  ?? 'text';
                        $inputType = match($tipo) {
                            'boolean'  => 'text',
                            'number'   => 'number',
                            'password' => 'password',
                            default    => 'text',
                        };
                      ?>
                      <tr>
                        <td><code class="small"><?php echo htmlspecialchars($chave); ?></code></td>
                        <td>
                          <input type="<?php echo $inputType; ?>"
                                 id="cfg_<?php echo htmlspecialchars($chave); ?>"
                                 class="form-control form-control-sm"
                                 value="<?php echo htmlspecialchars($cfg['valor'] ?? ''); ?>">
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($cfg['descricao'] ?? '-'); ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-primary"
                                  onclick="salvarConfig('<?php echo addslashes($chave); ?>', this)"
                                  title="Salvar">
                            <i class="bi bi-save"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</section>

<script>
const EMPRESA_CTX_CFG = <?php echo json_encode($selectedEmpresaId ?: null); ?>;

async function salvarConfig(chave, btn) {
  const input = document.getElementById('cfg_' + chave);
  if (!input) {
    bslToast('Campo nao encontrado.', 'danger');
    return;
  }
  bslSetLoading(btn, true);
  const res = await apiFetch('POST', 'configuracoes', { chave: chave, valor: input.value }, EMPRESA_CTX_CFG);
  bslSetLoading(btn, false);
  if (res.sucesso) {
    bslToast('Configuracao salva com sucesso!', 'success');
  } else {
    bslToast(res.mensagem || 'Erro ao salvar configuracao.', 'danger');
  }
}
</script>
