<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Repositories\PesquisaRepository;
use App\Modules\Pesquisa\Services\ConviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConviteController extends PesquisaBaseController
{
    public function __construct(
        private readonly ConviteService $service,
        private readonly PesquisaRepository $pesquisaRepository,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/pesquisas/{pesquisa}/convites',
        summary: 'Listar convites (links individuais) de uma campanha',
        tags: ['Pesquisa Psicossocial - Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'pesquisa', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Lista de convites com status de resposta')]
    )]
    public function index(Request $request, int $pesquisa): JsonResponse
    {
        $encontrada = $this->pesquisaRepository->buscarPorId($pesquisa, $request->auth_user);
        abort_if(! $encontrada, 404, 'Campanha não encontrada.');

        $convites = $this->service->listar($pesquisa);

        return $this->respostaSucesso($convites->map(fn ($c) => [
            'id'            => $c->id,
            'nome'          => $c->colaborador?->nome,
            'email'         => $c->colaborador?->email,
            'filial_id'     => $c->colaborador?->filial_id,
            'respondido'    => $c->jaRespondeu(),
            'respondido_em' => $c->respondido_em,
            'token'         => $c->token,
        ])->values());
    }
}
