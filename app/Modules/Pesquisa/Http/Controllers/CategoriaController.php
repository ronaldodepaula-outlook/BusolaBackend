<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\DTOs\CategoriaData;
use App\Modules\Pesquisa\Http\Requests\Categoria\StoreCategoriaRequest;
use App\Modules\Pesquisa\Http\Requests\Categoria\UpdateCategoriaRequest;
use App\Modules\Pesquisa\Http\Resources\CategoriaResource;
use App\Modules\Pesquisa\Services\CategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoriaController extends PesquisaBaseController
{
    public function __construct(private readonly CategoriaService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/formularios/{formulario}/categorias',
        summary: 'Listar categorias de um formulário',
        tags: ['Pesquisa Psicossocial - Categorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'formulario', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Lista de categorias')]
    )]
    public function index(Request $request, int $formulario): JsonResponse
    {
        $categorias = $this->service->listar($formulario, $request->auth_user);

        return $this->respostaSucesso(CategoriaResource::collection($categorias));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/formularios/{formulario}/categorias',
        summary: 'Criar categoria',
        description: 'Se o formulário já estiver vinculado a uma pesquisa encerrada, uma nova versão é criada automaticamente.',
        tags: ['Pesquisa Psicossocial - Categorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'formulario', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Categoria criada')]
    )]
    public function store(StoreCategoriaRequest $request, int $formulario): JsonResponse
    {
        $resultado = $this->service->criar($formulario, CategoriaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso([
            'categoria'           => new CategoriaResource($resultado['categoria']),
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Categoria criada com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/categorias/{id}',
        summary: 'Visualizar categoria',
        tags: ['Pesquisa Psicossocial - Categorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Dados da categoria')]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $categoria = $this->service->buscar($id, $request->auth_user);

        return $this->respostaSucesso(new CategoriaResource($categoria));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/categorias/{id}',
        summary: 'Atualizar categoria',
        tags: ['Pesquisa Psicossocial - Categorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Categoria atualizada')]
    )]
    public function update(UpdateCategoriaRequest $request, int $id): JsonResponse
    {
        $resultado = $this->service->atualizar($id, CategoriaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso([
            'categoria'           => new CategoriaResource($resultado['categoria']),
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Categoria atualizada com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/categorias/{id}',
        summary: 'Excluir categoria',
        tags: ['Pesquisa Psicossocial - Categorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Categoria excluída')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $resultado = $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso([
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Categoria removida com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/formularios/{formulario}/categorias/reordenar',
        summary: 'Reordenar categorias de um formulário',
        tags: ['Pesquisa Psicossocial - Categorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'formulario', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['ids'], properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs das categorias na ordem desejada'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: 'Categorias reordenadas')]
    )]
    public function reordenar(Request $request, int $formulario): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $resultado = $this->service->reordenar($formulario, $request->input('ids'), $request->auth_user);

        return $this->respostaSucesso([
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Categorias reordenadas com sucesso.');
    }
}
