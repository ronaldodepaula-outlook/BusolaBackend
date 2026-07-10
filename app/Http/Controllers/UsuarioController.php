<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\AtivacaoContaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly AtivacaoContaService $ativacaoContaService,
    ) {
    }

    private function getEmpresaId(Request $request): int
    {
        return $request->empresa->id ?? $request->auth_user->empresa_id;
    }

    #[OA\Get(
        path: '/api/v1/usuarios',
        summary: 'Listar usuários',
        tags: ['Usuários'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ativo', 'inativo', 'bloqueado'])),
            new OA\Parameter(name: 'tipo', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['admin', 'gerente', 'usuario'])),
            new OA\Parameter(name: 'filial_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', type: 'object', properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Usuario')),
                    new OA\Property(property: 'total', type: 'integer'),
                ]),
            ])),
        ]
    )]
    public function index(Request $request)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $query = User::where('empresa_id', $empresaId)->with('roles');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->filled('filial_id')) {
                $query->where('filial_id', $request->filial_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $usuarios = $query->orderBy('nome')->paginate(15);

            return response()->json([
                'sucesso' => true,
                'dados' => $usuarios,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao listar usuários.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/usuarios',
        summary: 'Cadastrar usuário',
        description: 'Valida limite max_usuarios. Quando `senha` não é informada, o usuário é criado sem senha e inativo — recebe por e-mail um link para definir a própria senha e ativar a conta (válido por 24h, uso único).',
        tags: ['Usuários'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'email', 'tipo'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'Maria Santos'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'senha', type: 'string', format: 'password', minLength: 8, nullable: true, description: 'Opcional — se omitida, um convite de ativação é enviado por e-mail'),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['admin', 'gerente', 'usuario']),
                    new OA\Property(property: 'filial_id', type: 'integer'),
                    new OA\Property(property: 'telefone', type: 'string'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuário criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Usuario')])),
            new OA\Response(response: 422, description: 'Validação falhou', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            // Opcional de propósito — ver Fluxo 1 (ativação de conta) na descrição do endpoint.
            'senha' => 'nullable|string|min:8',
            'tipo' => 'required|in:admin,gerente,usuario',
            'filial_id' => 'nullable|integer',
            'telefone' => 'nullable|string|max:20',
            'status' => 'nullable|in:ativo,inativo,bloqueado',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'senha.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'tipo.required' => 'O tipo de usuário é obrigatório.',
            'tipo.in' => 'Tipo inválido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresa = $request->empresa ?? $request->auth_user->empresa;
            $empresaId = $empresa->id ?? $request->auth_user->empresa_id;

            $totalUsuarios = User::where('empresa_id', $empresaId)->count();
            $maxUsuarios = $empresa->max_usuarios ?? PHP_INT_MAX;

            if ($totalUsuarios >= $maxUsuarios) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => "Limite de usuários atingido ({$maxUsuarios}). Atualize seu plano para adicionar mais.",
                ], 400);
            }

            // Superadmin não pode ser criado via API
            if ($request->tipo === 'superadmin') {
                return response()->json(['sucesso' => false, 'mensagem' => 'Tipo de usuário inválido.'], 400);
            }

            // Sem senha informada => Fluxo 1: conta criada inativa, sem senha,
            // até o próprio usuário concluir a ativação pelo link recebido
            // por e-mail (ver AtivacaoContaService). Com senha informada,
            // preserva o comportamento anterior (conta já usável de imediato).
            $senhaInformada = $request->filled('senha');

            $usuario = User::create([
                'empresa_id' => $empresaId,
                'filial_id' => $request->filial_id,
                'nome' => $request->nome,
                'email' => $request->email,
                'senha' => $senhaInformada ? Hash::make($request->senha) : null,
                'tipo' => $request->tipo,
                'telefone' => $request->telefone,
                'status' => $senhaInformada ? ($request->status ?? 'ativo') : 'inativo',
                'primeiro_acesso' => true,
            ]);

            if ($request->filled('roles')) {
                $usuario->roles()->sync($request->roles);
            }

            if (! $senhaInformada) {
                $this->ativacaoContaService->convidar($usuario);
            }

            $usuario->load('roles');

            return response()->json([
                'sucesso' => true,
                'mensagem' => $senhaInformada
                    ? 'Usuário criado com sucesso.'
                    : 'Usuário criado com sucesso. Um e-mail foi enviado para que ele defina a própria senha.',
                'dados' => $usuario,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao criar usuário.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/usuarios/{id}',
        summary: 'Visualizar usuário',
        tags: ['Usuários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados do usuário com roles', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Usuario')])),
            new OA\Response(response: 404, description: 'Não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function show(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)
                ->with(['roles.permissions', 'filial'])
                ->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            return response()->json(['sucesso' => true, 'dados' => $usuario]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar usuário.'], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/usuarios/{id}',
        summary: 'Atualizar usuário',
        tags: ['Usuários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'nome', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'tipo', type: 'string', enum: ['admin', 'gerente', 'usuario']),
            new OA\Property(property: 'filial_id', type: 'integer'),
            new OA\Property(property: 'nova_senha', type: 'string', format: 'password', minLength: 8),
        ])),
        responses: [new OA\Response(response: 200, description: 'Atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Usuario')]))]
    )]
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'email' => "nullable|email|max:255|unique:users,email,{$id}",
            'tipo' => 'nullable|in:admin,gerente,usuario',
            'filial_id' => 'nullable|integer',
            'telefone' => 'nullable|string|max:20',
            'status' => 'nullable|in:ativo,inativo,bloqueado',
            'nova_senha' => 'nullable|string|min:8',
            'confirmacao_senha' => 'nullable|same:nova_senha',
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'email.unique' => 'Este e-mail já está sendo usado.',
            'nova_senha.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
            'confirmacao_senha.same' => 'As senhas não coincidem.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            $data = $request->only(['nome', 'email', 'tipo', 'filial_id', 'telefone', 'status']);
            $data = array_filter($data, fn($v) => !is_null($v));

            if ($request->filled('nova_senha')) {
                $data['senha'] = Hash::make($request->nova_senha);
            }

            $usuario->update($data);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário atualizado com sucesso.',
                'dados' => $usuario->fresh(['roles']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atualizar usuário.'], 500);
        }
    }

    #[OA\Delete(path: '/api/v1/usuarios/{id}', summary: 'Excluir usuário', tags: ['Usuários'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Excluído', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function destroy(Request $request, $id)
    {
        if ($request->auth_user->id == $id) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Você não pode excluir sua própria conta.'], 400);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            $usuario->delete();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário removido com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao remover usuário.'], 500);
        }
    }

    #[OA\Patch(path: '/api/v1/usuarios/{id}/bloquear', summary: 'Bloquear usuário', tags: ['Usuários'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Bloqueado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function bloquear(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            $usuario->update(['status' => 'bloqueado']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário bloqueado com sucesso.',
                'dados' => $usuario,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao bloquear usuário.'], 500);
        }
    }

    #[OA\Patch(path: '/api/v1/usuarios/{id}/ativar', summary: 'Ativar usuário', tags: ['Usuários'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Ativado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function ativar(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            $usuario->update(['status' => 'ativo']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário ativado com sucesso.',
                'dados' => $usuario,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao ativar usuário.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/usuarios/{id}/resetar-senha',
        summary: 'Resetar senha do usuário (admin)',
        description: 'Gera nova senha aleatória.',
        tags: ['Usuários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Nova senha gerada', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'sucesso', type: 'boolean', example: true),
            new OA\Property(property: 'dados', type: 'object', properties: [new OA\Property(property: 'nova_senha', type: 'string')]),
        ]))]
    )]
    public function resetarSenha(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            $novaSenha = Str::random(12);
            $usuario->update([
                'senha' => Hash::make($novaSenha),
                'primeiro_acesso' => true,
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Senha redefinida com sucesso.',
                'dados' => ['nova_senha' => $novaSenha],
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao redefinir senha.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/usuarios/{id}/roles',
        summary: 'Atribuir roles ao usuário',
        description: 'Sincroniza os roles do usuário.',
        tags: ['Usuários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['roles'], properties: [
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 3]),
        ])),
        responses: [new OA\Response(response: 200, description: 'Roles sincronizados', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Usuario')]))]
    )]
    public function atribuirRoles(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'integer|exists:roles,id',
        ], [
            'roles.required' => 'Informe ao menos um perfil.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            $usuario->roles()->sync($request->roles);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Perfis atribuídos com sucesso.',
                'dados' => $usuario->fresh(['roles']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atribuir perfis.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/usuarios/{id}/foto',
        summary: 'Upload da foto do usuário',
        tags: ['Usuários'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'foto', type: 'string', format: 'binary')]))),
        responses: [new OA\Response(response: 200, description: 'Foto atualizada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))]
    )]
    public function uploadFoto(Request $request, $id)
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
            $empresaId = $this->getEmpresaId($request);
            $usuario = User::where('empresa_id', $empresaId)->find($id);

            if (!$usuario) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.'], 404);
            }

            if ($usuario->foto && Storage::disk('public')->exists($usuario->foto)) {
                Storage::disk('public')->delete($usuario->foto);
            }

            $path = $request->file('foto')->store('fotos', 'public');
            $usuario->update(['foto' => $path]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Foto atualizada com sucesso.',
                'dados' => ['foto' => Storage::disk('public')->url($path)],
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao fazer upload da foto.'], 500);
        }
    }
}
