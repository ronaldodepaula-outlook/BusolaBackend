<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\DTOs\PesquisaData;
use App\Modules\Pesquisa\Http\Requests\Pesquisa\DefinirPublicoRequest;
use App\Modules\Pesquisa\Http\Requests\Pesquisa\StorePesquisaRequest;
use App\Modules\Pesquisa\Http\Requests\Pesquisa\UpdatePesquisaRequest;
use App\Modules\Pesquisa\Http\Resources\PesquisaResource;
use App\Modules\Pesquisa\Services\PesquisaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PesquisaController extends PesquisaBaseController
{
    public function __construct(private readonly PesquisaService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/pesquisas',
        summary: 'Listar campanhas de pesquisa',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['rascunho', 'ativa', 'encerrada', 'cancelada']))],
        responses: [new OA\Response(response: 200, description: 'Lista paginada de campanhas')]
    )]
    public function index(Request $request): JsonResponse
    {
        $pesquisas = $this->service->listar($request->only(['status', 'empresa_id']), $request->auth_user);

        return $this->respostaSucesso($pesquisas);
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/pesquisas',
        summary: 'Criar campanha (rascunho)',
        description: 'O formulário deve estar publicado e vigente.',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Campanha criada em rascunho')]
    )]
    public function store(StorePesquisaRequest $request): JsonResponse
    {
        $pesquisa = $this->service->criar(PesquisaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso(new PesquisaResource($pesquisa), 'Campanha criada com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{id}',
        summary: 'Visualizar campanha',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Dados da campanha')]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $pesquisa = $this->service->buscar($id, $request->auth_user);

        return $this->respostaSucesso(new PesquisaResource($pesquisa));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{id}',
        summary: 'Atualizar dados da campanha',
        description: 'Só é permitido enquanto a campanha está em rascunho.',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Campanha atualizada')]
    )]
    public function update(UpdatePesquisaRequest $request, int $id): JsonResponse
    {
        $pesquisa = $this->service->atualizar($id, PesquisaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso(new PesquisaResource($pesquisa), 'Campanha atualizada com sucesso.');
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{id}/publico',
        summary: 'Definir público-alvo da campanha',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['tipo'], properties: [
                new OA\Property(property: 'tipo', type: 'string', enum: ['toda_empresa', 'filiais', 'colaboradores']),
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
            ])
        ),
        responses: [new OA\Response(response: 200, description: 'Público-alvo definido')]
    )]
    public function definirPublico(DefinirPublicoRequest $request, int $id): JsonResponse
    {
        $pesquisa = $this->service->definirPublico(
            $id,
            $request->validated('tipo'),
            $request->validated('ids') ?? [],
            $request->authUser()
        );

        return $this->respostaSucesso(new PesquisaResource($pesquisa), 'Público-alvo definido com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{id}/publicar',
        summary: 'Publicar campanha (rascunho -> ativa)',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Campanha publicada')]
    )]
    public function publicar(Request $request, int $id): JsonResponse
    {
        $pesquisa = $this->service->publicar($id, $request->auth_user);

        return $this->respostaSucesso(new PesquisaResource($pesquisa), 'Campanha publicada com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{id}/encerrar',
        summary: 'Encerrar campanha (ativa -> encerrada)',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Campanha encerrada')]
    )]
    public function encerrar(Request $request, int $id): JsonResponse
    {
        $pesquisa = $this->service->encerrar($id, $request->auth_user);

        return $this->respostaSucesso(new PesquisaResource($pesquisa), 'Campanha encerrada com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{id}',
        summary: 'Excluir campanha (somente rascunho)',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Campanha excluída')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Campanha removida com sucesso.');
    }
}
