<?php

namespace App\Http\Controllers;

use App\Models\Filial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class FilialController extends Controller
{
    private function getEmpresaId(Request $request): int
    {
        return $request->empresa->id ?? $request->auth_user->empresa_id;
    }

    #[OA\Get(
        path: '/api/v1/filiais',
        summary: 'Listar filiais',
        description: 'Filiais da empresa. Super Admin pode usar X-Empresa-Id.',
        tags: ['Filiais'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'X-Empresa-Id', in: 'header', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ativo', 'inativo'])),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de filiais', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', type: 'object', properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Filial')),
                    new OA\Property(property: 'total', type: 'integer'),
                ]),
            ])),
        ]
    )]
    public function index(Request $request)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $query = Filial::where('empresa_id', $empresaId);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%")
                      ->orWhere('cidade', 'like', "%{$search}%");
                });
            }

            $filiais = $query->orderBy('nome')->paginate(15);

            return response()->json([
                'sucesso' => true,
                'dados' => $filiais,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao listar filiais.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/filiais',
        summary: 'Cadastrar filial',
        description: 'Valida limite max_filiais da empresa.',
        tags: ['Filiais'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'Filial Sul'),
                    new OA\Property(property: 'codigo', type: 'string', example: 'FIL002'),
                    new OA\Property(property: 'cnpj', type: 'string'),
                    new OA\Property(property: 'responsavel', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'telefone', type: 'string'),
                    new OA\Property(property: 'horario_abertura', type: 'string', example: '08:00'),
                    new OA\Property(property: 'horario_fechamento', type: 'string', example: '18:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Filial criada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                new OA\Property(property: 'dados', ref: '#/components/schemas/Filial'),
            ])),
            new OA\Response(response: 422, description: 'Limite atingido ou validação falhou', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'cnpj' => 'nullable|string|max:18',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'responsavel' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:9',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'horario_abertura' => 'nullable|date_format:H:i',
            'horario_fechamento' => 'nullable|date_format:H:i',
            'observacoes' => 'nullable|string',
        ], [
            'nome.required' => 'O nome da filial é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresa = $request->empresa ?? $request->auth_user->empresa;
            $empresaId = $empresa->id ?? $request->auth_user->empresa_id;

            $totalFiliais = Filial::where('empresa_id', $empresaId)->count();
            $maxFiliais = $empresa->max_filiais ?? PHP_INT_MAX;

            if ($totalFiliais >= $maxFiliais) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => "Limite de filiais atingido ({$maxFiliais}). Atualize seu plano para adicionar mais.",
                ], 400);
            }

            $filial = Filial::create(array_merge(
                $request->only([
                    'nome', 'codigo', 'cnpj', 'email', 'telefone', 'responsavel',
                    'cep', 'endereco', 'numero', 'complemento', 'bairro', 'cidade',
                    'estado', 'horario_abertura', 'horario_fechamento', 'observacoes',
                ]),
                ['empresa_id' => $empresaId, 'status' => 'ativo']
            ));

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Filial criada com sucesso.',
                'dados' => $filial,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao criar filial.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/filiais/{id}',
        summary: 'Visualizar filial',
        tags: ['Filiais'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados da filial', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Filial')])),
            new OA\Response(response: 404, description: 'Não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function show(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $filial = Filial::where('empresa_id', $empresaId)->find($id);

            if (!$filial) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Filial não encontrada.'], 404);
            }

            return response()->json(['sucesso' => true, 'dados' => $filial]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar filial.'], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/filiais/{id}',
        summary: 'Atualizar filial',
        tags: ['Filiais'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'nome', type: 'string'),
            new OA\Property(property: 'codigo', type: 'string'),
            new OA\Property(property: 'responsavel', type: 'string'),
            new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo']),
        ])),
        responses: [new OA\Response(response: 200, description: 'Filial atualizada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Filial')]))]
    )]
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'cnpj' => 'nullable|string|max:18',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'responsavel' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:9',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'horario_abertura' => 'nullable|date_format:H:i',
            'horario_fechamento' => 'nullable|date_format:H:i',
            'observacoes' => 'nullable|string',
        ], [
            'nome.required' => 'O nome da filial é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $filial = Filial::where('empresa_id', $empresaId)->find($id);

            if (!$filial) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Filial não encontrada.'], 404);
            }

            $filial->update($request->only([
                'nome', 'codigo', 'cnpj', 'email', 'telefone', 'responsavel',
                'cep', 'endereco', 'numero', 'complemento', 'bairro', 'cidade',
                'estado', 'horario_abertura', 'horario_fechamento', 'observacoes',
            ]));

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Filial atualizada com sucesso.',
                'dados' => $filial->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao atualizar filial.'], 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/filiais/{id}',
        summary: 'Excluir filial',
        tags: ['Filiais'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Filial excluída', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'mensagem', type: 'string')]))]
    )]
    public function destroy(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $filial = Filial::where('empresa_id', $empresaId)->find($id);

            if (!$filial) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Filial não encontrada.'], 404);
            }

            $filial->delete();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Filial removida com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao remover filial.'], 500);
        }
    }

    #[OA\Patch(path: '/api/v1/filiais/{id}/ativar', summary: 'Ativar filial', tags: ['Filiais'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Ativada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function ativar(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $filial = Filial::where('empresa_id', $empresaId)->find($id);

            if (!$filial) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Filial não encontrada.'], 404);
            }

            $filial->update(['status' => 'ativo']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Filial ativada com sucesso.',
                'dados' => $filial,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao ativar filial.'], 500);
        }
    }

    #[OA\Patch(path: '/api/v1/filiais/{id}/inativar', summary: 'Inativar filial', tags: ['Filiais'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Inativada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function inativar(Request $request, $id)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $filial = Filial::where('empresa_id', $empresaId)->find($id);

            if (!$filial) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Filial não encontrada.'], 404);
            }

            $filial->update(['status' => 'inativo']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Filial inativada com sucesso.',
                'dados' => $filial,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao inativar filial.'], 500);
        }
    }
}
