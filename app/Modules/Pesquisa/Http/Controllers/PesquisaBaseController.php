<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class PesquisaBaseController extends Controller
{
    protected function respostaSucesso(mixed $dados = null, ?string $mensagem = null, int $status = 200): JsonResponse
    {
        $payload = ['sucesso' => true];

        if ($mensagem !== null) {
            $payload['mensagem'] = $mensagem;
        }

        if ($dados !== null) {
            $payload['dados'] = $dados;
        }

        return response()->json($payload, $status);
    }

    protected function respostaErro(string $mensagem, int $status = 422, mixed $erros = null): JsonResponse
    {
        $payload = ['sucesso' => false, 'mensagem' => $mensagem];

        if ($erros !== null) {
            $payload['erros'] = $erros;
        }

        return response()->json($payload, $status);
    }
}
