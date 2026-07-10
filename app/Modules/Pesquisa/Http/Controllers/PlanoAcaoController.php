<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\PlanoAcao\AtualizarPlanoAcaoRequest;
use App\Modules\Pesquisa\Http\Requests\PlanoAcao\AvancarFasePdcaRequest;
use App\Modules\Pesquisa\Http\Requests\PlanoAcao\ConcluirCicloPdcaRequest;
use App\Modules\Pesquisa\Models\PlanoAcao;
use App\Modules\Pesquisa\Services\PlanoAcaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaPlanoAcao',
    description: 'Ação do Plano de Ação (PDCA) gerada a partir da classificação de risco de uma categoria/GHE',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'categoria', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'nome', type: 'string')], type: 'object', nullable: true),
        new OA\Property(property: 'ghe', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'nome', type: 'string')], type: 'object', nullable: true),
        new OA\Property(property: 'tipo_controle', properties: [new OA\Property(property: 'value', type: 'string'), new OA\Property(property: 'label', type: 'string')], type: 'object'),
        new OA\Property(property: 'nivel_risco', properties: [new OA\Property(property: 'value', type: 'string'), new OA\Property(property: 'label', type: 'string')], type: 'object'),
        new OA\Property(property: 'farol', type: 'string'),
        new OA\Property(property: 'acao', type: 'string'),
        new OA\Property(property: 'como_executar', type: 'string', nullable: true),
        new OA\Property(property: 'evidencia', type: 'string', nullable: true),
        new OA\Property(property: 'responsavel', type: 'string', nullable: true),
        new OA\Property(property: 'prazo', type: 'string', nullable: true),
        new OA\Property(property: 'status', properties: [new OA\Property(property: 'value', type: 'string'), new OA\Property(property: 'label', type: 'string')], type: 'object'),
        new OA\Property(property: 'concluido_em', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'observacoes', type: 'string', nullable: true),
        new OA\Property(property: 'fase_pdca', properties: [new OA\Property(property: 'value', type: 'string', enum: ['planejar', 'executar', 'verificar', 'agir']), new OA\Property(property: 'label', type: 'string'), new OA\Property(property: 'proxima', type: 'string', nullable: true)], type: 'object'),
        new OA\Property(property: 'ciclo_pdca', type: 'integer', example: 1),
        new OA\Property(property: 'executado_em', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'evidencia_execucao', type: 'string', nullable: true),
        new OA\Property(property: 'verificado_em', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'verificado_por', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'nome', type: 'string')], type: 'object', nullable: true),
        new OA\Property(property: 'parecer_verificacao', type: 'string', nullable: true),
        new OA\Property(property: 'agido_em', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'eficacia', properties: [new OA\Property(property: 'value', type: 'string', enum: ['eficaz', 'parcialmente_eficaz', 'ineficaz']), new OA\Property(property: 'label', type: 'string')], type: 'object', nullable: true),
        new OA\Property(property: 'necessita_nova_acao', type: 'boolean', nullable: true),
        new OA\Property(property: 'historico_pdca', type: 'array', items: new OA\Items(type: 'object'), description: 'Snapshot de cada ciclo PDCA anterior, arquivado quando a eficácia não é plena e o ciclo é reaberto automaticamente'),
    ]
)]
class PlanoAcaoController extends PesquisaBaseController
{
    public function __construct(private readonly PlanoAcaoService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{pesquisa}/plano-acao',
        summary: 'Listar o plano de ação de uma campanha',
        tags: ['Pesquisa Psicossocial - Plano de Ação'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'pesquisa', in: 'path', required: true, description: 'ID da campanha', schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de ações do plano',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/PesquisaPlanoAcao')),
                ])
            ),
        ]
    )]
    public function index(Request $request, int $pesquisa): JsonResponse
    {
        $acoes = $this->service->listar($pesquisa, $request->auth_user)->map($this->formatar(...));

        return $this->respostaSucesso($acoes->values());
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{pesquisa}/plano-acao/gerar',
        summary: 'Gerar/atualizar o plano de ação a partir do resultado atual da campanha',
        description: 'Idempotente: cria uma ação (fase PDCA "Planejar") para cada Categoria×GHE×TipoControle cujo nível de risco exija ação, ou atualiza a existente. Pode ser executado novamente após reclassificação de risco.',
        tags: ['Pesquisa Psicossocial - Plano de Ação'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'pesquisa', in: 'path', required: true, description: 'ID da campanha', schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumo da geração',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'dados', properties: [
                        new OA\Property(property: 'geradas', type: 'integer'),
                        new OA\Property(property: 'atualizadas', type: 'integer'),
                    ], type: 'object'),
                ])
            ),
        ]
    )]
    public function gerar(Request $request, int $pesquisa): JsonResponse
    {
        $resultado = $this->service->gerar($pesquisa, $request->auth_user);

        return $this->respostaSucesso($resultado, 'Plano de ação gerado a partir do resultado atual da campanha.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/plano-acao/{id}',
        summary: 'Atualizar responsável, prazo, status ou observações de uma ação',
        tags: ['Pesquisa Psicossocial - Plano de Ação'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'responsavel', type: 'string', nullable: true, maxLength: 150),
            new OA\Property(property: 'prazo', type: 'string', nullable: true, maxLength: 30),
            new OA\Property(property: 'status', type: 'string', nullable: true),
            new OA\Property(property: 'observacoes', type: 'string', nullable: true),
        ])),
        responses: [new OA\Response(response: 200, description: 'Ação atualizada', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaPlanoAcao')]))]
    )]
    public function update(AtualizarPlanoAcaoRequest $request, int $id): JsonResponse
    {
        $planoAcao = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($this->formatar($planoAcao), 'Ação atualizada com sucesso.');
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/plano-acao/{id}/avancar-fase',
        summary: 'Avançar a ação para a próxima fase do ciclo PDCA',
        description: 'Sequência estrita Planejar → Executar → Verificar → Agir, sem pular etapas. Exige `evidencia_execucao` ao avançar para "verificar" e `parecer_verificacao` ao avançar para "agir".',
        tags: ['Pesquisa Psicossocial - Plano de Ação'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['fase'],
            properties: [
                new OA\Property(property: 'fase', type: 'string', enum: ['planejar', 'executar', 'verificar', 'agir'], description: 'Fase de destino — deve ser exatamente a próxima da fase atual'),
                new OA\Property(property: 'evidencia_execucao', type: 'string', nullable: true, description: 'Obrigatório quando fase = "verificar"'),
                new OA\Property(property: 'parecer_verificacao', type: 'string', nullable: true, description: 'Obrigatório quando fase = "agir"'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Ação avançada de fase', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaPlanoAcao')])),
            new OA\Response(response: 422, description: 'Fase fora de sequência ou evidência/parecer faltando'),
        ]
    )]
    public function avancarFase(AvancarFasePdcaRequest $request, int $id): JsonResponse
    {
        $dados = $request->validated();
        $planoAcao = $this->service->avancarFase($id, $dados['fase'], $dados, $request->authUser());

        return $this->respostaSucesso($this->formatar($planoAcao), "Ação avançada para a fase \"{$planoAcao->fase_pdca->label()}\".");
    }

    #[OA\Patch(
        path: '/api/v1/pesquisa-psicossocial/plano-acao/{id}/concluir-ciclo',
        summary: 'Concluir o ciclo PDCA da ação (fase "Agir")',
        description: 'Se `eficacia` não for "eficaz", um novo ciclo é aberto automaticamente (fase volta a "planejar", `ciclo_pdca` é incrementado e o ciclo anterior é arquivado em `historico_pdca`). Se "eficaz", a ação é encerrada.',
        tags: ['Pesquisa Psicossocial - Plano de Ação'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['eficacia'],
            properties: [
                new OA\Property(property: 'eficacia', type: 'string', enum: ['eficaz', 'parcialmente_eficaz', 'ineficaz']),
                new OA\Property(property: 'necessita_nova_acao', type: 'boolean'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Ciclo concluído (encerrado ou reaberto)', content: new OA\JsonContent(properties: [new OA\Property(property: 'dados', ref: '#/components/schemas/PesquisaPlanoAcao')]))]
    )]
    public function concluirCiclo(ConcluirCicloPdcaRequest $request, int $id): JsonResponse
    {
        $planoAcao = $this->service->concluirCiclo($id, $request->validated(), $request->authUser());

        $mensagem = $planoAcao->fase_pdca->value === 'planejar'
            ? 'Ciclo concluído sem eficácia plena — um novo ciclo PDCA foi aberto automaticamente.'
            : 'Ciclo PDCA concluído com sucesso — ação encerrada.';

        return $this->respostaSucesso($this->formatar($planoAcao), $mensagem);
    }

    /** Serializa os enums como {value,label} — Laravel serializa BackedEnum casts como valor escalar cru por padrão. */
    private function formatar(PlanoAcao $acao): array
    {
        return [
            'id'                   => $acao->id,
            'categoria'            => $acao->categoria ? ['id' => $acao->categoria->id, 'nome' => $acao->categoria->nome] : null,
            'ghe'                  => $acao->ghe ? ['id' => $acao->ghe->id, 'nome' => $acao->ghe->nome] : null,
            'tipo_controle'        => ['value' => $acao->tipo_controle->value, 'label' => $acao->tipo_controle->label()],
            'nivel_risco'          => ['value' => $acao->nivel_risco->value, 'label' => $acao->nivel_risco->label()],
            'farol'                => $acao->farol,
            'acao'                 => $acao->acao,
            'como_executar'        => $acao->como_executar,
            'evidencia'            => $acao->evidencia,
            'responsavel'          => $acao->responsavel,
            'prazo'                => $acao->prazo,
            'status'               => ['value' => $acao->status->value, 'label' => $acao->status->label()],
            'concluido_em'         => $acao->concluido_em?->toIso8601String(),
            'observacoes'          => $acao->observacoes,
            'fase_pdca'            => ['value' => $acao->fase_pdca->value, 'label' => $acao->fase_pdca->label(), 'proxima' => $acao->fase_pdca->proxima()?->value],
            'ciclo_pdca'           => $acao->ciclo_pdca,
            'executado_em'         => $acao->executado_em?->toIso8601String(),
            'evidencia_execucao'   => $acao->evidencia_execucao,
            'verificado_em'        => $acao->verificado_em?->toIso8601String(),
            'verificado_por'       => $acao->verificadoPor ? ['id' => $acao->verificadoPor->id, 'nome' => $acao->verificadoPor->nome] : null,
            'parecer_verificacao'  => $acao->parecer_verificacao,
            'agido_em'             => $acao->agido_em?->toIso8601String(),
            'eficacia'             => $acao->eficacia ? ['value' => $acao->eficacia->value, 'label' => $acao->eficacia->label()] : null,
            'necessita_nova_acao'  => $acao->necessita_nova_acao,
            'historico_pdca'       => $acao->historico_pdca ?? [],
        ];
    }
}
