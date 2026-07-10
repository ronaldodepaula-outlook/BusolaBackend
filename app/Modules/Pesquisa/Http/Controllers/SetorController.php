<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\Setor\SetorRequest;
use App\Modules\Pesquisa\Services\SetorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SetorController extends PesquisaBaseController
{
    public function __construct(private readonly SetorService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/setores',
        summary: 'Listar setores organizacionais da empresa',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de setores',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                    new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/PesquisaSetor')),
                ])
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->respostaSucesso($this->service->listar($request->auth_user));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/setores',
        summary: 'Criar setor',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['nome'],
            properties: [
                new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, description: 'Somente super administrador'),
                new OA\Property(property: 'ghe_id', type: 'integer', nullable: true),
                new OA\Property(property: 'nome', type: 'string', maxLength: 150, example: 'Comercial'),
                new OA\Property(property: 'ativo', type: 'boolean', default: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Setor criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaSetor')])),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function store(SetorRequest $request): JsonResponse
    {
        $setor = $this->service->criar($request->validated(), $request->authUser());

        return $this->respostaSucesso($setor, 'Setor criado com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/setores/{id}',
        summary: 'Visualizar setor',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados do setor', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaSetor')])),
            new OA\Response(response: 404, description: 'Setor não encontrado'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->buscar($id, $request->auth_user));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/setores/{id}',
        summary: 'Atualizar setor',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'ghe_id', type: 'integer', nullable: true),
            new OA\Property(property: 'nome', type: 'string', maxLength: 150),
            new OA\Property(property: 'ativo', type: 'boolean'),
        ])),
        responses: [new OA\Response(response: 200, description: 'Setor atualizado')]
    )]
    public function update(SetorRequest $request, int $id): JsonResponse
    {
        $setor = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($setor, 'Setor atualizado com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/setores/{id}',
        summary: 'Excluir setor',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Setor removido')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Setor removido com sucesso.');
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/setores/{id}/usuario',
        summary: 'Atribuir um usuário (conta de acesso) a um setor',
        description: 'Fluxo legado, anterior ao cadastro de Colaboradores — o alvo do convite individual de campanhas hoje é Colaborador, não User.',
        tags: ['Pesquisa Psicossocial - Setores e GHE'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do setor', schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(required: ['user_id'], properties: [
            new OA\Property(property: 'user_id', type: 'integer'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Setor do usuário atualizado'),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function definirUsuario(Request $request, int $id): JsonResponse
    {
        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $this->service->definirSetorDoUsuario((int) $request->input('user_id'), $id, $request->auth_user);

        return $this->respostaSucesso(null, 'Setor do colaborador atualizado com sucesso.');
    }
}
