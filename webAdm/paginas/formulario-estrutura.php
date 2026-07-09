<?php
if (!Auth::hasPermission('formulario.visualizar')) {
    echo '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Você não tem permissão para acessar esta tela.</div>';
    return;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ?paginas=formularios');
    exit;
}

$api  = new ApiClient(Auth::getToken());
$resp = $api->get("pesquisa-psicossocial/formularios/{$id}/estrutura");

if (!$resp['success']) {
    echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' .
         htmlspecialchars($resp['data']['mensagem'] ?? 'Formulário não encontrado.') .
         ' <a href="?paginas=formularios">Voltar para a lista</a></div>';
    return;
}

$formulario = $resp['data']['dados'];
$categorias = $formulario['categorias'] ?? [];
$ativo      = (bool)($formulario['ativo'] ?? true);

$respConceitos = $api->get('pesquisa-psicossocial/conceitos', ['per_page' => 200]);
$conceitos     = $respConceitos['data']['dados']['data'] ?? [];

$podeCategoriaCriar   = Auth::hasPermission('categoria.criar');
$podeCategoriaEditar  = Auth::hasPermission('categoria.editar');
$podeCategoriaExcluir = Auth::hasPermission('categoria.excluir');
$podeSubCriar         = Auth::hasPermission('subcategoria.criar');
$podeSubEditar        = Auth::hasPermission('subcategoria.editar');
$podeSubExcluir       = Auth::hasPermission('subcategoria.excluir');
$podePerguntaCriar    = Auth::hasPermission('pergunta.criar');
$podePerguntaEditar   = Auth::hasPermission('pergunta.editar');
$podePerguntaExcluir  = Auth::hasPermission('pergunta.excluir');

$tipoPerguntaLabels = [
    'escala'            => ['Escala', 'bg-primary'],
    'texto'             => ['Texto', 'bg-secondary'],
    'numero'            => ['Número', 'bg-secondary'],
    'data'              => ['Data', 'bg-secondary'],
    'sim_nao'           => ['Sim/Não', 'bg-info text-dark'],
    'multipla_escolha'  => ['Múltipla Escolha', 'bg-warning text-dark'],
    'unica_escolha'     => ['Única Escolha', 'bg-warning text-dark'],
];

$statusBadge = match ($formulario['status'] ?? '') {
    'rascunho'  => 'bg-secondary',
    'publicado' => 'bg-success',
    'arquivado' => 'bg-dark',
    default     => 'bg-light text-dark',
};
?>

<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <h1><?php echo htmlspecialchars($formulario['nome']); ?></h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?paginas=home">Início</a></li>
        <li class="breadcrumb-item"><a href="?paginas=formularios">Formulários</a></li>
        <li class="breadcrumb-item active">Estrutura</li>
      </ol>
    </nav>
  </div>
  <a href="?paginas=formularios" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>

<section class="section">

  <?php if (!$ativo): ?>
    <div class="alert alert-secondary d-flex align-items-center">
      <i class="bi bi-archive me-2 fs-5"></i>
      <div>Esta é uma <strong>versão histórica</strong> (arquivada) deste formulário — somente leitura. Para editar, acesse a versão vigente na lista de Formulários.</div>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body d-flex align-items-center flex-wrap gap-3">
      <div><span class="text-muted">Código:</span> <code><?php echo htmlspecialchars($formulario['codigo']); ?></code></div>
      <div><span class="text-muted">Status:</span> <span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($formulario['status']); ?></span></div>
      <div><span class="text-muted">Versão:</span> v<?php echo (int)$formulario['versao']; ?></div>
      <div><span class="text-muted">Tipo:</span> <?php echo $formulario['tipo'] === 'global' ? '<span class="badge bg-info text-dark">Global</span>' : 'Empresa'; ?></div>
      <?php if ($ativo && $podeCategoriaCriar): ?>
        <button class="btn btn-outline-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#modalImportarCsv">
          <i class="bi bi-file-earmark-arrow-up me-1"></i> Importar via CSV
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <h5 class="mb-0">Categorias</h5>
    <?php if ($ativo && $podeCategoriaCriar): ?>
      <button class="btn btn-primary btn-sm" onclick="abrirModalCategoria()"><i class="bi bi-plus-circle me-1"></i> Nova Categoria</button>
    <?php endif; ?>
  </div>

  <?php if (empty($categorias)): ?>
    <div class="alert alert-light border text-center py-4">
      <i class="bi bi-diagram-3 fs-2 text-muted d-block mb-2"></i>
      Nenhuma categoria cadastrada ainda. Comece criando a primeira categoria ou importe uma estrutura via CSV.
    </div>
  <?php endif; ?>

  <div id="categorias-container">
    <?php foreach ($categorias as $cat): ?>
      <div class="card mb-2" data-id="<?php echo (int)$cat['id']; ?>">
        <div class="card-header d-flex align-items-center gap-2">
          <?php if ($ativo): ?><i class="bi bi-grip-vertical drag-handle-categoria text-muted" style="cursor:grab"></i><?php endif; ?>
          <button class="btn btn-sm btn-link text-decoration-none text-dark fw-semibold p-0" type="button"
                  data-bs-toggle="collapse" data-bs-target="#cat-body-<?php echo (int)$cat['id']; ?>">
            <i class="bi bi-chevron-down me-1"></i><?php echo htmlspecialchars($cat['nome']); ?>
          </button>
          <span class="badge bg-secondary"><?php echo count($cat['subcategorias'] ?? []); ?> subcategoria(s)</span>
          <?php if ($ativo): ?>
          <div class="ms-auto d-flex gap-1">
            <?php if ($podeSubCriar): ?>
              <button class="btn btn-sm btn-outline-success" title="Nova subcategoria"
                      onclick="abrirModalSubcategoria(<?php echo (int)$cat['id']; ?>)"><i class="bi bi-plus-lg"></i></button>
            <?php endif; ?>
            <?php if ($podeCategoriaEditar): ?>
              <button class="btn btn-sm btn-outline-secondary" title="Editar categoria"
                      onclick='editarCategoria(<?php echo json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
            <?php endif; ?>
            <?php if ($podeCategoriaExcluir): ?>
              <button class="btn btn-sm btn-outline-danger" title="Excluir categoria"
                      onclick="deletarCategoria(<?php echo (int)$cat['id']; ?>, '<?php echo addslashes($cat['nome']); ?>')"><i class="bi bi-trash"></i></button>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="collapse show" id="cat-body-<?php echo (int)$cat['id']; ?>">
          <div class="card-body">
            <div class="subcategorias-container" data-categoria-id="<?php echo (int)$cat['id']; ?>">
              <?php foreach (($cat['subcategorias'] ?? []) as $sub): ?>
                <div class="card mb-2 bg-light-subtle" data-id="<?php echo (int)$sub['id']; ?>">
                  <div class="card-header d-flex align-items-center gap-2 py-2">
                    <?php if ($ativo): ?><i class="bi bi-grip-vertical drag-handle-subcategoria text-muted" style="cursor:grab"></i><?php endif; ?>
                    <button class="btn btn-sm btn-link text-decoration-none text-dark p-0" type="button"
                            data-bs-toggle="collapse" data-bs-target="#sub-body-<?php echo (int)$sub['id']; ?>">
                      <i class="bi bi-chevron-down me-1"></i><?php echo htmlspecialchars($sub['nome']); ?>
                    </button>
                    <span class="badge bg-secondary"><?php echo count($sub['perguntas'] ?? []); ?> pergunta(s)</span>
                    <?php if ($ativo): ?>
                    <div class="ms-auto d-flex gap-1">
                      <?php if ($podePerguntaCriar): ?>
                        <button class="btn btn-sm btn-outline-success" title="Nova pergunta"
                                onclick="abrirModalPergunta(<?php echo (int)$sub['id']; ?>)"><i class="bi bi-plus-lg"></i></button>
                      <?php endif; ?>
                      <?php if ($podeSubEditar): ?>
                        <button class="btn btn-sm btn-outline-secondary" title="Editar subcategoria"
                                onclick='editarSubcategoria(<?php echo json_encode($sub, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
                      <?php endif; ?>
                      <?php if ($podeSubExcluir): ?>
                        <button class="btn btn-sm btn-outline-danger" title="Excluir subcategoria"
                                onclick="deletarSubcategoria(<?php echo (int)$sub['id']; ?>, '<?php echo addslashes($sub['nome']); ?>')"><i class="bi bi-trash"></i></button>
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="collapse show" id="sub-body-<?php echo (int)$sub['id']; ?>">
                    <div class="card-body py-2">
                      <ul class="list-group perguntas-container" data-subcategoria-id="<?php echo (int)$sub['id']; ?>">
                        <?php foreach (($sub['perguntas'] ?? []) as $perg): ?>
                          <?php [$tipoLabel, $tipoBadge] = $tipoPerguntaLabels[$perg['tipo_pergunta']] ?? [$perg['tipo_pergunta'], 'bg-secondary']; ?>
                          <li class="list-group-item d-flex align-items-center gap-2" data-id="<?php echo (int)$perg['id']; ?>">
                            <?php if ($ativo): ?><i class="bi bi-grip-vertical drag-handle-pergunta text-muted" style="cursor:grab"></i><?php endif; ?>
                            <span class="badge <?php echo $tipoBadge; ?>"><?php echo $tipoLabel; ?></span>
                            <?php if (!empty($perg['obrigatoria'])): ?><span class="text-danger" title="Obrigatória">*</span><?php endif; ?>
                            <span class="flex-grow-1"><?php echo htmlspecialchars($perg['texto']); ?></span>
                            <?php if (!empty($perg['conceito'])): ?>
                              <span class="badge bg-light text-dark border"><i class="bi bi-rulers me-1"></i><?php echo htmlspecialchars($perg['conceito']['nome']); ?></span>
                            <?php endif; ?>
                            <?php if ($ativo): ?>
                            <div class="d-flex gap-1">
                              <?php if ($podePerguntaEditar): ?>
                                <button class="btn btn-sm btn-outline-secondary" title="Editar"
                                        onclick='editarPergunta(<?php echo json_encode($perg, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil"></i></button>
                              <?php endif; ?>
                              <?php if ($podePerguntaExcluir): ?>
                                <button class="btn btn-sm btn-outline-danger" title="Excluir"
                                        onclick="deletarPergunta(<?php echo (int)$perg['id']; ?>)"><i class="bi bi-trash"></i></button>
                              <?php endif; ?>
                            </div>
                            <?php endif; ?>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Modal Categoria -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloModalCategoria">Nova Categoria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCategoria">
          <input type="hidden" name="id" id="catId">
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="nome" id="catNome" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" id="catDescricao" class="form-control" rows="2"></textarea>
          </div>
          <hr>
          <div class="mb-3">
            <label class="form-label">Fator de risco oficial (COPSOQ II)</label>
            <select name="categoria_referencia" id="catReferencia" class="form-select" onchange="atualizarSeveridadePadrao()">
              <option value="">— Categoria livre (sem classificação de risco) —</option>
              <option value="Gestão Organizacional" data-sev="3">Gestão Organizacional (S3)</option>
              <option value="Contexto da Organização do Trabalho" data-sev="3">Contexto da Organização do Trabalho (S3)</option>
              <option value="Relações Sociais no Trabalho" data-sev="3">Relações Sociais no Trabalho (S3)</option>
              <option value="Conteúdo das Tarefas" data-sev="3">Conteúdo das Tarefas do Trabalho (S3)</option>
              <option value="Condições do Ambiente de Trabalho" data-sev="2">Condições do Ambiente de Trabalho (S2)</option>
              <option value="Interação Pessoa–Tarefa" data-sev="2">Interação Pessoa–Tarefa (S2)</option>
              <option value="Jornada de Trabalho" data-sev="4">Jornada de Trabalho (S4)</option>
              <option value="Violência e Assédio Moral/Sexual" data-sev="5">Violência e Assédio Moral/Sexual no Trabalho (S5)</option>
              <option value="Discriminação" data-sev="4">Discriminação no Trabalho (S4)</option>
              <option value="Fatores Psicossociais Relacionados a Risco de Morte e Trauma" data-sev="5">Risco de Morte e Trauma no Trabalho (S5)</option>
              <option value="Insegurança no Emprego" data-sev="3">Desemprego / Insegurança Ocupacional (S3)</option>
            </select>
            <small class="text-muted">Vincula a categoria a um fator de risco oficial para habilitar a classificação de risco (Probabilidade × Severidade) nos resultados.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Severidade fixa (1-5)</label>
            <input type="number" name="severidade" id="catSeveridade" class="form-control" min="1" max="5">
            <small class="text-muted">Preenchida automaticamente pela referência oficial; pode ser ajustada manualmente se necessário.</small>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarCategoria(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Subcategoria -->
<div class="modal fade" id="modalSubcategoria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloModalSubcategoria">Nova Subcategoria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formSubcategoria">
          <input type="hidden" name="id" id="subId">
          <input type="hidden" id="subCategoriaId">
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" name="nome" id="subNome" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" id="subDescricao" class="form-control" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarSubcategoria(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Pergunta -->
<div class="modal fade" id="modalPergunta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloModalPergunta">Nova Pergunta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formPergunta">
          <input type="hidden" name="id" id="pergId">
          <input type="hidden" id="pergSubcategoriaId">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Texto da pergunta <span class="text-danger">*</span></label>
              <textarea name="texto" id="pergTexto" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo <span class="text-danger">*</span></label>
              <select name="tipo_pergunta" id="pergTipo" class="form-select" required onchange="toggleConceitoPergunta()">
                <option value="escala">Escala</option>
                <option value="texto">Texto</option>
                <option value="numero">Número</option>
                <option value="data">Data</option>
                <option value="sim_nao">Sim/Não</option>
                <option value="multipla_escolha">Múltipla Escolha</option>
                <option value="unica_escolha">Única Escolha</option>
              </select>
            </div>
            <div class="col-md-6" id="pergConceitoWrapper">
              <label class="form-label">Conceito de avaliação <span class="text-danger" id="pergConceitoObrigatorio">*</span></label>
              <select name="conceito_id" id="pergConceitoId" class="form-select">
                <option value="">Nenhum</option>
                <?php foreach ($conceitos as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?> (<?php echo htmlspecialchars($c['tipo']); ?>)</option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Obrigatório para os tipos Escala, Múltipla Escolha e Única Escolha.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Descrição / instrução adicional</label>
              <textarea name="descricao" id="pergDescricao" class="form-control" rows="1"></textarea>
            </div>
            <div class="col-12 d-flex gap-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="obrigatoria" id="pergObrigatoria" checked>
                <label class="form-check-label" for="pergObrigatoria">Obrigatória</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="permite_observacao" id="pergPermiteObs">
                <label class="form-check-label" for="pergPermiteObs">Permite observação</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="permite_anexo" id="pergPermiteAnexo">
                <label class="form-check-label" for="pergPermiteAnexo">Permite anexo</label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="salvarPergunta(this)"><i class="bi bi-save me-1"></i> Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Importar CSV -->
<div class="modal fade" id="modalImportarCsv" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?paginas=formulario-importar&id=<?php echo (int)$id; ?>" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i>Importar Estrutura via CSV</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Monte categorias, subcategorias e perguntas de uma vez a partir de um arquivo CSV. Categorias e subcategorias já existentes (mesmo nome) são reaproveitadas automaticamente.</p>
          <p>
            <a href="assets/templates/formulario_estrutura_template.csv" download><i class="bi bi-download me-1"></i>Baixar modelo de exemplo (.csv)</a>
          </p>
          <div class="alert alert-light border small mb-3">
            <strong>Colunas esperadas:</strong> <code>categoria, subcategoria, tipo_pergunta, texto, obrigatoria, permite_observacao, permite_anexo, conceito</code><br>
            <code>tipo_pergunta</code>: escala, texto, numero, data, sim_nao, multipla_escolha, unica_escolha.<br>
            <code>conceito</code>: nome de um conceito de avaliação já cadastrado (obrigatório para os tipos escala/múltipla escolha/única escolha).<br>
            <code>obrigatoria</code>, <code>permite_observacao</code>, <code>permite_anexo</code>: sim/não (ou 1/0).
          </div>
          <div class="mb-3">
            <label class="form-label">Arquivo CSV <span class="text-danger">*</span></label>
            <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Importar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const FORMULARIO_ID = <?php echo (int)$id; ?>;

function toggleConceitoPergunta() {
  const tipo = document.getElementById('pergTipo').value;
  const exige = ['escala', 'multipla_escolha', 'unica_escolha'].includes(tipo);
  document.getElementById('pergConceitoObrigatorio').style.display = exige ? '' : 'none';
}

function aposAcaoComVersionamento(res, mensagemSucesso) {
  if (!res.sucesso) {
    bslToast(res.mensagem || 'Ocorreu um erro.', 'danger');
    return;
  }
  const versionado = res.dados?.versionado;
  const novoId = res.dados?.formulario_atual_id;
  if (versionado && novoId) {
    bslToast('Formulário protegido por campanha encerrada: uma nova versão foi criada automaticamente.', 'warning');
    setTimeout(() => { window.location.href = '?paginas=formulario-estrutura&id=' + novoId; }, 1400);
  } else {
    bslToast(mensagemSucesso, 'success');
    setTimeout(() => location.reload(), 700);
  }
}

// ---- Categoria ----
function abrirModalCategoria() {
  document.getElementById('formCategoria').reset();
  document.getElementById('catId').value = '';
  document.getElementById('tituloModalCategoria').textContent = 'Nova Categoria';
  new bootstrap.Modal(document.getElementById('modalCategoria')).show();
}
function editarCategoria(cat) {
  document.getElementById('catId').value = cat.id;
  document.getElementById('catNome').value = cat.nome ?? '';
  document.getElementById('catDescricao').value = cat.descricao ?? '';
  document.getElementById('catReferencia').value = cat.categoria_referencia ?? '';
  document.getElementById('catSeveridade').value = cat.severidade ?? '';
  document.getElementById('tituloModalCategoria').textContent = 'Editar Categoria';
  new bootstrap.Modal(document.getElementById('modalCategoria')).show();
}
function atualizarSeveridadePadrao() {
  const select = document.getElementById('catReferencia');
  const opt = select.options[select.selectedIndex];
  const sev = opt ? opt.getAttribute('data-sev') : null;
  if (sev) document.getElementById('catSeveridade').value = sev;
}
async function salvarCategoria(btn) {
  bslSetLoading(btn, true);
  const id = document.getElementById('catId').value;
  const data = bslFormData('formCategoria');
  delete data.id;
  const res = id
    ? await apiFetch('PUT', 'pesquisa-psicossocial/categorias/' + id, data)
    : await apiFetch('POST', 'pesquisa-psicossocial/formularios/' + FORMULARIO_ID + '/categorias', data);
  bslSetLoading(btn, false);
  bslCloseModal('modalCategoria');
  aposAcaoComVersionamento(res, id ? 'Categoria atualizada.' : 'Categoria criada.');
}
async function deletarCategoria(id, nome) {
  if (!confirm('Excluir a categoria "' + nome + '" e todas as suas subcategorias/perguntas?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/categorias/' + id);
  aposAcaoComVersionamento(res, 'Categoria excluída.');
}

// ---- Subcategoria ----
function abrirModalSubcategoria(categoriaId) {
  document.getElementById('formSubcategoria').reset();
  document.getElementById('subId').value = '';
  document.getElementById('subCategoriaId').value = categoriaId;
  document.getElementById('tituloModalSubcategoria').textContent = 'Nova Subcategoria';
  new bootstrap.Modal(document.getElementById('modalSubcategoria')).show();
}
function editarSubcategoria(sub) {
  document.getElementById('subId').value = sub.id;
  document.getElementById('subCategoriaId').value = sub.categoria_id;
  document.getElementById('subNome').value = sub.nome ?? '';
  document.getElementById('subDescricao').value = sub.descricao ?? '';
  document.getElementById('tituloModalSubcategoria').textContent = 'Editar Subcategoria';
  new bootstrap.Modal(document.getElementById('modalSubcategoria')).show();
}
async function salvarSubcategoria(btn) {
  bslSetLoading(btn, true);
  const id = document.getElementById('subId').value;
  const categoriaId = document.getElementById('subCategoriaId').value;
  const data = bslFormData('formSubcategoria');
  delete data.id;
  const res = id
    ? await apiFetch('PUT', 'pesquisa-psicossocial/subcategorias/' + id, data)
    : await apiFetch('POST', 'pesquisa-psicossocial/categorias/' + categoriaId + '/subcategorias', data);
  bslSetLoading(btn, false);
  bslCloseModal('modalSubcategoria');
  aposAcaoComVersionamento(res, id ? 'Subcategoria atualizada.' : 'Subcategoria criada.');
}
async function deletarSubcategoria(id, nome) {
  if (!confirm('Excluir a subcategoria "' + nome + '" e todas as suas perguntas?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/subcategorias/' + id);
  aposAcaoComVersionamento(res, 'Subcategoria excluída.');
}

// ---- Pergunta ----
function abrirModalPergunta(subcategoriaId) {
  document.getElementById('formPergunta').reset();
  document.getElementById('pergId').value = '';
  document.getElementById('pergSubcategoriaId').value = subcategoriaId;
  document.getElementById('tituloModalPergunta').textContent = 'Nova Pergunta';
  toggleConceitoPergunta();
  new bootstrap.Modal(document.getElementById('modalPergunta')).show();
}
function editarPergunta(perg) {
  document.getElementById('pergId').value = perg.id;
  document.getElementById('pergSubcategoriaId').value = perg.subcategoria_id;
  document.getElementById('pergTexto').value = perg.texto ?? '';
  document.getElementById('pergTipo').value = perg.tipo_pergunta ?? 'texto';
  document.getElementById('pergConceitoId').value = perg.conceito_id ?? '';
  document.getElementById('pergDescricao').value = perg.descricao ?? '';
  document.getElementById('pergObrigatoria').checked = !!perg.obrigatoria;
  document.getElementById('pergPermiteObs').checked = !!perg.permite_observacao;
  document.getElementById('pergPermiteAnexo').checked = !!perg.permite_anexo;
  document.getElementById('tituloModalPergunta').textContent = 'Editar Pergunta';
  toggleConceitoPergunta();
  new bootstrap.Modal(document.getElementById('modalPergunta')).show();
}
async function salvarPergunta(btn) {
  bslSetLoading(btn, true);
  const id = document.getElementById('pergId').value;
  const subcategoriaId = document.getElementById('pergSubcategoriaId').value;
  const form = document.getElementById('formPergunta');
  const data = {
    texto: form.texto.value,
    tipo_pergunta: form.tipo_pergunta.value,
    conceito_id: form.conceito_id.value || null,
    descricao: form.descricao.value || null,
    obrigatoria: form.obrigatoria.checked,
    permite_observacao: form.permite_observacao.checked,
    permite_anexo: form.permite_anexo.checked,
  };
  const res = id
    ? await apiFetch('PUT', 'pesquisa-psicossocial/perguntas/' + id, data)
    : await apiFetch('POST', 'pesquisa-psicossocial/subcategorias/' + subcategoriaId + '/perguntas', data);
  bslSetLoading(btn, false);
  if (!res.sucesso) {
    bslToast(res.mensagem || 'Erro ao salvar pergunta.', 'danger');
    return;
  }
  bslCloseModal('modalPergunta');
  aposAcaoComVersionamento(res, id ? 'Pergunta atualizada.' : 'Pergunta criada.');
}
async function deletarPergunta(id) {
  if (!confirm('Excluir esta pergunta?')) return;
  const res = await apiFetch('DELETE', 'pesquisa-psicossocial/perguntas/' + id);
  aposAcaoComVersionamento(res, 'Pergunta excluída.');
}

// ---- Drag and drop ----
document.addEventListener('DOMContentLoaded', function () {
  const catContainer = document.getElementById('categorias-container');
  if (catContainer) {
    new Sortable(catContainer, {
      handle: '.drag-handle-categoria',
      animation: 150,
      onEnd: async function () {
        const ids = Array.from(catContainer.children).map(el => el.dataset.id);
        const res = await apiFetch('PATCH', 'pesquisa-psicossocial/formularios/' + FORMULARIO_ID + '/categorias/reordenar', { ids });
        aposAcaoComVersionamento(res, 'Ordem atualizada.');
      }
    });
  }

  document.querySelectorAll('.subcategorias-container').forEach(function (container) {
    new Sortable(container, {
      handle: '.drag-handle-subcategoria',
      animation: 150,
      onEnd: async function () {
        const ids = Array.from(container.children).map(el => el.dataset.id);
        const categoriaId = container.dataset.categoriaId;
        const res = await apiFetch('PATCH', 'pesquisa-psicossocial/categorias/' + categoriaId + '/subcategorias/reordenar', { ids });
        aposAcaoComVersionamento(res, 'Ordem atualizada.');
      }
    });
  });

  document.querySelectorAll('.perguntas-container').forEach(function (container) {
    new Sortable(container, {
      handle: '.drag-handle-pergunta',
      animation: 150,
      onEnd: async function () {
        const ids = Array.from(container.children).map(el => el.dataset.id);
        const subcategoriaId = container.dataset.subcategoriaId;
        const res = await apiFetch('PATCH', 'pesquisa-psicossocial/subcategorias/' + subcategoriaId + '/perguntas/reordenar', { ids });
        aposAcaoComVersionamento(res, 'Ordem atualizada.');
      }
    });
  });
});
</script>
