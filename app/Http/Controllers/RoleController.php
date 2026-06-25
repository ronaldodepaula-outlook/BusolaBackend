<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    private function getEmpresaId(Request $request): int
    {
        return $request->empresa->id ?? $request->auth_user->empresa_id;
    }

    #[OA\Get(
        path: '/api/v1/roles',
        summary: 'Listar roles',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ativo', 'inativo']))],
        responses: [new OA\Response(response: 200, description: 'Lista de roles', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/Role'))]))]
    )]
    public function index(Request $request)
    {
        try {
            $empresaId = $this->getEmpresaId($request);

            $roles = Role::withCount('permissions')
                ->where(function ($q) use ($empresaId) {
                    $q->where('empresa_id', $empresaId)
                      ->orWhere('sistema', true);
                })
                ->orderBy('sistema', 'desc')
                ->orderBy('nome')
                ->get();

            return response()->json([
                'sucesso' => true,
                'dados' => $roles,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao listar perfis.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/roles',
        summary: 'Criar role',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['nome'], properties: [
            new OA\Property(property: 'nome', type: 'string', example: 'Operador Financeiro'),
            new OA\Property(property: 'descricao', type: 'string'),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'Role criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Role')])),
            new OA\Response(response: 422, description: 'Validação falhou', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:500',
            'status' => 'nullable|in:ativo,inativo',
        ], [
            'nome.required' => 'O nome do perfil é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $slug = Str::slug($request->nome);
            $slugBase = $slug;
            $i = 1;

            while (Role::where('empresa_id', $empresaId)->where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$i}";
                $i++;
            }

            $role = Role::create([
                'empresa_id' => $empresaId,
                'nome' => $request->nome,
                'slug' => $slug,
                'descricao' => $request->descricao,
                'status' => $request->status ?? 'ativo',
                'sistema' => false,
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil criado com sucesso.',
                'dados' => $role,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao criar perfil.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/roles/{id}',
        summary: 'Visualizar role',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Role com permissões', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Role')])),
            new OA\Response(response: 404, description: 'Não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function show(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $role = Role::with('permissions')
                ->where(function ($q) use ($empresaId) {
                    $q->where('empresa_id', $empresaId)->orWhere('sistema', true);
                })
                ->find($id);

            if (!$role) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Perfil não encontrado.'], 404);
            }

            return response()->json(['sucesso' => true, 'dados' => $role]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar perfil.'], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/roles/{id}',
        summary: 'Atualizar role',
        description: 'Não pode modificar roles de sistema.',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'nome', type: 'string'), new OA\Property(property: 'descricao', type: 'string')])),
        responses: [
            new OA\Response(response: 200, description: 'Role atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Role')])),
            new OA\Response(response: 403, description: 'Role de sistema protegido', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string|max:500',
            'status' => 'nullable|in:ativo,inativo',
        ], [
            'nome.required' => 'O nome do perfil é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $role = Role::where('empresa_id', $empresaId)->find($id);

            if (!$role) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Perfil não encontrado.'], 404);
            }

            if ($role->sistema) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Não é permitido modificar perfis do sistema.'], 400);
            }

            $updateData = $request->only(['nome', 'descricao', 'status']);

            if ($request->filled('nome')) {
                $slug = Str::slug($request->nome);
                $slugBase = $slug;
                $i = 1;
                while (Role::where('empresa_id', $empresaId)->where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = "{$slugBase}-{$i}";
                    $i++;
                }
                $updateData['slug'] = $slug;
            }

            $role->update($updateData);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil atualizado com sucesso.',
                'dados' => $role->fresh(['permissions']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atualizar perfil.'], 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/roles/{id}',
        summary: 'Excluir role',
        description: 'Não pode excluir roles de sistema.',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Role excluído', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'mensagem', type: 'string')])),
            new OA\Response(response: 403, description: 'Role de sistema protegido', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $role = Role::where('empresa_id', $empresaId)->find($id);

            if (!$role) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Perfil não encontrado.'], 404);
            }

            if ($role->sistema) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Não é permitido excluir perfis do sistema.'], 400);
            }

            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil removido com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao remover perfil.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/roles/{id}/duplicar',
        summary: 'Duplicar role',
        description: 'Clona o role com todas as permissões.',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Role duplicado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Role')]))]
    )]
    public function duplicar(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $role = Role::with('permissions')
                ->where(function ($q) use ($empresaId) {
                    $q->where('empresa_id', $empresaId)->orWhere('sistema', true);
                })
                ->find($id);

            if (!$role) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Perfil não encontrado.'], 404);
            }

            $novoNome = "Cópia de {$role->nome}";
            $slug = Str::slug($novoNome);
            $slugBase = $slug;
            $i = 1;
            while (Role::where('empresa_id', $empresaId)->where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$i}";
                $i++;
            }

            $novoRole = Role::create([
                'empresa_id' => $empresaId,
                'nome' => $novoNome,
                'slug' => $slug,
                'descricao' => $role->descricao,
                'status' => 'ativo',
                'sistema' => false,
            ]);

            $permissionIds = $role->permissions->pluck('id')->toArray();
            $novoRole->permissions()->sync($permissionIds);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil duplicado com sucesso.',
                'dados' => $novoRole->load('permissions'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao duplicar perfil.'], 500);
        }
    }

    #[OA\Patch(path: '/api/v1/roles/{id}/ativar', summary: 'Ativar role', tags: ['Roles'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Role ativado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function ativar(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $role = Role::where('empresa_id', $empresaId)->find($id);

            if (!$role) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Perfil não encontrado.'], 404);
            }

            $role->update(['status' => 'ativo']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil ativado com sucesso.',
                'dados' => $role,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao ativar perfil.'], 500);
        }
    }

    #[OA\Patch(path: '/api/v1/roles/{id}/inativar', summary: 'Inativar role', tags: ['Roles'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Role inativado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function inativar(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $role = Role::where('empresa_id', $empresaId)->find($id);

            if (!$role) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Perfil não encontrado.'], 404);
            }

            $role->update(['status' => 'inativo']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfil inativado com sucesso.',
                'dados' => $role,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao inativar perfil.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/roles/{id}/permissoes',
        summary: 'Atribuir permissões ao role',
        description: 'Sincroniza as permissões do role.',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['permissoes'], properties: [
            new OA\Property(property: 'permissoes', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
        ])),
        responses: [new OA\Response(response: 200, description: 'Permissões sincronizadas', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Role')]))]
    )]
    public function atribuirPermissoes(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'permissoes' => 'required|array',
            'permissoes.*' => 'integer|exists:permissions,id',
        ], [
            'permissoes.required' => 'Informe as permissões.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $role = Role::where('empresa_id', $empresaId)->find($id);

            if (!$role) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Perfil não encontrado.'], 404);
            }

            if ($role->sistema) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Não é permitido modificar permissões de perfis do sistema.'], 400);
            }

            $role->permissions()->sync($request->permissoes);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Permissões atribuídas com sucesso.',
                'dados' => $role->fresh(['permissions']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atribuir permissões.'], 500);
        }
    }
}
