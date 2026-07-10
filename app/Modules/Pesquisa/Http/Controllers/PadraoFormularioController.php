<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\PadraoFormulario\StorePadraoFormularioRequest;
use App\Modules\Pesquisa\Http\Requests\PadraoFormulario\UpdatePadraoFormularioRequest;
use App\Modules\Pesquisa\Services\PadraoFormularioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PadraoFormularioController extends PesquisaBaseController
{
    public function __construct(private readonly PadraoFormularioService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/padroes-formulario',
        summary: 'Listar padrões de formulário (ex.: COPSOQ II, NR-1, ou padrões da empresa)',
        description: 'Padrões globais (empresa_id nulo) aparecem para todas as empresas; padrões de empresa só aparecem para a empresa dona (super administrador vê todos).',
        tags: ['Pesquisa Psicossocial - Padrões de Formulário'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'empresa_id', in: 'query', required: false, description: 'Somente super administrador', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'apenas_ativos', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de padrões',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/PesquisaPadraoFormulario'))])
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->only(['empresa_id', 'apenas_ativos']);

        return $this->respostaSucesso($this->service->listar($request->auth_user, $filtros));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/padroes-formulario',
        summary: 'Criar padrão de formulário',
        description: 'Padrões do tipo "global" (disponíveis a todas as empresas) só podem ser criados pelo super administrador.',
        tags: ['Pesquisa Psicossocial - Padrões de Formulário'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['nome', 'tipo'],
            properties: [
                new OA\Property(property: 'nome', type: 'string', maxLength: 150, example: 'COPSOQ II'),
                new OA\Property(property: 'descricao', type: 'string', nullable: true),
                new OA\Property(property: 'tipo', type: 'string', enum: ['global', 'empresa']),
                new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, description: 'Obrigatório quando tipo=empresa e o usuário é super administrador'),
                new OA\Property(property: 'ativo', type: 'boolean', default: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Padrão criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaPadraoFormulario')])),
            new OA\Response(response: 422, description: 'Validação falhou (inclui nome duplicado no escopo, ou tipo=global sem ser super administrador)'),
        ]
    )]
    public function store(StorePadraoFormularioRequest $request): JsonResponse
    {
        $padrao = $this->service->criar($request->validated(), $request->authUser());

        return $this->respostaSucesso($padrao, 'Padrão de formulário criado com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/padroes-formulario/{id}',
        summary: 'Visualizar padrão de formulário',
        tags: ['Pesquisa Psicossocial - Padrões de Formulário'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados do padrão', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaPadraoFormulario')])),
            new OA\Response(response: 404, description: 'Padrão não encontrado'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->buscar($id, $request->auth_user));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/padroes-formulario/{id}',
        summary: 'Atualizar padrão de formulário',
        description: 'Apenas nome, descrição e status são editáveis — tipo e empresa são imutáveis após a criação.',
        tags: ['Pesquisa Psicossocial - Padrões de Formulário'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'nome', type: 'string', maxLength: 150),
            new OA\Property(property: 'descricao', type: 'string', nullable: true),
            new OA\Property(property: 'ativo', type: 'boolean'),
        ])),
        responses: [new OA\Response(response: 200, description: 'Padrão atualizado')]
    )]
    public function update(UpdatePadraoFormularioRequest $request, int $id): JsonResponse
    {
        $padrao = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($padrao, 'Padrão de formulário atualizado com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/padroes-formulario/{id}',
        summary: 'Excluir padrão de formulário',
        description: 'Formulários que usam este padrão ficam sem padrão associado (padrao_formulario_id passa a nulo) — não são excluídos.',
        tags: ['Pesquisa Psicossocial - Padrões de Formulário'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Padrão removido')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Padrão de formulário removido com sucesso.');
    }
}
