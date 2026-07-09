<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Resources\FormularioResource;
use App\Modules\Pesquisa\Services\RespostaPublicaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Endpoints públicos (sem autenticação) — acessados via o link individual
 * enviado a cada colaborador convidado para uma campanha.
 */
class RespostaPublicaController extends PesquisaBaseController
{
    public function __construct(private readonly RespostaPublicaService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/publico/{token}',
        summary: 'Validar link individual e obter a estrutura da pesquisa a responder',
        description: 'Endpoint público, sem autenticação. Retorna erro se o link já foi usado, a campanha não está ativa ou está fora do período.',
        tags: ['Pesquisa Psicossocial - Resposta Pública'],
        parameters: [new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Estrutura da pesquisa'),
            new OA\Response(response: 404, description: 'Link não encontrado'),
            new OA\Response(response: 409, description: 'Link já usado, campanha indisponível ou fora do período'),
        ]
    )]
    public function show(string $token): JsonResponse
    {
        $dados = $this->service->buscarEstrutura($token);

        return $this->respostaSucesso([
            'pesquisa' => [
                'id'        => $dados['pesquisa']->id,
                'nome'      => $dados['pesquisa']->nome,
                'descricao' => $dados['pesquisa']->descricao,
                'anonima'   => $dados['pesquisa']->anonima,
            ],
            'formulario' => new FormularioResource($dados['formulario']),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/publico/{token}/respostas',
        summary: 'Submeter respostas via link individual',
        description: 'Endpoint público, sem autenticação. A resposta gravada não é vinculada a nenhum usuário — apenas o link é marcado como utilizado.',
        tags: ['Pesquisa Psicossocial - Resposta Pública'],
        parameters: [new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 201, description: 'Respostas registradas'),
            new OA\Response(response: 422, description: 'Pergunta obrigatória não respondida'),
        ]
    )]
    public function store(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'respostas'   => 'array',
            'observacoes' => 'array',
        ]);

        $this->service->submeterRespostas(
            $token,
            $request->input('respostas', []),
            $request->input('observacoes', [])
        );

        return $this->respostaSucesso(null, 'Respostas registradas com sucesso. Obrigado pela participação!', 201);
    }

    #[OA\Get(
        path: '/api/v1/pesquisa-psicossocial/publico/global/{token}',
        summary: 'Validar link global (compartilhado) e obter a estrutura da pesquisa',
        description: 'Endpoint público, sem autenticação. O token identifica a CAMPANHA (não uma pessoa) — a prevenção de duplicidade é feita por um token de sessão gerado no navegador do respondente.',
        tags: ['Pesquisa Psicossocial - Resposta Pública'],
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sessao_token', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estrutura da pesquisa'),
            new OA\Response(response: 404, description: 'Link não encontrado'),
            new OA\Response(response: 409, description: 'Já respondido neste dispositivo, campanha indisponível ou fora do período'),
        ]
    )]
    public function showGlobal(Request $request, string $token): JsonResponse
    {
        $request->validate(['sessao_token' => 'required|string|max:64']);

        $dados = $this->service->buscarEstruturaGlobal(
            $token,
            $request->input('sessao_token'),
            $request->input('ip') ?: $request->ip(),
            $request->userAgent()
        );

        return $this->respostaSucesso([
            'pesquisa' => [
                'id'        => $dados['pesquisa']->id,
                'nome'      => $dados['pesquisa']->nome,
                'descricao' => $dados['pesquisa']->descricao,
                'anonima'   => $dados['pesquisa']->anonima,
            ],
            'formulario' => new FormularioResource($dados['formulario']),
            'setores'    => $this->service->listarSetoresParaSelecao($dados['pesquisa'])->map(fn ($s) => [
                'id'   => $s->id,
                'nome' => $s->nome,
            ])->values(),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/pesquisa-psicossocial/publico/global/{token}/respostas',
        summary: 'Submeter respostas via link global (compartilhado)',
        description: 'Endpoint público, sem autenticação. A resposta gravada não é vinculada a nenhum dispositivo/sessão — apenas a sessão é marcada como utilizada.',
        tags: ['Pesquisa Psicossocial - Resposta Pública'],
        parameters: [new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 201, description: 'Respostas registradas'),
            new OA\Response(response: 422, description: 'Pergunta obrigatória não respondida'),
        ]
    )]
    public function storeGlobal(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'sessao_token' => 'required|string|max:64',
            'setor_id'     => 'nullable|integer|exists:pesq_setores,id',
            'respostas'    => 'array',
            'observacoes'  => 'array',
        ]);

        $this->service->submeterRespostasGlobal(
            $token,
            $request->input('sessao_token'),
            $request->input('ip') ?: $request->ip(),
            $request->userAgent(),
            $request->input('respostas', []),
            $request->input('observacoes', []),
            $request->integer('setor_id') ?: null
        );

        return $this->respostaSucesso(null, 'Respostas registradas com sucesso. Obrigado pela participação!', 201);
    }
}
