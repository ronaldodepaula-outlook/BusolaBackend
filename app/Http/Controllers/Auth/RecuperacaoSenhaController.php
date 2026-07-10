<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DefinirSenhaComTokenRequest;
use App\Http\Requests\Auth\SolicitarRecuperacaoSenhaRequest;
use App\Services\Auth\RecuperacaoSenhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Endpoints públicos (sem `auth.jwt`) do Fluxo 2. `solicitar()` é
 * deliberadamente "burro" na resposta — sempre 200 com a mesma mensagem
 * genérica — para não revelar se um e-mail está cadastrado (ver
 * {@see RecuperacaoSenhaService::solicitar()} para onde a decisão real
 * acontece).
 */
class RecuperacaoSenhaController extends Controller
{
    public function __construct(private readonly RecuperacaoSenhaService $service)
    {
    }

    #[OA\Post(
        path: '/api/v1/auth/recuperar-senha',
        summary: 'Solicitar recuperação de senha',
        description: 'Sempre responde com a mesma mensagem genérica de sucesso, exista ou não o e-mail informado — evita enumeração de contas. Sujeito a rate limit.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(required: ['email'], properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Solicitação processada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string'),
            ])),
            new OA\Response(response: 429, description: 'Muitas tentativas — aguarde antes de tentar novamente'),
        ]
    )]
    public function solicitar(SolicitarRecuperacaoSenhaRequest $request): JsonResponse
    {
        $this->service->solicitar($request->validated('email'));

        return response()->json([
            'sucesso'  => true,
            'mensagem' => 'Se o e-mail informado estiver cadastrado, você receberá as instruções de recuperação em breve.',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/auth/resetar-senha/{token}',
        summary: 'Verificar validade de um link de recuperação de senha',
        description: 'Usado pela tela pública antes de exibir o formulário de nova senha, para confirmar que o link ainda não expirou nem foi usado.',
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
        path: '/api/v1/auth/resetar-senha',
        summary: 'Concluir a recuperação definindo a nova senha',
        description: 'Consome o token de recuperação: define a nova senha e invalida o token. Uso único.',
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
            new OA\Response(response: 200, description: 'Senha redefinida', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string'),
            ])),
            new OA\Response(response: 422, description: 'Token inválido/expirado ou senha fora da política', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function redefinir(DefinirSenhaComTokenRequest $request): JsonResponse
    {
        $this->service->redefinir($request->validated('token'), $request->validated('senha'));

        return response()->json([
            'sucesso'  => true,
            'mensagem' => 'Senha redefinida com sucesso! Você já pode fazer login.',
        ]);
    }
}
