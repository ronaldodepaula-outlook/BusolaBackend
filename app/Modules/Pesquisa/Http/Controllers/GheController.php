<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\Ghe\GheRequest;
use App\Modules\Pesquisa\Services\GheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GheController extends PesquisaBaseController
{
    public function __construct(private readonly GheService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/ghes',
        summary: 'Listar GHEs (Grupos Homogêneos de Exposição) da empresa',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de GHEs',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                    new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/PesquisaGhe')),
                ])
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->respostaSucesso($this->service->listar($request->auth_user));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/ghes',
        summary: 'Criar GHE',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['nome'],
            properties: [
                new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, description: 'Somente super administrador'),
                new OA\Property(property: 'nome', type: 'string', maxLength: 150, example: 'GHE 01 – Comercial e Relacionamento'),
                new OA\Property(property: 'descricao', type: 'string', nullable: true),
                new OA\Property(property: 'ativo', type: 'boolean', default: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'GHE criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaGhe')])),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function store(GheRequest $request): JsonResponse
    {
        $ghe = $this->service->criar($request->validated(), $request->authUser());

        return $this->respostaSucesso($ghe, 'GHE criado com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/ghes/{id}',
        summary: 'Visualizar GHE',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados do GHE', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaGhe')])),
            new OA\Response(response: 404, description: 'GHE não encontrado'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->buscar($id, $request->auth_user));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/ghes/{id}',
        summary: 'Atualizar GHE',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'nome', type: 'string', maxLength: 150),
            new OA\Property(property: 'descricao', type: 'string', nullable: true),
            new OA\Property(property: 'ativo', type: 'boolean'),
        ])),
        responses: [new OA\Response(response: 200, description: 'GHE atualizado')]
    )]
    public function update(GheRequest $request, int $id): JsonResponse
    {
        $ghe = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($ghe, 'GHE atualizado com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/ghes/{id}',
        summary: 'Excluir GHE',
        description: 'Os setores vinculados a este GHE ficam sem GHE (ghe_id passa a nulo) — não são excluídos.',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'GHE removido')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'GHE removido com sucesso.');
    }
}
