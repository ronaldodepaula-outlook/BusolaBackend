<?php
/**
 * Página pública de resposta a uma campanha de pesquisa psicossocial.
 * Standalone — fora do fluxo autenticado do painel (sem Auth::exigirAuth()).
 *
 * Dois modos de acesso:
 *  - Individual: responder.php?token=xxxx (um link por colaborador convidado)
 *  - Global: responder.php?campanha=xxxx (link único compartilhado da empresa;
 *    o controle de "uma resposta por dispositivo" é feito por um token de
 *    sessão gerado no primeiro acesso e guardado em cookie neste navegador).
 */
require_once __DIR__ . '/classe/Config.php';
require_once __DIR__ . '/classe/ApiClient.php';

$tokenIndividual = trim($_GET['token'] ?? '');
$tokenGlobal = trim($_GET['campanha'] ?? '');
$modo = $tokenIndividual !== '' ? 'individual' : ($tokenGlobal !== '' ? 'global' : null);

$api = new ApiClient(); // endpoint público — sem token de autenticação
$ipVisitante = $_SERVER['REMOTE_ADDR'] ?? null;

$sessaoToken = null;
if ($modo === 'global') {
    $cookieNome = 'psq_sessao_' . substr(md5($tokenGlobal), 0, 16);
    $sessaoToken = $_COOKIE[$cookieNome] ?? '';
    if ($sessaoToken === '') {
        $sessaoToken = bin2hex(random_bytes(24));
        setcookie($cookieNome, $sessaoToken, time() + 60 * 60 * 24 * 365, '/');
    }
}

$erro = null;
$dados = null;
$concluido = false;

if ($modo === null) {
    $erro = 'Link inválido. Verifique se você copiou o endereço completo enviado a você.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $respostas = $_POST['respostas'] ?? [];
    $observacoes = $_POST['observacoes'] ?? [];

    if ($modo === 'individual') {
        $resp = $api->post("pesquisa-psicossocial/publico/{$tokenIndividual}/respostas", [
            'respostas'   => $respostas,
            'observacoes' => $observacoes,
        ]);
    } else {
        $resp = $api->post("pesquisa-psicossocial/publico/global/{$tokenGlobal}/respostas", [
            'sessao_token' => $sessaoToken,
            'ip'           => $ipVisitante,
            'setor_id'     => $_POST['setor_id'] ?: null,
            'respostas'    => $respostas,
            'observacoes'  => $observacoes,
        ]);
    }

    if ($resp['success']) {
        $concluido = true;
    } else {
        $erro = $resp['data']['mensagem'] ?? 'Não foi possível registrar sua resposta. Tente novamente.';
        // Recarrega a estrutura para reexibir o formulário com o erro
        $respEstrutura = $modo === 'individual'
            ? $api->get("pesquisa-psicossocial/publico/{$tokenIndividual}")
            : $api->get("pesquisa-psicossocial/publico/global/{$tokenGlobal}", ['sessao_token' => $sessaoToken, 'ip' => $ipVisitante]);
        if ($respEstrutura['success']) {
            $dados = $respEstrutura['data']['dados'];
        }
    }
} else {
    $resp = $modo === 'individual'
        ? $api->get("pesquisa-psicossocial/publico/{$tokenIndividual}")
        : $api->get("pesquisa-psicossocial/publico/global/{$tokenGlobal}", ['sessao_token' => $sessaoToken, 'ip' => $ipVisitante]);

    if ($resp['success']) {
        $dados = $resp['data']['dados'];
    } else {
        $erro = $resp['data']['mensagem'] ?? 'Este link não está disponível no momento.';
    }
}

$etapa = $_GET['etapa'] ?? 'aviso';
$linkBase = $modo === 'individual'
    ? '?token=' . urlencode($tokenIndividual)
    : '?campanha=' . urlencode($tokenGlobal);

$tipoLabels = [
    'escala' => 'escala', 'texto' => 'texto', 'numero' => 'numero',
    'data' => 'data', 'sim_nao' => 'sim_nao',
    'multipla_escolha' => 'multipla_escolha', 'unica_escolha' => 'unica_escolha',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Pesquisa de Clima e Riscos Psicossociais</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f4f6f9; }
    .responder-wrap { max-width: 760px; margin: 0 auto; padding: 2.5rem 1rem; }
    .pergunta-card { border-left: 4px solid #0d6efd22; }
    .item-opcao { border:1px solid #dee2e6; border-radius:.5rem; padding:.5rem .75rem; margin-bottom:.4rem; cursor:pointer; }
    .item-opcao:hover { background:#f8f9fa; }
    .categoria-titulo { border-bottom:2px solid #0d6efd; padding-bottom:.4rem; margin-top:2rem; }
  </style>
</head>
<body>
<div class="responder-wrap">
  <div class="text-center mb-4">
    <i class="bi bi-shield-lock display-5 text-primary"></i>
    <h4 class="mt-2 mb-0">Pesquisa de Clima e Riscos Psicossociais</h4>
    <p class="text-muted small">Sua participação é anônima e voluntária.</p>
  </div>

  <?php if ($erro): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-exclamation-circle display-4 text-warning"></i>
        <p class="mt-3 mb-0 fs-5"><?php echo htmlspecialchars($erro); ?></p>
      </div>
    </div>

  <?php elseif ($concluido): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-check-circle display-4 text-success"></i>
        <h5 class="mt-3">Obrigado pela sua participação!</h5>
        <p class="text-muted mb-0">Sua resposta foi registrada de forma anônima. Você já pode fechar esta página.</p>
      </div>
    </div>

  <?php elseif ($dados && $etapa === 'aviso'): ?>
    <div class="card">
      <div class="card-body p-4">
        <h5><?php echo htmlspecialchars($dados['pesquisa']['nome'] ?? 'Pesquisa'); ?></h5>
        <?php if (!empty($dados['pesquisa']['descricao'])): ?>
          <p class="text-muted"><?php echo htmlspecialchars($dados['pesquisa']['descricao']); ?></p>
        <?php endif; ?>
        <div class="alert alert-light border">
          <i class="bi bi-incognito me-2"></i>
          <strong>Sua identidade não será associada às suas respostas.</strong>
          <?php if ($modo === 'individual'): ?>
            Este link é pessoal apenas para garantir que cada colaborador responda uma única vez — o conteúdo do
            que você responder não fica vinculado ao seu usuário em nenhum momento.
          <?php else: ?>
            Este link é compartilhado entre os colaboradores da empresa; identificamos apenas que "este
            dispositivo" já respondeu, para evitar respostas repetidas — o conteúdo do que você responder não
            fica vinculado a você nem ao seu dispositivo em nenhum momento.
          <?php endif; ?>
          Responda com sinceridade.
        </div>
        <a class="btn btn-primary w-100" href="<?php echo $linkBase; ?>&etapa=form">
          <i class="bi bi-arrow-right-circle me-1"></i> Iniciar
        </a>
      </div>
    </div>

  <?php elseif ($dados): ?>
    <form method="POST" action="<?php echo $linkBase; ?>">
      <?php if ($modo === 'global' && !empty($dados['setores'])): ?>
        <div class="card pergunta-card mb-3">
          <div class="card-body">
            <label class="form-label fw-semibold">Qual é o seu setor? <span class="text-danger">*</span></label>
            <p class="text-muted small">Usado apenas para agrupar os resultados por área — sua resposta continua anônima.</p>
            <select class="form-select" name="setor_id" required>
              <option value="">Selecione...</option>
              <?php foreach ($dados['setores'] as $setor): ?>
                <option value="<?php echo (int)$setor['id']; ?>"><?php echo htmlspecialchars($setor['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>
      <?php foreach (($dados['formulario']['categorias'] ?? []) as $categoria): ?>
        <h6 class="categoria-titulo"><?php echo htmlspecialchars($categoria['nome']); ?></h6>
        <?php foreach (($categoria['subcategorias'] ?? []) as $subcategoria): ?>
          <?php foreach (($subcategoria['perguntas'] ?? []) as $pergunta): ?>
            <div class="card pergunta-card mb-3">
              <div class="card-body">
                <label class="form-label fw-semibold">
                  <?php echo htmlspecialchars($pergunta['texto']); ?>
                  <?php if ($pergunta['obrigatoria']): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <?php if (!empty($pergunta['descricao'])): ?>
                  <p class="text-muted small"><?php echo htmlspecialchars($pergunta['descricao']); ?></p>
                <?php endif; ?>

                <?php $nomeCampo = "respostas[{$pergunta['id']}]"; $req = $pergunta['obrigatoria'] ? 'required' : ''; ?>

                <?php if (in_array($pergunta['tipo_pergunta'], ['escala', 'unica_escolha'], true)): ?>
                  <?php foreach (($pergunta['conceito']['itens'] ?? []) as $item): ?>
                    <label class="item-opcao d-block">
                      <input type="radio" class="form-check-input me-2" name="<?php echo $nomeCampo; ?>" value="<?php echo (int)$item['id']; ?>" <?php echo $req; ?>>
                      <?php echo htmlspecialchars($item['descricao']); ?>
                    </label>
                  <?php endforeach; ?>

                <?php elseif ($pergunta['tipo_pergunta'] === 'multipla_escolha'): ?>
                  <?php foreach (($pergunta['conceito']['itens'] ?? []) as $item): ?>
                    <label class="item-opcao d-block">
                      <input type="checkbox" class="form-check-input me-2" name="<?php echo $nomeCampo; ?>[]" value="<?php echo (int)$item['id']; ?>">
                      <?php echo htmlspecialchars($item['descricao']); ?>
                    </label>
                  <?php endforeach; ?>

                <?php elseif ($pergunta['tipo_pergunta'] === 'sim_nao'): ?>
                  <label class="item-opcao d-block">
                    <input type="radio" class="form-check-input me-2" name="<?php echo $nomeCampo; ?>" value="sim" <?php echo $req; ?>> Sim
                  </label>
                  <label class="item-opcao d-block">
                    <input type="radio" class="form-check-input me-2" name="<?php echo $nomeCampo; ?>" value="nao" <?php echo $req; ?>> Não
                  </label>

                <?php elseif ($pergunta['tipo_pergunta'] === 'numero'): ?>
                  <input type="number" class="form-control" name="<?php echo $nomeCampo; ?>" <?php echo $req; ?>>

                <?php elseif ($pergunta['tipo_pergunta'] === 'data'): ?>
                  <input type="date" class="form-control" name="<?php echo $nomeCampo; ?>" <?php echo $req; ?>>

                <?php else: // texto ?>
                  <textarea class="form-control" name="<?php echo $nomeCampo; ?>" rows="2" <?php echo $req; ?>></textarea>
                <?php endif; ?>

                <?php if (!empty($pergunta['permite_observacao'])): ?>
                  <div class="mt-2">
                    <label class="form-label form-label-sm text-muted">Observação (opcional)</label>
                    <textarea class="form-control form-control-sm" name="observacoes[<?php echo (int)$pergunta['id']; ?>]" rows="1"></textarea>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <button type="submit" class="btn btn-success w-100 mt-2">
        <i class="bi bi-send-check me-1"></i> Enviar respostas
      </button>
    </form>
  <?php endif; ?>

  <p class="text-center text-muted small mt-4">busola — Gestão de Pesquisas Psicossociais</p>
</div>
</body>
</html>
