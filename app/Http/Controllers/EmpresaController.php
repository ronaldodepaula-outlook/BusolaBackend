<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class EmpresaController extends Controller
{
    private function checkPermission(Request $request): bool
    {
        $tipo = $request->auth_user->tipo ?? '';
        return in_array($tipo, ['superadmin', 'admin']);
    }

    #[OA\Get(
        path: '/api/v1/empresas',
        summary: 'Listar empresas',
        description: 'Super Admin vê todas; Admin vê apenas a própria.',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ativo', 'inativo', 'bloqueado'])),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de empresas', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', type: 'object', properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Empresa')),
                    new OA\Property(property: 'total', type: 'integer'),
                    new OA\Property(property: 'current_page', type: 'integer'),
                ]),
            ])),
            new OA\Response(response: 403, description: 'Acesso negado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function index(Request $request)
    {
        if (!$this->checkPermission($request)) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'], 403);
        }

        try {
            $query = Empresa::withCount(['filiais', 'users']);

            if ($request->auth_user->tipo !== 'superadmin') {
                $query->where('id', $request->auth_user->empresa_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('cnpj', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $empresas = $query->orderBy('nome')->paginate(15);

            return response()->json([
                'sucesso' => true,
                'dados' => $empresas,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao listar empresas.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/empresas',
        summary: 'Cadastrar empresa',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'Nova Empresa LTDA'),
                    new OA\Property(property: 'cnpj', type: 'string', example: '98.765.432/0001-10'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'telefone', type: 'string'),
                    new OA\Property(property: 'plano', type: 'string', enum: ['basic', 'professional', 'enterprise']),
                    new OA\Property(property: 'max_filiais', type: 'integer', example: 5),
                    new OA\Property(property: 'max_usuarios', type: 'integer', example: 50),
                    new OA\Property(property: 'responsavel', type: 'string'),
                    new OA\Property(property: 'cidade', type: 'string'),
                    new OA\Property(property: 'estado', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Empresa criada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', ref: '#/components/schemas/Empresa'),
            ])),
            new OA\Response(response: 422, description: 'Validação falhou', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function store(Request $request)
    {
        if ($request->auth_user->tipo !== 'superadmin') {
            return response()->json(['sucesso' => false, 'mensagem' => 'Apenas superadmin pode criar empresas.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18|unique:empresas,cnpj',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'plano' => 'nullable|string|in:basico,intermediario,avancado,enterprise',
            'max_filiais' => 'nullable|integer|min:1',
            'max_usuarios' => 'nullable|integer|min:1',
            'responsavel' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:9',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'observacoes' => 'nullable|string',
        ], [
            'nome.required' => 'O nome da empresa é obrigatório.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresa = Empresa::create(array_merge($request->only([
                'nome', 'cnpj', 'email', 'telefone', 'plano', 'max_filiais',
                'max_usuarios', 'responsavel', 'cep', 'endereco', 'numero',
                'complemento', 'bairro', 'cidade', 'estado', 'observacoes',
            ]), ['status' => 'ativo']));

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Empresa criada com sucesso.',
                'dados' => $empresa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao criar empresa.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/empresas/{id}',
        summary: 'Visualizar empresa',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados da empresa', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', ref: '#/components/schemas/Empresa'),
            ])),
            new OA\Response(response: 404, description: 'Não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function show(Request $request, $id)
    {
        if (!$this->checkPermission($request)) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'], 403);
        }

        try {
            $query = Empresa::withCount(['filiais', 'users']);

            if ($request->auth_user->tipo !== 'superadmin') {
                $query->where('id', $request->auth_user->empresa_id);
            }

            $empresa = $query->find($id);

            if (!$empresa) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Empresa não encontrada.'], 404);
            }

            return response()->json(['sucesso' => true, 'dados' => $empresa]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar empresa.'], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/empresas/{id}',
        summary: 'Atualizar empresa',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'nome', type: 'string'),
                new OA\Property(property: 'cnpj', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo', 'bloqueado']),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Empresa atualizada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', ref: '#/components/schemas/Empresa'),
            ])),
            new OA\Response(response: 404, description: 'Não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission($request)) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'cnpj' => "nullable|string|max:18|unique:empresas,cnpj,{$id}",
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'plano' => 'nullable|string|in:basico,intermediario,avancado,enterprise',
            'max_filiais' => 'nullable|integer|min:1',
            'max_usuarios' => 'nullable|integer|min:1',
            'responsavel' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:9',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'observacoes' => 'nullable|string',
        ], [
            'nome.required' => 'O nome da empresa é obrigatório.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaQuery = Empresa::query();
            if ($request->auth_user->tipo !== 'superadmin') {
                $empresaQuery->where('id', $request->auth_user->empresa_id);
            }
            $empresa = $empresaQuery->find($id);

            if (!$empresa) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Empresa não encontrada.'], 404);
            }

            $empresa->update($request->only([
                'nome', 'cnpj', 'email', 'telefone', 'plano', 'max_filiais',
                'max_usuarios', 'responsavel', 'cep', 'endereco', 'numero',
                'complemento', 'bairro', 'cidade', 'estado', 'observacoes',
            ]));

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Empresa atualizada com sucesso.',
                'dados' => $empresa->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atualizar empresa.'], 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/empresas/{id}',
        summary: 'Excluir empresa',
        description: 'Soft delete. Não é possível excluir a própria empresa.',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Empresa excluída', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'mensagem', type: 'string'),
            ])),
            new OA\Response(response: 404, description: 'Não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        if ($request->auth_user->tipo !== 'superadmin') {
            return response()->json(['sucesso' => false, 'mensagem' => 'Apenas superadmin pode excluir empresas.'], 403);
        }

        if ($request->auth_user->empresa_id == $id) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Não é permitido excluir sua própria empresa.'], 400);
        }

        try {
            $empresa = Empresa::find($id);

            if (!$empresa) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Empresa não encontrada.'], 404);
            }

            $empresa->delete();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Empresa removida com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao remover empresa.'], 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/empresas/{id}/ativar',
        summary: 'Ativar empresa',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Empresa ativada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'mensagem', type: 'string')]))]
    )]
    public function ativar(Request $request, $id)
    {
        if (!$this->checkPermission($request)) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'], 403);
        }

        try {
            $empresa = Empresa::find($id);
            if (!$empresa) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Empresa não encontrada.'], 404);
            }

            $empresa->update(['status' => 'ativo']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Empresa ativada com sucesso.',
                'dados' => $empresa,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao ativar empresa.'], 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/empresas/{id}/inativar',
        summary: 'Inativar empresa',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Empresa inativada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'mensagem', type: 'string')]))]
    )]
    public function inativar(Request $request, $id)
    {
        if (!$this->checkPermission($request)) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'], 403);
        }

        try {
            $empresa = Empresa::find($id);
            if (!$empresa) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Empresa não encontrada.'], 404);
            }

            $empresa->update(['status' => 'inativo']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Empresa inativada com sucesso.',
                'dados' => $empresa,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao inativar empresa.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/empresas/{id}/logo',
        summary: 'Upload do logotipo',
        tags: ['Empresas'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [new OA\Property(property: 'logo', type: 'string', format: 'binary')])
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Logo atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))]
    )]
    public function uploadLogo(Request $request, $id)
    {
        if (!$this->checkPermission($request)) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'logo.required' => 'A imagem é obrigatória.',
            'logo.image' => 'O arquivo deve ser uma imagem.',
            'logo.mimes' => 'Formatos aceitos: jpeg, png, jpg, gif, webp.',
            'logo.max' => 'A imagem deve ter no máximo 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Arquivo inválido.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaQuery = Empresa::query();
            if ($request->auth_user->tipo !== 'superadmin') {
                $empresaQuery->where('id', $request->auth_user->empresa_id);
            }
            $empresa = $empresaQuery->find($id);

            if (!$empresa) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Empresa não encontrada.'], 404);
            }

            if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                Storage::disk('public')->delete($empresa->logo);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $empresa->update(['logo' => $path]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Logo atualizado com sucesso.',
                'dados' => ['logo' => Storage::url($path)],
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao fazer upload do logo.'], 500);
        }
    }
}
