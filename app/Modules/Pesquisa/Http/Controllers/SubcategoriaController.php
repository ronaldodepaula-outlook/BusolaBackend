<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\DTOs\SubcategoriaData;
use App\Modules\Pesquisa\Http\Requests\Subcategoria\StoreSubcategoriaRequest;
use App\Modules\Pesquisa\Http\Requests\Subcategoria\UpdateSubcategoriaRequest;
use App\Modules\Pesquisa\Http\Resources\SubcategoriaResource;
use App\Modules\Pesquisa\Services\SubcategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubcategoriaController extends PesquisaBaseController
{
    public function __construct(private readonly SubcategoriaService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/categorias/{categoria}/subcategorias',
        summary: 'Listar subcategorias de uma categoria',
        tags: ['Pesquisa Psicossocial - Subcategorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'categoria', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Lista de subcategorias')]
    )]
    public function index(Request $request, int $categoria): JsonResponse
    {
        $subcategorias = $this->service->listar($categoria, $request->auth_user);

        return $this->respostaSucesso(SubcategoriaResource::collection($subcategorias));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/categorias/{categoria}/subcategorias',
        summary: 'Criar subcategoria',
        tags: ['Pesquisa Psicossocial - Subcategorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'categoria', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Subcategoria criada')]
    )]
    public function store(StoreSubcategoriaRequest $request, int $categoria): JsonResponse
    {
        $resultado = $this->service->criar($categoria, SubcategoriaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso([
            'subcategoria'        => new SubcategoriaResource($resultado['subcategoria']),
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Subcategoria criada com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/subcategorias/{id}',
        summary: 'Visualizar subcategoria',
        tags: ['Pesquisa Psicossocial - Subcategorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Dados da subcategoria')]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $subcategoria = $this->service->buscar($id, $request->auth_user);

        return $this->respostaSucesso(new SubcategoriaResource($subcategoria));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/subcategorias/{id}',
        summary: 'Atualizar subcategoria',
        tags: ['Pesquisa Psicossocial - Subcategorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Subcategoria atualizada')]
    )]
    public function update(UpdateSubcategoriaRequest $request, int $id): JsonResponse
    {
        $resultado = $this->service->atualizar($id, SubcategoriaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso([
            'subcategoria'        => new SubcategoriaResource($resultado['subcategoria']),
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Subcategoria atualizada com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/categorias/{categoria}/subcategorias/reordenar',
        summary: 'Reordenar subcategorias de uma categoria',
        tags: ['Pesquisa Psicossocial - Subcategorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'categoria', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['ids'], properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs das subcategorias na ordem desejada'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: 'Subcategorias reordenadas')]
    )]
    public function reordenar(Request $request, int $categoria): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $resultado = $this->service->reordenar($categoria, $request->input('ids'), $request->auth_user);

        return $this->respostaSucesso([
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Subcategorias reordenadas com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/subcategorias/{id}',
        summary: 'Excluir subcategoria',
        tags: ['Pesquisa Psicossocial - Subcategorias'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Subcategoria excluída')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $resultado = $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso([
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Subcategoria removida com sucesso.');
    }
}
