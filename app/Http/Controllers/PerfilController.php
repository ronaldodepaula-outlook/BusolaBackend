<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PerfilController extends Controller
{
    #[OA\Get(
        path: '/api/v1/perfil',
        summary: 'Ver próprio perfil',
        tags: ['Perfil'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Perfil do usuário', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', ref: '#/components/schemas/Usuario'),
            ])),
        ]
    )]
    public function show(Request $request)
    {
        try {
            $user = $request->auth_user;
            $user->load(['roles.permissions', 'filial', 'empresa']);

            return response()->json([
                'sucesso' => true,
                'dados' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar perfil.'], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/perfil',
        summary: 'Atualizar próprio perfil',
        tags: ['Perfil'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'telefone', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Perfil atualizado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', ref: '#/components/schemas/Usuario'),
            ])),
        ]
    )]
    public function update(Request $request)
    {
        $user = $request->auth_user;

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'email' => "nullable|email|max:255|unique:users,email,{$user->id}",
            'telefone' => 'nullable|string|max:20',
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'email.unique' => 'Este e-mail já está sendo usado.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $data = array_filter($request->only(['nome', 'email', 'telefone']), fn($v) => !is_null($v));
            $user->update($data);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil atualizado com sucesso.',
                'dados' => $user->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atualizar perfil.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/perfil/trocar-senha',
        summary: 'Trocar própria senha',
        tags: ['Perfil'],
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
            'confirmacao.required' => 'A confirmação é obrigatória.',
            'confirmacao.same' => 'As senhas não coincidem.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $user = $request->auth_user;

            if (!Hash::check($request->senha_atual, $user->senha)) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Senha atual incorreta.'], 400);
            }

            $user->update(['senha' => Hash::make($request->nova_senha)]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Senha alterada com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao alterar senha.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/perfil/foto',
        summary: 'Upload da foto de perfil',
        tags: ['Perfil'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'foto', type: 'string', format: 'binary', description: 'JPG/PNG/GIF máx 2MB'),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Foto atualizada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
            ])),
        ]
    )]
    public function uploadFoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'foto.required' => 'A foto é obrigatória.',
            'foto.image' => 'O arquivo deve ser uma imagem.',
            'foto.mimes' => 'Formatos aceitos: jpeg, png, jpg, webp.',
            'foto.max' => 'A imagem deve ter no máximo 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Arquivo inválido.', 'erros' => $validator->errors()], 422);
        }

        try {
            $user = $request->auth_user;

            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }

            $path = $request->file('foto')->store('fotos', 'public');
            $user->update(['foto' => $path]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Foto atualizada com sucesso.',
                'dados' => ['foto' => Storage::url($path)],
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao fazer upload da foto.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/perfil/permissoes',
        summary: 'Listar permissões do usuário',
        description: 'Todas as permissões consolidadas dos roles do usuário.',
        tags: ['Perfil'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de permissões', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/Permission')),
            ])),
        ]
    )]
    public function permissoes(Request $request)
    {
        try {
            $user = $request->auth_user;
            $user->load('roles.permissions');

            $permissoes = $user->roles
                ->flatMap(fn($role) => $role->permissions)
                ->unique('id')
                ->values();

            return response()->json([
                'sucesso' => true,
                'dados' => [
                    'permissoes' => $permissoes,
                    'slugs' => $permissoes->pluck('slug'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar permissões.'], 500);
        }
    }
}
