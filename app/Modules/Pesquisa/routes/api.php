<?php

use App\Modules\Pesquisa\Http\Controllers\CategoriaController;
use App\Modules\Pesquisa\Http\Controllers\ColaboradorController;
use App\Modules\Pesquisa\Http\Controllers\ConceitoController;
use App\Modules\Pesquisa\Http\Controllers\ConceitoItemController;
use App\Modules\Pesquisa\Http\Controllers\ConviteController;
use App\Modules\Pesquisa\Http\Controllers\FormularioController;
use App\Modules\Pesquisa\Http\Controllers\GheController;
use App\Modules\Pesquisa\Http\Controllers\PerguntaController;
use App\Modules\Pesquisa\Http\Controllers\PesquisaController;
use App\Modules\Pesquisa\Http\Controllers\PlanoAcaoController;
use App\Modules\Pesquisa\Http\Controllers\RelatorioTecnicoController;
use App\Modules\Pesquisa\Http\Controllers\RespostaPublicaController;
use App\Modules\Pesquisa\Http\Controllers\ResultadoController;
use App\Modules\Pesquisa\Http\Controllers\SetorController;
use App\Modules\Pesquisa\Http\Controllers\SubcategoriaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo "Gestão de Pesquisas Psicossociais" — Fase 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1/pesquisa-psicossocial')->middleware(['auth.jwt', 'tenant', 'log.api'])->group(function () {

    // -------------------------------------------------------------------------
    // Formularios
    // -------------------------------------------------------------------------
    Route::prefix('formularios')->group(function () {
        Route::get('/', [FormularioController::class, 'index'])
            ->middleware('permission:formulario.listar');
        Route::post('/', [FormularioController::class, 'store'])
            ->middleware('permission:formulario.criar');
        Route::get('/{id}', [FormularioController::class, 'show'])
            ->middleware('permission:formulario.visualizar');
        Route::put('/{id}', [FormularioController::class, 'update'])
            ->middleware('permission:formulario.editar');
        Route::delete('/{id}', [FormularioController::class, 'destroy'])
            ->middleware('permission:formulario.excluir');
        Route::patch('/{id}/publicar', [FormularioController::class, 'publicar'])
            ->middleware('permission:formulario.editar');
        Route::patch('/{id}/arquivar', [FormularioController::class, 'arquivar'])
            ->middleware('permission:formulario.editar');
        Route::post('/{id}/nova-versao', [FormularioController::class, 'novaVersao'])
            ->middleware('permission:formulario.versionar');
        Route::get('/{id}/versoes', [FormularioController::class, 'versoes'])
            ->middleware('permission:formulario.visualizar');
        Route::get('/{id}/estrutura', [FormularioController::class, 'estrutura'])
            ->middleware('permission:formulario.visualizar');

        Route::prefix('/{formulario}/categorias')->group(function () {
            Route::get('/', [CategoriaController::class, 'index'])
                ->middleware('permission:categoria.listar');
            Route::post('/', [CategoriaController::class, 'store'])
                ->middleware('permission:categoria.criar');
            Route::patch('/reordenar', [CategoriaController::class, 'reordenar'])
                ->middleware('permission:categoria.editar');
        });
    });

    // -------------------------------------------------------------------------
    // Categorias
    // -------------------------------------------------------------------------
    Route::prefix('categorias/{id}')->group(function () {
        Route::get('/', [CategoriaController::class, 'show'])
            ->middleware('permission:categoria.visualizar');
        Route::put('/', [CategoriaController::class, 'update'])
            ->middleware('permission:categoria.editar');
        Route::delete('/', [CategoriaController::class, 'destroy'])
            ->middleware('permission:categoria.excluir');
    });

    Route::prefix('categorias/{categoria}/subcategorias')->group(function () {
        Route::get('/', [SubcategoriaController::class, 'index'])
            ->middleware('permission:subcategoria.listar');
        Route::post('/', [SubcategoriaController::class, 'store'])
            ->middleware('permission:subcategoria.criar');
        Route::patch('/reordenar', [SubcategoriaController::class, 'reordenar'])
            ->middleware('permission:subcategoria.editar');
    });

    // -------------------------------------------------------------------------
    // Subcategorias
    // -------------------------------------------------------------------------
    Route::prefix('subcategorias/{id}')->group(function () {
        Route::get('/', [SubcategoriaController::class, 'show'])
            ->middleware('permission:subcategoria.visualizar');
        Route::put('/', [SubcategoriaController::class, 'update'])
            ->middleware('permission:subcategoria.editar');
        Route::delete('/', [SubcategoriaController::class, 'destroy'])
            ->middleware('permission:subcategoria.excluir');
    });

    Route::prefix('subcategorias/{subcategoria}/perguntas')->group(function () {
        Route::get('/', [PerguntaController::class, 'index'])
            ->middleware('permission:pergunta.listar');
        Route::post('/', [PerguntaController::class, 'store'])
            ->middleware('permission:pergunta.criar');
        Route::patch('/reordenar', [PerguntaController::class, 'reordenar'])
            ->middleware('permission:pergunta.editar');
    });

    // -------------------------------------------------------------------------
    // Perguntas
    // -------------------------------------------------------------------------
    Route::prefix('perguntas/{id}')->group(function () {
        Route::get('/', [PerguntaController::class, 'show'])
            ->middleware('permission:pergunta.visualizar');
        Route::put('/', [PerguntaController::class, 'update'])
            ->middleware('permission:pergunta.editar');
        Route::delete('/', [PerguntaController::class, 'destroy'])
            ->middleware('permission:pergunta.excluir');
    });

    // -------------------------------------------------------------------------
    // Conceitos
    // -------------------------------------------------------------------------
    Route::prefix('conceitos')->group(function () {
        Route::get('/', [ConceitoController::class, 'index'])
            ->middleware('permission:conceito.listar');
        Route::post('/', [ConceitoController::class, 'store'])
            ->middleware('permission:conceito.criar');
        Route::get('/{id}', [ConceitoController::class, 'show'])
            ->middleware('permission:conceito.visualizar');
        Route::put('/{id}', [ConceitoController::class, 'update'])
            ->middleware('permission:conceito.editar');
        Route::delete('/{id}', [ConceitoController::class, 'destroy'])
            ->middleware('permission:conceito.excluir');

        Route::post('/{conceito}/itens', [ConceitoItemController::class, 'store'])
            ->middleware('permission:conceito.editar');
        Route::put('/{conceito}/itens/{itemId}', [ConceitoItemController::class, 'update'])
            ->middleware('permission:conceito.editar');
        Route::delete('/{conceito}/itens/{itemId}', [ConceitoItemController::class, 'destroy'])
            ->middleware('permission:conceito.editar');
        Route::patch('/{conceito}/itens/reordenar', [ConceitoItemController::class, 'reordenar'])
            ->middleware('permission:conceito.editar');
    });

    // -------------------------------------------------------------------------
    // Pesquisas (campanhas) — recorte funcional da Fase 2
    // -------------------------------------------------------------------------
    Route::prefix('pesquisas')->group(function () {
        Route::get('/', [PesquisaController::class, 'index'])
            ->middleware('permission:pesquisa.listar');
        Route::post('/', [PesquisaController::class, 'store'])
            ->middleware('permission:pesquisa.criar');
        Route::get('/{id}', [PesquisaController::class, 'show'])
            ->middleware('permission:pesquisa.visualizar');
        Route::put('/{id}', [PesquisaController::class, 'update'])
            ->middleware('permission:pesquisa.editar');
        Route::delete('/{id}', [PesquisaController::class, 'destroy'])
            ->middleware('permission:pesquisa.excluir');
        Route::delete('/{id}/definitivo', [PesquisaController::class, 'excluirDefinitivo'])
            ->middleware('permission:pesquisa.excluir_definitivo');
        Route::post('/{id}/publico', [PesquisaController::class, 'definirPublico'])
            ->middleware('permission:pesquisa.editar');
        Route::patch('/{id}/publicar', [PesquisaController::class, 'publicar'])
            ->middleware('permission:pesquisa.publicar');
        Route::patch('/{id}/encerrar', [PesquisaController::class, 'encerrar'])
            ->middleware('permission:pesquisa.encerrar');

        Route::get('/{pesquisa}/convites', [ConviteController::class, 'index'])
            ->middleware('permission:pesquisa.visualizar');
        Route::get('/{pesquisa}/resultados', [ResultadoController::class, 'show'])
            ->middleware('permission:resultado.consultar');

        Route::get('/{pesquisa}/plano-acao', [PlanoAcaoController::class, 'index'])
            ->middleware('permission:resultado.consultar');
        Route::post('/{pesquisa}/plano-acao/gerar', [PlanoAcaoController::class, 'gerar'])
            ->middleware('permission:plano_acao.gerar');

        Route::get('/{pesquisa}/relatorios-tecnicos', [RelatorioTecnicoController::class, 'porEmpresa'])
            ->middleware('permission:relatorio.listar');
        Route::post('/{pesquisa}/relatorios-tecnicos', [RelatorioTecnicoController::class, 'gerar'])
            ->middleware('permission:relatorio.gerar');
    });

    // -------------------------------------------------------------------------
    // Plano de ação — atualização de uma ação isolada e ciclo PDCA
    // -------------------------------------------------------------------------
    Route::patch('/plano-acao/{id}', [PlanoAcaoController::class, 'update'])
        ->middleware('permission:plano_acao.editar');
    Route::patch('/plano-acao/{id}/avancar-fase', [PlanoAcaoController::class, 'avancarFase'])
        ->middleware('permission:plano_acao.editar');
    Route::patch('/plano-acao/{id}/concluir-ciclo', [PlanoAcaoController::class, 'concluirCiclo'])
        ->middleware('permission:plano_acao.editar');

    // -------------------------------------------------------------------------
    // Setores e GHE (Grupos Homogêneos de Exposição)
    // -------------------------------------------------------------------------
    Route::prefix('setores')->group(function () {
        Route::get('/', [SetorController::class, 'index'])->middleware('permission:setor.listar');
        Route::post('/', [SetorController::class, 'store'])->middleware('permission:setor.criar');
        Route::get('/{id}', [SetorController::class, 'show'])->middleware('permission:setor.listar');
        Route::put('/{id}', [SetorController::class, 'update'])->middleware('permission:setor.editar');
        Route::delete('/{id}', [SetorController::class, 'destroy'])->middleware('permission:setor.excluir');
        Route::post('/{id}/usuario', [SetorController::class, 'definirUsuario'])->middleware('permission:setor.editar');
    });

    Route::prefix('ghes')->group(function () {
        Route::get('/', [GheController::class, 'index'])->middleware('permission:ghe.listar');
        Route::post('/', [GheController::class, 'store'])->middleware('permission:ghe.criar');
        Route::get('/{id}', [GheController::class, 'show'])->middleware('permission:ghe.listar');
        Route::put('/{id}', [GheController::class, 'update'])->middleware('permission:ghe.editar');
        Route::delete('/{id}', [GheController::class, 'destroy'])->middleware('permission:ghe.excluir');
    });

    // -------------------------------------------------------------------------
    // Colaboradores — alvo do convite individual (dados sensíveis sob LGPD)
    // -------------------------------------------------------------------------
    Route::prefix('colaboradores')->group(function () {
        Route::get('/', [ColaboradorController::class, 'index'])->middleware('permission:colaborador.listar');
        Route::post('/', [ColaboradorController::class, 'store'])->middleware('permission:colaborador.criar');
        Route::post('/importar', [ColaboradorController::class, 'importar'])->middleware('permission:colaborador.importar');
        Route::get('/{id}', [ColaboradorController::class, 'show'])->middleware('permission:colaborador.listar');
        Route::put('/{id}', [ColaboradorController::class, 'update'])->middleware('permission:colaborador.editar');
        Route::delete('/{id}', [ColaboradorController::class, 'destroy'])->middleware('permission:colaborador.excluir');
        Route::patch('/{id}/anonimizar', [ColaboradorController::class, 'anonimizar'])->middleware('permission:colaborador.excluir');
        Route::get('/{id}/dados-sensiveis', [ColaboradorController::class, 'dadosSensiveis'])
            ->middleware('permission:colaborador.visualizar_dados_sensiveis');
    });

    // -------------------------------------------------------------------------
    // Relatórios técnicos — download individual e gestão cross-empresa
    // (super administrador)
    // -------------------------------------------------------------------------
    Route::get('/relatorios-tecnicos', [RelatorioTecnicoController::class, 'todas'])
        ->middleware('permission:relatorio.listar_todas');
    Route::get('/relatorios-tecnicos/{id}/download', [RelatorioTecnicoController::class, 'download'])
        ->middleware('permission:relatorio.listar');
});

// -----------------------------------------------------------------------------
// Resposta pública (sem autenticação) — acessada via link individual enviado
// a cada colaborador convidado. Sem auth.jwt/tenant/permission/log.api: não
// há usuário autenticado nem contexto de empresa nesta rota.
// -----------------------------------------------------------------------------
Route::prefix('v1/pesquisa-psicossocial/publico')->middleware('throttle:60,1')->group(function () {
    Route::get('/global/{token}', [RespostaPublicaController::class, 'showGlobal']);
    Route::post('/global/{token}/respostas', [RespostaPublicaController::class, 'storeGlobal']);

    Route::get('/{token}', [RespostaPublicaController::class, 'show']);
    Route::post('/{token}/respostas', [RespostaPublicaController::class, 'store']);
});
