<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\PlanoAcao\AtualizarPlanoAcaoRequest;
use App\Modules\Pesquisa\Http\Requests\PlanoAcao\AvancarFasePdcaRequest;
use App\Modules\Pesquisa\Http\Requests\PlanoAcao\ConcluirCicloPdcaRequest;
use App\Modules\Pesquisa\Models\PlanoAcao;
use App\Modules\Pesquisa\Services\PlanoAcaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanoAcaoController extends PesquisaBaseController
{
    public function __construct(private readonly PlanoAcaoService $service)
    {
    }

    public function index(Request $request, int $pesquisa): JsonResponse
    {
        $acoes = $this->service->listar($pesquisa, $request->auth_user)->map($this->formatar(...));

        return $this->respostaSucesso($acoes->values());
    }

    public function gerar(Request $request, int $pesquisa): JsonResponse
    {
        $resultado = $this->service->gerar($pesquisa, $request->auth_user);

        return $this->respostaSucesso($resultado, 'Plano de ação gerado a partir do resultado atual da campanha.');
    }

    public function update(AtualizarPlanoAcaoRequest $request, int $id): JsonResponse
    {
        $planoAcao = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($this->formatar($planoAcao), 'Ação atualizada com sucesso.');
    }

    public function avancarFase(AvancarFasePdcaRequest $request, int $id): JsonResponse
    {
        $dados = $request->validated();
        $planoAcao = $this->service->avancarFase($id, $dados['fase'], $dados, $request->authUser());

        return $this->respostaSucesso($this->formatar($planoAcao), "Ação avançada para a fase \"{$planoAcao->fase_pdca->label()}\".");
    }

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
