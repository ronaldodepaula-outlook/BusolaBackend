<?php

namespace App\Http\Controllers;

use App\Models\TokenBlacklist;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Realizar login',
        description: 'Autentica o usuário e retorna o token JWT.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'senha'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'superadmin@sistema.com'),
                    new OA\Property(property: 'senha', type: 'string', format: 'password', example: 'Admin@2024'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login realizado com sucesso',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                    new OA\Property(property: 'mensagem', type: 'string'),
                    new OA\Property(property: 'dados', type: 'object', properties: [
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'tipo', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'usuario', ref: '#/components/schemas/Usuario'),
                    ]),
                ])
            ),
            new OA\Response(response: 401, description: 'Credenciais inválidas', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
            new OA\Response(response: 422, description: 'Dados inválidos', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'senha' => 'required|string',
        ], [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'senha.required' => 'A senha é obrigatória.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Dados inválidos.',
                'erros' => $validator->errors(),
            ], 422);
        }

        try {
            $credentials = ['email' => $request->email, 'password' => $request->senha];

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'E-mail ou senha incorretos.',
                ], 401);
            }

            $user = JWTAuth::user();

            if ($user->status === 'inativo') {
                JWTAuth::invalidate($token);
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Usuário inativo. Entre em contato com o administrador.',
                ], 403);
            }

            if ($user->status === 'bloqueado') {
                JWTAuth::invalidate($token);
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Usuário bloqueado. Entre em contato com o administrador.',
                ], 403);
            }

            $user->update(['ultimo_login' => Carbon::now()]);
            $user->load(['empresa', 'filial', 'roles.permissions']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso.',
                'dados' => [
                    'token' => $token,
                    'tipo' => 'bearer',
                    'usuario' => $user,
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Não foi possível criar o token. Tente novamente.',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Realizar logout',
        description: 'Invalida o token JWT adicionando-o à blacklist.',
        tags: ['Autenticação'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout realizado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string', example: 'Logout realizado com sucesso.'),
            ])),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();

            TokenBlacklist::create([
                'token' => $token->get(),
                'expires_at' => Carbon::now()->addMinutes(config('jwt.ttl', 60)),
            ]);

            JWTAuth::invalidate($token);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Logout realizado com sucesso.',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao realizar logout.',
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Dados do usuário autenticado',
        tags: ['Autenticação'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Dados do usuário', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', ref: '#/components/schemas/Usuario'),
            ])),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function me(Request $request)
    {
        try {
            $user = $request->auth_user;
            $user->load(['empresa', 'filial', 'roles.permissions']);

            return response()->json([
                'sucesso' => true,
                'dados' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar dados do usuário.',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: 'Renovar token JWT',
        tags: ['Autenticação'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token renovado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', type: 'object', properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'tipo', type: 'string', example: 'bearer'),
                ]),
            ])),
            new OA\Response(response: 401, description: 'Token inválido', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function refresh(Request $request)
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Token renovado com sucesso.',
                'dados' => [
                    'token' => $newToken,
                    'tipo' => 'bearer',
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Não foi possível renovar o token.',
            ], 401);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/trocar-senha',
        summary: 'Trocar senha do usuário autenticado',
        tags: ['Autenticação'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['senha_atual', 'nova_senha', 'confirmacao'],
                properties: [
                    new OA\Property(property: 'senha_atual', type: 'string', format: 'password'),
                    new OA\Property(property: 'nova_senha', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'confirmacao', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Senha alterada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string'),
            ])),
            new OA\Response(response: 422, description: 'Validação falhou', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function trocarSenha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'senha_atual' => 'required|string',
            'nova_senha' => 'required|string|min:8',
            'confirmacao' => 'required|same:nova_senha',
        ], [
            'senha_atual.required' => 'A senha atual é obrigatória.',
            'nova_senha.required' => 'A nova senha é obrigatória.',
            'nova_senha.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
            'confirmacao.required' => 'A confirmação de senha é obrigatória.',
            'confirmacao.same' => 'A confirmação não coincide com a nova senha.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Dados inválidos.',
                'erros' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->auth_user;

            if (!Hash::check($request->senha_atual, $user->senha)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Senha atual incorreta.',
                ], 400);
            }

            $user->update(['senha' => Hash::make($request->nova_senha)]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Senha alterada com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao alterar a senha.',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/recuperar-senha',
        summary: 'Solicitar recuperação de senha',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token gerado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string'),
            ])),
            new OA\Response(response: 404, description: 'E-mail não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function recuperarSenha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Dados inválidos.',
                'erros' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                // Retornar sucesso mesmo se não encontrar (segurança)
                return response()->json([
                    'sucesso' => true,
                    'mensagem' => 'Se o e-mail estiver cadastrado, você receberá as instruções de recuperação.',
                ]);
            }

            $token = Str::random(64);
            $user->update([
                'reset_token' => Hash::make($token),
                'reset_token_expires_at' => Carbon::now()->addHours(2),
            ]);

            // Em produção, enviaria por e-mail. Aqui retorna para fins de desenvolvimento.
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Token de recuperação gerado.',
                'dados' => [
                    'token' => $token, // Remover em produção
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao processar solicitação de recuperação.',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/resetar-senha',
        summary: 'Redefinir senha com token',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'nova_senha', 'confirmacao'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'nova_senha', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'confirmacao', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Senha redefinida', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string'),
            ])),
            new OA\Response(response: 400, description: 'Token inválido', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function resetarSenha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'nova_senha' => 'required|string|min:8',
            'confirmacao' => 'required|same:nova_senha',
        ], [
            'token.required' => 'O token é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'nova_senha.required' => 'A nova senha é obrigatória.',
            'nova_senha.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
            'confirmacao.required' => 'A confirmação é obrigatória.',
            'confirmacao.same' => 'A confirmação não coincide com a nova senha.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Dados inválidos.',
                'erros' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)
                ->whereNotNull('reset_token')
                ->first();

            if (!$user || !Hash::check($request->token, $user->reset_token)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Token inválido ou expirado.',
                ], 400);
            }

            if (Carbon::now()->isAfter($user->reset_token_expires_at)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Token expirado. Solicite um novo.',
                ], 400);
            }

            $user->update([
                'senha' => Hash::make($request->nova_senha),
                'reset_token' => null,
                'reset_token_expires_at' => null,
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Senha redefinida com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao redefinir a senha.',
            ], 500);
        }
    }
}
