<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\RelatorioTecnico\GerarRelatorioTecnicoRequest;
use App\Modules\Pesquisa\Services\RelatorioTecnicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaRelatorioTecnico',
    description: 'Registro de uma geração do Relatório Técnico (PDF) de uma campanha',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'pesquisa_id', type: 'integer'),
        new OA\Property(property: 'empresa_id', type: 'integer'),
        new OA\Property(property: 'gerado_por', type: 'integer', nullable: true),
        new OA\Property(property: 'responsavel_tecnico_nome', type: 'string', nullable: true),
        new OA\Property(property: 'responsavel_tecnico_registro', type: 'string', nullable: true),
        new OA\Property(property: 'arquivo_path', type: 'string', description: 'Caminho no disco privado — baixe via /relatorios-tecnicos/{id}/download'),
        new OA\Property(property: 'tamanho_bytes', type: 'integer'),
        new OA\Property(property: 'gerado_em', type: 'string', format: 'date-time'),
    ]
)]
class RelatorioTecnicoController extends PesquisaBaseController
{
    public function __construct(private readonly RelatorioTecnicoService $service)
    {
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{pesquisa}/relatorios-tecnicos',
        summary: 'Gerar o Relatório Técnico (PDF) de uma campanha',
        description: 'Consolida resultado tabulado, classificação de risco por categoria/GHE e plano de ação no relatório técnico padrão da metodologia. O PDF é salvo em disco privado (nunca público).',
        tags: ['Pesquisa Psicossocial - Relatórios Técnicos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'pesquisa', in: 'path', required: true, description: 'ID da campanha', schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'responsavel_tecnico_nome', type: 'string', nullable: true, maxLength: 150),
            new OA\Property(property: 'responsavel_tecnico_registro', type: 'string', nullable: true, maxLength: 60, example: 'CRP 11/15242'),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'Relatório gerado', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaRelatorioTecnico')])),
        ]
    )]
    public function gerar(GerarRelatorioTecnicoRequest $request, int $pesquisa): JsonResponse
    {
        $relatorio = $this->service->gerar($pesquisa, $request->authUser(), [
            'nome'     => $request->validated('responsavel_tecnico_nome'),
            'registro' => $request->validated('responsavel_tecnico_registro'),
        ]);

        return $this->respostaSucesso($relatorio, 'Relatório técnico gerado com sucesso.', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{pesquisa}/relatorios-tecnicos',
        summary: 'Listar relatórios técnicos já gerados de uma campanha (própria empresa)',
        tags: ['Pesquisa Psicossocial - Relatórios Técnicos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'pesquisa', in: 'path', required: true, description: 'ID da campanha (não filtra o resultado — mantido por compatibilidade de rota)', schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de relatórios da empresa do usuário autenticado',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/PesquisaRelatorioTecnico'))])
            ),
        ]
    )]
    public function porEmpresa(Request $request): JsonResponse
    {
        abort_if(! $request->auth_user->empresa_id, 422, 'Usuário não está vinculado a uma empresa.');

        return $this->respostaSucesso($this->service->listarPorEmpresa($request->auth_user->empresa_id));
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/relatorios-tecnicos',
        summary: 'Listar relatórios técnicos de todas as empresas (super administrador)',
        tags: ['Pesquisa Psicossocial - Relatórios Técnicos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'empresa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada cross-empresa'),
            new OA\Response(response: 403, description: 'Apenas o super administrador pode acessar esta listagem'),
        ]
    )]
    public function todas(Request $request): JsonResponse
    {
        abort_unless($request->auth_user->isSuperAdmin(), 403, 'Apenas o super administrador pode acessar esta listagem.');

        $empresaId = $request->integer('empresa_id') ?: null;

        return $this->respostaSucesso($this->service->listarTodas($empresaId));
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/relatorios-tecnicos/{id}/download',
        summary: 'Baixar o PDF de um relatório técnico',
        tags: ['Pesquisa Psicossocial - Relatórios Técnicos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Arquivo PDF', content: new OA\MediaType(mediaType: 'application/pdf', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 403, description: 'Relatório pertence a outra empresa'),
            new OA\Response(response: 404, description: 'Relatório ou arquivo não encontrado'),
        ]
    )]
    public function download(Request $request, int $id): Response
    {
        $relatorio = $this->service->buscar($id, $request->auth_user);
        $conteudo = $this->service->conteudo($relatorio);

        return response($conteudo, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.basename($relatorio->arquivo_path).'"',
        ]);
    }
}
