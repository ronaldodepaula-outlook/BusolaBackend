<?php
if (!Auth::hasPermission('categoria.criar') || !Auth::hasPermission('subcategoria.criar') || !Auth::hasPermission('pergunta.criar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para importar estrutura de formulário.</div>';
    return;
}

$formularioId = (int)($_GET['id'] ?? 0);
if (!$formularioId) {
    header('Location: ?paginas=formularios');
    exit;
}

$api = new ApiClient(Auth::getToken());

function normalizar(string $s): string
{
    return mb_strtolower(trim($s));
}

function paraBool($valor): bool
{
    $v = normalizar((string)$valor);
    return in_array($v, ['sim', 's', '1', 'true', 'yes'], true);
}

$linhas = [];       // relatório: ['linha' => n, 'ok' => bool, 'mensagem' => string]
$totalOk = 0;
$totalErro = 0;
$formularioAtualId = $formularioId;
$formularioVersionadoDurante = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv']['tmp_name']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {

    $handle = fopen($_FILES['csv']['tmp_name'], 'r');
    if ($handle === false) {
        $linhas[] = ['linha' => 0, 'ok' => false, 'mensagem' => 'Não foi possível ler o arquivo enviado.'];
    } else {
        $header = fgetcsv($handle, 0, ',');
        if ($header) {
            $header = array_map(fn ($h) => normalizar($h), $header);
        }

        $colunasEsperadas = ['categoria', 'subcategoria', 'tipo_pergunta', 'texto', 'obrigatoria', 'permite_observacao', 'permite_anexo', 'conceito'];
        $faltando = array_diff($colunasEsperadas, $header ?: []);

        if (!$header || !empty($faltando)) {
            $linhas[] = ['linha' => 1, 'ok' => false, 'mensagem' => 'Cabeçalho inválido. Colunas esperadas: ' . implode(', ', $colunasEsperadas)];
        } else {
            // Carrega todos os conceitos visíveis (nome normalizado => id)
            $conceitoCache = [];
            $respConceitos = $api->get('pesquisa-psicossocial/conceitos', ['per_page' => 200]);
            foreach (($respConceitos['data']['dados']['data'] ?? []) as $c) {
                $conceitoCache[normalizar($c['nome'])] = $c['id'];
            }

            $carregarCategorias = function (int $fid) use ($api) {
                $mapa = [];
                $resp = $api->get("pesquisa-psicossocial/formularios/{$fid}/categorias");
                foreach (($resp['data']['dados'] ?? []) as $cat) {
                    $mapa[normalizar($cat['nome'])] = $cat['id'];
                }
                return $mapa;
            };
            $carregarSubcategorias = function (int $categoriaId) use ($api) {
                $mapa = [];
                $resp = $api->get("pesquisa-psicossocial/categorias/{$categoriaId}/subcategorias");
                foreach (($resp['data']['dados'] ?? []) as $sub) {
                    $mapa[normalizar($sub['nome'])] = $sub['id'];
                }
                return $mapa;
            };

            $categoriaCache = $carregarCategorias($formularioAtualId);
            $subcategoriaCachePorCategoria = []; // categoriaId => [nomeNormalizado => id]

            $tiposValidos = ['escala', 'texto', 'numero', 'data', 'sim_nao', 'multipla_escolha', 'unica_escolha'];
            $tiposQueExigemConceito = ['escala', 'multipla_escolha', 'unica_escolha'];

            $numeroLinha = 1; // linha 1 = cabeçalho
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $numeroLinha++;
                if (count(array_filter($row, fn ($v) => trim((string)$v) !== '')) === 0) {
                    continue; // linha em branco
                }

                $dados = @array_combine($header, $row);
                if ($dados === false) {
                    $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => 'Número de colunas incompatível com o cabeçalho.'];
                    $totalErro++;
                    continue;
                }

                $nomeCategoria    = trim($dados['categoria'] ?? '');
                $nomeSubcategoria = trim($dados['subcategoria'] ?? '');
                $tipoPergunta     = normalizar($dados['tipo_pergunta'] ?? '');
                $texto            = trim($dados['texto'] ?? '');
                $nomeConceito     = trim($dados['conceito'] ?? '');

                if ($nomeCategoria === '' || $nomeSubcategoria === '' || $texto === '') {
                    $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => 'Categoria, subcategoria e texto são obrigatórios.'];
                    $totalErro++;
                    continue;
                }

                if (!in_array($tipoPergunta, $tiposValidos, true)) {
                    $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => "Tipo de pergunta inválido: \"{$tipoPergunta}\"."];
                    $totalErro++;
                    continue;
                }

                $conceitoId = null;
                if ($nomeConceito !== '') {
                    $conceitoId = $conceitoCache[normalizar($nomeConceito)] ?? null;
                    if (!$conceitoId) {
                        $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => "Conceito \"{$nomeConceito}\" não encontrado."];
                        $totalErro++;
                        continue;
                    }
                } elseif (in_array($tipoPergunta, $tiposQueExigemConceito, true)) {
                    $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => "Tipo \"{$tipoPergunta}\" exige um conceito de avaliação."];
                    $totalErro++;
                    continue;
                }

                // Resolve/cria categoria
                $chaveCategoria = normalizar($nomeCategoria);
                if (!isset($categoriaCache[$chaveCategoria])) {
                    $respCat = $api->post("pesquisa-psicossocial/formularios/{$formularioAtualId}/categorias", ['nome' => $nomeCategoria]);
                    if (!$respCat['success']) {
                        $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => 'Erro ao criar categoria "' . $nomeCategoria . '": ' . ($respCat['data']['mensagem'] ?? 'erro desconhecido')];
                        $totalErro++;
                        continue;
                    }
                    if (!empty($respCat['data']['dados']['versionado']) && $respCat['data']['dados']['formulario_atual_id'] != $formularioAtualId) {
                        $formularioAtualId = $respCat['data']['dados']['formulario_atual_id'];
                        $formularioVersionadoDurante = true;
                        $categoriaCache = $carregarCategorias($formularioAtualId);
                        $subcategoriaCachePorCategoria = [];
                    }
                    $categoriaCache[$chaveCategoria] = $respCat['data']['dados']['categoria']['id'];
                }
                $categoriaId = $categoriaCache[$chaveCategoria];

                // Resolve/cria subcategoria
                if (!isset($subcategoriaCachePorCategoria[$categoriaId])) {
                    $subcategoriaCachePorCategoria[$categoriaId] = $carregarSubcategorias($categoriaId);
                }
                $chaveSub = normalizar($nomeSubcategoria);
                if (!isset($subcategoriaCachePorCategoria[$categoriaId][$chaveSub])) {
                    $respSub = $api->post("pesquisa-psicossocial/categorias/{$categoriaId}/subcategorias", ['nome' => $nomeSubcategoria]);
                    if (!$respSub['success']) {
                        $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => 'Erro ao criar subcategoria "' . $nomeSubcategoria . '": ' . ($respSub['data']['mensagem'] ?? 'erro desconhecido')];
                        $totalErro++;
                        continue;
                    }
                    if (!empty($respSub['data']['dados']['versionado']) && $respSub['data']['dados']['formulario_atual_id'] != $formularioAtualId) {
                        $formularioAtualId = $respSub['data']['dados']['formulario_atual_id'];
                        $formularioVersionadoDurante = true;
                        $categoriaCache = $carregarCategorias($formularioAtualId);
                        $categoriaId = $categoriaCache[$chaveCategoria] ?? $categoriaId;
                        $subcategoriaCachePorCategoria = [];
                        $subcategoriaCachePorCategoria[$categoriaId] = $carregarSubcategorias($categoriaId);
                    }
                    $subcategoriaCachePorCategoria[$categoriaId][$chaveSub] = $respSub['data']['dados']['subcategoria']['id'];
                }
                $subcategoriaId = $subcategoriaCachePorCategoria[$categoriaId][$chaveSub];

                // Cria a pergunta
                $respPerg = $api->post("pesquisa-psicossocial/subcategorias/{$subcategoriaId}/perguntas", [
                    'tipo_pergunta'      => $tipoPergunta,
                    'texto'              => $texto,
                    'conceito_id'        => $conceitoId,
                    'obrigatoria'        => paraBool($dados['obrigatoria'] ?? ''),
                    'permite_observacao' => paraBool($dados['permite_observacao'] ?? ''),
                    'permite_anexo'      => paraBool($dados['permite_anexo'] ?? ''),
                ]);

                if (!$respPerg['success']) {
                    $msg = $respPerg['data']['mensagem'] ?? 'erro desconhecido';
                    if (!empty($respPerg['data']['erros'])) {
                        $msg .= ' (' . implode('; ', array_map(fn ($e) => implode(', ', $e), $respPerg['data']['erros'])) . ')';
                    }
                    $linhas[] = ['linha' => $numeroLinha, 'ok' => false, 'mensagem' => 'Erro ao criar pergunta: ' . $msg];
                    $totalErro++;
                    continue;
                }

                if (!empty($respPerg['data']['dados']['versionado']) && $respPerg['data']['dados']['formulario_atual_id'] != $formularioAtualId) {
                    $formularioAtualId = $respPerg['data']['dados']['formulario_atual_id'];
                    $formularioVersionadoDurante = true;
                }

                $linhas[] = ['linha' => $numeroLinha, 'ok' => true, 'mensagem' => 'Pergunta importada em "' . $nomeCategoria . ' → ' . $nomeSubcategoria . '".'];
                $totalOk++;
            }
        }

        fclose($handle);
    }
} else {
    $linhas[] = ['linha' => 0, 'ok' => false, 'mensagem' => 'Nenhum arquivo válido foi enviado.'];
}
?>

<div class="pagetitle">
  <h1>Resultado da Importação</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
      <li class="breadcrumb-item"><a href="?paginas=formularios">Formulários</a></li>
      <li class="breadcrumb-item"><a href="?paginas=formulario-estrutura&id=<?php echo (int)$formularioId; ?>">Estrutura</a></li>
      <li class="breadcrumb-item active">Importação CSV</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="alert <?php echo $totalErro > 0 ? 'alert-warning' : 'alert-success'; ?>">
    <i class="bi bi-<?php echo $totalErro > 0 ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
    <strong><?php echo $totalOk; ?></strong> pergunta(s) importada(s) com sucesso.
    <?php if ($totalErro > 0): ?> <strong><?php echo $totalErro; ?></strong> linha(s) com erro.<?php endif; ?>
  </div>

  <?php if ($formularioVersionadoDurante): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      Este formulário já estava vinculado a uma campanha encerrada — uma <strong>nova versão</strong> foi criada automaticamente durante a importação.
      A estrutura importada está na nova versão.
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Detalhes por linha</div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th style="width:80px">Linha</th><th>Resultado</th></tr></thead>
        <tbody>
          <?php foreach ($linhas as $l): ?>
            <tr class="<?php echo $l['ok'] ? '' : 'table-danger'; ?>">
              <td><?php echo (int)$l['linha']; ?></td>
              <td>
                <i class="bi <?php echo $l['ok'] ? 'bi-check-circle text-success' : 'bi-x-circle text-danger'; ?> me-1"></i>
                <?php echo htmlspecialchars($l['mensagem']); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <a href="?paginas=formulario-estrutura&id=<?php echo (int)$formularioAtualId; ?>" class="btn btn-primary mt-3">
    <i class="bi bi-arrow-left me-1"></i> Voltar para a estrutura do formulário
  </a>
</section>
