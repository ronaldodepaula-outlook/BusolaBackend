<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\Colaborador\ColaboradorRequest;
use App\Modules\Pesquisa\Http\Requests\Colaborador\ImportarColaboradoresRequest;
use App\Modules\Pesquisa\Services\ColaboradorImportService;
use App\Modules\Pesquisa\Services\ColaboradorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ColaboradorController extends PesquisaBaseController
{
    public function __construct(
        private readonly ColaboradorService $service,
        private readonly ColaboradorImportService $importService,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/colaboradores',
        summary: 'Listar colaboradores da empresa',
        description: 'CPF e data de nascimento nunca são retornados em claro nesta listagem — apenas mascarados (ver `cpf_mascarado`).',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'setor_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filial_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ativo', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'empresa_id', in: 'query', required: false, description: 'Somente super administrador', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista paginada de colaboradores')]
    )]
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->only(['search', 'setor_id', 'filial_id', 'ativo', 'empresa_id', 'per_page']);

        return $this->respostaSucesso($this->service->listar($filtros, $request->auth_user));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/colaboradores',
        summary: 'Cadastrar colaborador manualmente',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['nome'],
            properties: [
                new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, description: 'Somente super administrador'),
                new OA\Property(property: 'filial_id', type: 'integer', nullable: true),
                new OA\Property(property: 'setor_id', type: 'integer', nullable: true),
                new OA\Property(property: 'matricula', type: 'string', nullable: true, maxLength: 40),
                new OA\Property(property: 'nome', type: 'string', maxLength: 150),
                new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                new OA\Property(property: 'cargo', type: 'string', nullable: true, maxLength: 100),
                new OA\Property(property: 'ativo', type: 'boolean', default: true),
                new OA\Property(property: 'cpf', type: 'string', nullable: true, description: 'Com ou sem pontuação — cifrado no banco, nunca retornado em claro nesta rota', example: '123.456.789-09'),
                new OA\Property(property: 'data_nascimento', type: 'string', format: 'date', nullable: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Colaborador cadastrado', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaColaborador')])),
            new OA\Response(response: 422, description: 'Validação falhou (inclui CPF duplicado na mesma empresa)'),
        ]
    )]
    public function store(ColaboradorRequest $request): JsonResponse
    {
        $colaborador = $this->service->criar($request->validated(), $request->authUser());

        return $this->respostaSucesso($colaborador, 'Colaborador cadastrado com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/colaboradores/{id}',
        summary: 'Visualizar colaborador',
        description: 'CPF e data de nascimento retornam mascarados — use o endpoint dedicado /dados-sensiveis para vê-los em claro.',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados do colaborador', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaColaborador')])),
            new OA\Response(response: 404, description: 'Colaborador não encontrado'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->buscar($id, $request->auth_user));
    }

    #[OA\Put(
        path: '/api/v1/pesquisa-psicossocial/colaboradores/{id}',
        summary: 'Atualizar colaborador',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'filial_id', type: 'integer', nullable: true),
            new OA\Property(property: 'setor_id', type: 'integer', nullable: true),
            new OA\Property(property: 'matricula', type: 'string', nullable: true),
            new OA\Property(property: 'nome', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
            new OA\Property(property: 'cargo', type: 'string', nullable: true),
            new OA\Property(property: 'ativo', type: 'boolean'),
            new OA\Property(property: 'cpf', type: 'string', nullable: true),
            new OA\Property(property: 'data_nascimento', type: 'string', format: 'date', nullable: true),
        ])),
        responses: [new OA\Response(response: 200, description: 'Colaborador atualizado')]
    )]
    public function update(ColaboradorRequest $request, int $id): JsonResponse
    {
        $colaborador = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($colaborador, 'Colaborador atualizado com sucesso.');
    }

    #[OA\Delete(
        path: '/api/v1/pesquisa-psicossocial/colaboradores/{id}',
        summary: 'Excluir colaborador',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Colaborador removido')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Colaborador removido com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/colaboradores/{id}/anonimizar',
        summary: 'Anonimizar dados pessoais do colaborador (LGPD)',
        description: 'Remove nome, e-mail, matrícula, CPF e data de nascimento sem afetar convites/respostas de campanhas já geradas (essas nunca guardaram dados pessoais).',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Dados pessoais anonimizados')]
    )]
    public function anonimizar(Request $request, int $id): JsonResponse
    {
        $colaborador = $this->service->anonimizar($id, $request->auth_user);

        return $this->respostaSucesso($colaborador, 'Dados pessoais do colaborador anonimizados com sucesso.');
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/colaboradores/{id}/dados-sensiveis',
        summary: 'Revelar CPF e data de nascimento em claro',
        description: 'Único endpoint que devolve dados sensíveis sem máscara — exige permissão dedicada (`colaborador.visualizar_dados_sensiveis`) e a requisição é registrada pelo log automático do sistema (usuário, IP, timestamp), atendendo ao princípio de responsabilização da LGPD.',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dados sensíveis em claro',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'dados', properties: [
                        new OA\Property(property: 'cpf', type: 'string', nullable: true, example: '12345678909'),
                        new OA\Property(property: 'data_nascimento', type: 'string', format: 'date', nullable: true),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 403, description: 'Sem permissão para visualizar dados sensíveis'),
        ]
    )]
    public function dadosSensiveis(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->dadosSensiveis($id, $request->auth_user));
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/colaboradores/importar',
        summary: 'Importar colaboradores via CSV',
        description: 'Colunas reconhecidas: nome, cpf, data_nascimento, email, cargo, matricula, setor, filial. Deduplica por CPF (ou matrícula, se o CPF não vier); cada linha é processada em transação isolada, então uma linha inválida não aborta o lote.',
        tags: ['Pesquisa Psicossocial - Colaboradores'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['conteudo_csv'],
            properties: [
                new OA\Property(property: 'conteudo_csv', type: 'string', description: 'Conteúdo bruto do arquivo CSV (não um upload multipart)'),
                new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, description: 'Somente super administrador'),
            ]
        )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resultado da importação',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'dados', properties: [
                        new OA\Property(property: 'importados', type: 'integer'),
                        new OA\Property(property: 'atualizados', type: 'integer'),
                        new OA\Property(property: 'erros', type: 'array', items: new OA\Items(type: 'object')),
                    ], type: 'object'),
                ])
            ),
        ]
    )]
    public function importar(ImportarColaboradoresRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $resultado = $this->importService->importar($dados['conteudo_csv'], $request->authUser(), $dados['empresa_id'] ?? null);

        return $this->respostaSucesso(
            $resultado,
            "{$resultado['importados']} colaborador(es) importado(s), {$resultado['atualizados']} atualizado(s)."
        );
    }
}
