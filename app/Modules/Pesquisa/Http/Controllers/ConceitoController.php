<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\DTOs\ConceitoData;
use App\Modules\Pesquisa\Http\Requests\Conceito\StoreConceitoRequest;
use App\Modules\Pesquisa\Http\Requests\Conceito\UpdateConceitoRequest;
use App\Modules\Pesquisa\Http\Resources\ConceitoResource;
use App\Modules\Pesquisa\Services\ConceitoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConceitoController extends PesquisaBaseController
{
    public function __construct(private readonly ConceitoService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/conceitos',
        summary: 'Listar conceitos de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tipo', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['escala_likert', 'frequencia', 'numerica', 'personalizado'])),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista paginada de conceitos')]
    )]
    public function index(Request $request): JsonResponse
    {
        $conceitos = $this->service->listar($request->only(['tipo', 'search', 'empresa_id']), $request->auth_user);

        return $this->respostaSucesso($conceitos);
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/conceitos',
        summary: 'Criar conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Conceito criado')]
    )]
    public function store(StoreConceitoRequest $request): JsonResponse
    {
        $conceito = $this->service->criar(ConceitoData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso(new ConceitoResource($conceito), 'Conceito criado com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/conceitos/{id}',
        summary: 'Visualizar conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Dados do conceito, incluindo seus itens')]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $conceito = $this->service->buscar($id, $request->auth_user);

        return $this->respostaSucesso(new ConceitoResource($conceito));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/conceitos/{id}',
        summary: 'Atualizar conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Conceito atualizado')]
    )]
    public function update(UpdateConceitoRequest $request, int $id): JsonResponse
    {
        $conceito = $this->service->atualizar($id, ConceitoData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso(new ConceitoResource($conceito), 'Conceito atualizado com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/conceitos/{id}',
        summary: 'Excluir conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Conceito excluído')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Conceito removido com sucesso.');
    }
}
