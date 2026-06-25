<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PermissaoController extends Controller
{
    #[OA\Get(path: '/api/v1/permissoes', summary: 'Listar permissões', tags: ['Permissões'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Lista de permissões', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/Permission'))]))])]
    public function index(Request $request)
    {
        try {
            $permissions = Permission::orderBy('modulo')->orderBy('nome')->get();

            return response()->json([
                'sucesso' => true,
                'dados' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao listar permissões.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/permissoes/por-modulo',
        summary: 'Permissões agrupadas por módulo',
        description: 'Ideal para construir interfaces de configuração de roles.',
        tags: ['Permissões'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Agrupadas por módulo', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', type: 'object')]))]
    )]
    public function porModulo(Request $request)
    {
        try {
            $permissions = Permission::orderBy('modulo')->orderBy('nome')->get();

            $agrupadas = $permissions->groupBy('modulo')->map(function ($items, $modulo) {
                return [
                    'modulo' => $modulo,
                    'permissoes' => $items->values(),
                ];
            })->values();

            return response()->json([
                'sucesso' => true,
                'dados' => $agrupadas,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar permissões por módulo.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/permissoes',
        summary: 'Criar permissão',
        description: 'Apenas Super Admin.',
        tags: ['Permissões'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['nome', 'slug', 'modulo'], properties: [
            new OA\Property(property: 'nome', type: 'string', example: 'Aprovar Orçamentos'),
            new OA\Property(property: 'slug', type: 'string', example: 'financeiro.aprovar'),
            new OA\Property(property: 'modulo', type: 'string', example: 'financeiro'),
            new OA\Property(property: 'descricao', type: 'string'),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'Permissão criada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Permission')])),
            new OA\Response(response: 422, description: 'Slug duplicado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function store(Request $request)
    {
        if ($request->auth_user->tipo !== 'superadmin') {
            return response()->json(['sucesso' => false, 'mensagem' => 'Apenas superadmin pode criar permissões.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'modulo' => 'required|string|max:100',
            'descricao' => 'nullable|string|max:500',
        ], [
            'nome.required' => 'O nome da permissão é obrigatório.',
            'slug.required' => 'O slug é obrigatório.',
            'slug.unique' => 'Este slug já está em uso.',
            'modulo.required' => 'O módulo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $permission = Permission::create($request->only(['nome', 'slug', 'modulo', 'descricao']));

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Permissão criada com sucesso.',
                'dados' => $permission,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao criar permissão.'], 500);
        }
    }

    #[OA\Get(path: '/api/v1/permissoes/{id}', summary: 'Visualizar permissão', tags: ['Permissões'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Dados', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Permission')]))])]
    public function show(Request $request, $id)
    {
        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Permissão não encontrada.'], 404);
            }

            return response()->json(['sucesso' => true, 'dados' => $permission]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar permissão.'], 500);
        }
    }

    #[OA\Put(path: '/api/v1/permissoes/{id}', summary: 'Atualizar permissão', tags: ['Permissões'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'nome', type: 'string'), new OA\Property(property: 'descricao', type: 'string')])), responses: [new OA\Response(response: 200, description: 'Atualizada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Permission')]))])]
    public function update(Request $request, $id)
    {
        if ($request->auth_user->tipo !== 'superadmin') {
            return response()->json(['sucesso' => false, 'mensagem' => 'Apenas superadmin pode editar permissões.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'slug' => "sometimes|required|string|max:255|unique:permissions,slug,{$id}",
            'modulo' => 'sometimes|required|string|max:100',
            'descricao' => 'nullable|string|max:500',
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'slug.unique' => 'Este slug já está em uso.',
            'modulo.required' => 'O módulo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Permissão não encontrada.'], 404);
            }

            $permission->update($request->only(['nome', 'slug', 'modulo', 'descricao']));

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Permissão atualizada com sucesso.',
                'dados' => $permission->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atualizar permissão.'], 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/permissoes/{id}',
        summary: 'Excluir permissão',
        description: 'Não pode excluir se vinculada a roles.',
        tags: ['Permissões'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Excluída', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'mensagem', type: 'string')])),
            new OA\Response(response: 409, description: 'Em uso por roles', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        if ($request->auth_user->tipo !== 'superadmin') {
            return response()->json(['sucesso' => false, 'mensagem' => 'Apenas superadmin pode excluir permissões.'], 403);
        }

        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Permissão não encontrada.'], 404);
            }

            $usadaEmRoles = $permission->roles()->count();
            if ($usadaEmRoles > 0) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => "Esta permissão está sendo usada por {$usadaEmRoles} perfil(is). Remova-a dos perfis antes de excluir.",
                ], 400);
            }

            $permission->delete();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Permissão removida com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao remover permissão.'], 500);
        }
    }
}
