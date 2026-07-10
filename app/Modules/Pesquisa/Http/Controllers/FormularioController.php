<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\Formulario\StoreFormularioRequest;
use App\Modules\Pesquisa\Http\Requests\Formulario\UpdateFormularioRequest;
use App\Modules\Pesquisa\Http\Resources\FormularioResource;
use App\Modules\Pesquisa\Http\Resources\FormularioResumoResource;
use App\Modules\Pesquisa\DTOs\FormularioData;
use App\Modules\Pesquisa\Services\FormularioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FormularioController extends PesquisaBaseController
{
    public function __construct(private readonly FormularioService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/formularios',
        summary: 'Listar formulários',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['rascunho', 'publicado', 'arquivado'])),
            new OA\Parameter(name: 'tipo', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['global', 'empresa'])),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista paginada de formulários')]
    )]
    public function index(Request $request): JsonResponse
    {
        $formularios = $this->service->listar($request->only(['status', 'tipo', 'search', 'empresa_id']), $request->auth_user);

        // A paginação (total/last_page/current_page) precisa continuar vindo do
        // paginator; só os itens passam pela Resource, para expor campos
        // computados (ex.: total_categorias, padrao_formulario_nome) que não
        // existem como coluna crua do model.
        $formularios->setCollection(
            $formularios->getCollection()->map(fn ($f) => (new FormularioResource($f))->resolve($request))
        );

        return $this->respostaSucesso($formularios);
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/formularios',
        summary: 'Criar formulário',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Formulário criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaFormulario')])),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function store(StoreFormularioRequest $request): JsonResponse
    {
        $formulario = $this->service->criar(FormularioData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso(new FormularioResource($formulario), 'Formulário criado com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}',
        summary: 'Visualizar formulário',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Dados do formulário')]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $formulario = $this->service->buscar($id, $request->auth_user);

        return $this->respostaSucesso(new FormularioResource($formulario));
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}/estrutura',
        summary: 'Visualizar a árvore completa do formulário (categorias, subcategorias, perguntas e conceitos)',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Estrutura completa do formulário')]
    )]
    public function estrutura(Request $request, int $id): JsonResponse
    {
        $formulario = $this->service->estrutura($id, $request->auth_user);

        return $this->respostaSucesso(new FormularioResource($formulario));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}',
        summary: 'Atualizar formulário',
        description: 'Se o formulário já estiver vinculado a uma pesquisa encerrada, uma nova versão é criada automaticamente.',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Formulário atualizado')]
    )]
    public function update(UpdateFormularioRequest $request, int $id): JsonResponse
    {
        $resultado = $this->service->atualizar($id, FormularioData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso([
            'formulario'          => new FormularioResource($resultado['formulario']),
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario']->id,
        ], 'Formulário atualizado com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}',
        summary: 'Excluir formulário',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Formulário excluído')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Formulário removido com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}/publicar',
        summary: 'Publicar formulário',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Formulário publicado')]
    )]
    public function publicar(Request $request, int $id): JsonResponse
    {
        $formulario = $this->service->publicar($id, $request->auth_user);

        return $this->respostaSucesso(new FormularioResource($formulario), 'Formulário publicado com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}/arquivar',
        summary: 'Arquivar formulário',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Formulário arquivado')]
    )]
    public function arquivar(Request $request, int $id): JsonResponse
    {
        $formulario = $this->service->arquivar($id, $request->auth_user);

        return $this->respostaSucesso(new FormularioResource($formulario), 'Formulário arquivado com sucesso.');
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}/nova-versao',
        summary: 'Forçar criação de uma nova versão do formulário',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Nova versão criada')]
    )]
    public function novaVersao(Request $request, int $id): JsonResponse
    {
        $formulario = $this->service->novaVersaoManual($id, $request->auth_user);

        return $this->respostaSucesso(new FormularioResource($formulario), 'Nova versão criada com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/formularios/{id}/versoes',
        summary: 'Listar todas as versões do grupo de um formulário',
        tags: ['Pesquisa Psicossocial - Formulários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Lista de versões')]
    )]
    public function versoes(Request $request, int $id): JsonResponse
    {
        $versoes = $this->service->listarVersoes($id, $request->auth_user);

        return $this->respostaSucesso(FormularioResumoResource::collection($versoes));
    }
}
