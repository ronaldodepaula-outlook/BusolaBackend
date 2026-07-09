<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Services\ResultadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ResultadoController extends PesquisaBaseController
{
    public function __construct(private readonly ResultadoService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{pesquisa}/resultados',
        summary: 'Tabulação agregada de resultados de uma campanha',
        description: 'Dados sempre agregados (contagens/médias) — nunca expõe uma resposta individual junto de dados de quem respondeu.',
        tags: ['Pesquisa Psicossocial - Resultados'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'pesquisa', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Taxa de resposta, distribuição por pergunta e média por categoria')]
    )]
    public function show(Request $request, int $pesquisa): JsonResponse
    {
        $dados = $this->service->resultados($pesquisa, $request->auth_user);

        return $this->respostaSucesso($dados);
    }
}
