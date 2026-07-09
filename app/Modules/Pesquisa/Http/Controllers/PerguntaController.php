<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\DTOs\PerguntaData;
use App\Modules\Pesquisa\Http\Requests\Pergunta\StorePerguntaRequest;
use App\Modules\Pesquisa\Http\Requests\Pergunta\UpdatePerguntaRequest;
use App\Modules\Pesquisa\Http\Resources\PerguntaResource;
use App\Modules\Pesquisa\Services\PerguntaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PerguntaController extends PesquisaBaseController
{
    public function __construct(private readonly PerguntaService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/subcategorias/{subcategoria}/perguntas',
        summary: 'Listar perguntas de uma subcategoria',
        tags: ['Pesquisa Psicossocial - Perguntas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'subcategoria', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Lista de perguntas')]
    )]
    public function index(Request $request, int $subcategoria): JsonResponse
    {
        $perguntas = $this->service->listar($subcategoria, $request->auth_user);

        return $this->respostaSucesso(PerguntaResource::collection($perguntas));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/subcategorias/{subcategoria}/perguntas',
        summary: 'Criar pergunta',
        tags: ['Pesquisa Psicossocial - Perguntas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'subcategoria', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Pergunta criada')]
    )]
    public function store(StorePerguntaRequest $request, int $subcategoria): JsonResponse
    {
        $resultado = $this->service->criar($subcategoria, PerguntaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso([
            'pergunta'            => new PerguntaResource($resultado['pergunta']),
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Pergunta criada com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/perguntas/{id}',
        summary: 'Visualizar pergunta',
        tags: ['Pesquisa Psicossocial - Perguntas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Dados da pergunta')]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $pergunta = $this->service->buscar($id, $request->auth_user);

        return $this->respostaSucesso(new PerguntaResource($pergunta));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/perguntas/{id}',
        summary: 'Atualizar pergunta',
        tags: ['Pesquisa Psicossocial - Perguntas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Pergunta atualizada')]
    )]
    public function update(UpdatePerguntaRequest $request, int $id): JsonResponse
    {
        $resultado = $this->service->atualizar($id, PerguntaData::fromRequest($request), $request->authUser());

        return $this->respostaSucesso([
            'pergunta'            => new PerguntaResource($resultado['pergunta']),
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Pergunta atualizada com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/subcategorias/{subcategoria}/perguntas/reordenar',
        summary: 'Reordenar perguntas de uma subcategoria',
        tags: ['Pesquisa Psicossocial - Perguntas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'subcategoria', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['ids'], properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'IDs das perguntas na ordem desejada'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: 'Perguntas reordenadas')]
    )]
    public function reordenar(Request $request, int $subcategoria): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $resultado = $this->service->reordenar($subcategoria, $request->input('ids'), $request->auth_user);

        return $this->respostaSucesso([
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Perguntas reordenadas com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/perguntas/{id}',
        summary: 'Excluir pergunta',
        tags: ['Pesquisa Psicossocial - Perguntas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Pergunta excluída')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $resultado = $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso([
            'versionado'          => $resultado['versionado'],
            'formulario_atual_id' => $resultado['formulario_atual_id'],
        ], 'Pergunta removida com sucesso.');
    }
}
