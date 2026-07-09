<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\DTOs\ConceitoItemData;
use App\Modules\Pesquisa\Http\Requests\ConceitoItem\StoreConceitoItemRequest;
use App\Modules\Pesquisa\Http\Requests\ConceitoItem\UpdateConceitoItemRequest;
use App\Modules\Pesquisa\Http\Resources\ConceitoItemResource;
use App\Modules\Pesquisa\Services\ConceitoItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConceitoItemController extends PesquisaBaseController
{
    public function __construct(private readonly ConceitoItemService $service)
    {
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/conceitos/{conceito}/itens',
        summary: 'Adicionar item a um conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'conceito', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Item criado')]
    )]
    public function store(StoreConceitoItemRequest $request, int $conceito): JsonResponse
    {
        $item = $this->service->adicionar($conceito, ConceitoItemData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso(new ConceitoItemResource($item), 'Item adicionado com sucesso.', 201);
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/conceitos/{conceito}/itens/{itemId}',
        summary: 'Atualizar item de um conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'conceito', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Item atualizado')]
    )]
    public function update(UpdateConceitoItemRequest $request, int $conceito, int $itemId): JsonResponse
    {
        $item = $this->service->atualizar($conceito, $itemId, ConceitoItemData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso(new ConceitoItemResource($item), 'Item atualizado com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/conceitos/{conceito}/itens/{itemId}',
        summary: 'Remover item de um conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'conceito', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Item removido')]
    )]
    public function destroy(Request $request, int $conceito, int $itemId): JsonResponse
    {
        $this->service->remover($conceito, $itemId, $request->auth_user);

        return $this->respostaSucesso(null, 'Item removido com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/conceitos/{conceito}/itens/reordenar',
        summary: 'Reordenar itens de um conceito de avaliação',
        tags: ['Pesquisa Psicossocial - Conceitos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'conceito', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['ids'], properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs dos itens na ordem desejada'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: 'Itens reordenados')]
    )]
    public function reordenar(Request $request, int $conceito): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $this->service->reordenar($conceito, $request->input('ids'), $request->auth_user);

        return $this->respostaSucesso(null, 'Itens reordenados com sucesso.');
    }
}
