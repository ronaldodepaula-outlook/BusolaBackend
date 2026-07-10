<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DefinirSenhaComTokenRequest;
use App\Services\Auth\AtivacaoContaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Endpoints públicos (sem `auth.jwt`) do Fluxo 1 — quem os autoriza é a
 * posse do token válido, não uma sessão autenticada. Ambos delegam toda a
 * regra de negócio a {@see AtivacaoContaService}; se o token for
 * inválido/expirado, a {@see \App\Exceptions\Auth\TokenInvalidoOuExpiradoException}
 * lançada pelo Service já sabe se auto-renderizar como HTTP 422 — não há
 * try/catch aqui.
 */
class AtivacaoContaController extends Controller
{
    public function __construct(private readonly AtivacaoContaService $service)
    {
    }

    #[OA\Get(
        path: '/api/v1/auth/ativacao/{token}',
        summary: 'Verificar validade de um link de ativação de conta',
        description: 'Usado pela tela pública antes de exibir o formulário de senha, para confirmar que o link ainda não expirou nem foi usado.',
        tags: ['Autenticação'],
        parameters: [new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Token válido', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', properties: [new OA\Property(property: 'nome', type: 'string')], type: 'object'),
            ])),
            new OA\Response(response: 422, description: 'Token inválido, já usado ou expirado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function validar(Request $request, string $token): JsonResponse
    {
        $user = $this->service->validarToken($token);

        return response()->json([
            'sucesso' => true,
            'dados'   => ['nome' => $user->nome],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/ativacao',
        summary: 'Concluir a ativação de conta definindo a senha',
        description: 'Consome o token de ativação: define a senha escolhida, ativa a conta (status=ativo, primeiro_acesso=false) e invalida o token. Uso único.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'senha', 'confirmacao_senha'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'senha', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'confirmacao_senha', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Conta ativada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string'),
            ])),
            new OA\Response(response: 422, description: 'Token inválido/expirado ou senha fora da política', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function ativar(DefinirSenhaComTokenRequest $request): JsonResponse
    {
        $this->service->ativar($request->validated('token'), $request->validated('senha'));

        return response()->json([
            'sucesso'  => true,
            'mensagem' => 'Conta ativada com sucesso! Você já pode fazer login.',
        ]);
    }
}
