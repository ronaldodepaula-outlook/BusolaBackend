<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ConfiguracaoController extends Controller
{
    private function getEmpresaId(Request $request): int
    {
        return $request->empresa->id ?? $request->auth_user->empresa_id;
    }

    private function getFilialId(Request $request): ?int
    {
        return $request->filial->id ?? $request->auth_user->filial_id ?? null;
    }

    #[OA\Get(path: '/api/v1/configuracoes', summary: 'Listar configurações', tags: ['Configurações'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'grupo', in: 'query', required: false, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Configurações', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', type: 'object')]))])]
    public function index(Request $request)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $filialId = $this->getFilialId($request);

            $query = Configuracao::where('empresa_id', $empresaId);

            if ($filialId) {
                $query->where(function ($q) use ($filialId) {
                    $q->whereNull('filial_id')->orWhere('filial_id', $filialId);
                });
            } else {
                $query->whereNull('filial_id');
            }

            $configs = $query->orderBy('grupo')->orderBy('chave')->get();

            $agrupadas = $configs->groupBy('grupo')->map(function ($items, $grupo) {
                return [
                    'grupo' => $grupo ?: 'geral',
                    'configuracoes' => $items->values(),
                ];
            })->values();

            return response()->json([
                'sucesso' => true,
                'dados' => $agrupadas,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao listar configurações.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/configuracoes/{chave}',
        summary: 'Buscar configuração por chave',
        tags: ['Configurações'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'chave', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'smtp.host'))],
        responses: [
            new OA\Response(response: 200, description: 'Configuração encontrada', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Configuracao')])),
            new OA\Response(response: 404, description: 'Chave não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function show(Request $request, $chave)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $filialId = $this->getFilialId($request);

            $config = Configuracao::where('empresa_id', $empresaId)
                ->where('chave', $chave)
                ->where('filial_id', $filialId)
                ->first();

            if (!$config) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Configuração não encontrada.'], 404);
            }

            return response()->json(['sucesso' => true, 'dados' => $config]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar configuração.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/configuracoes',
        summary: 'Salvar configuração (upsert)',
        tags: ['Configurações'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['chave', 'valor'], properties: [
            new OA\Property(property: 'chave', type: 'string', example: 'smtp.host'),
            new OA\Property(property: 'valor', type: 'string', example: 'smtp.gmail.com'),
            new OA\Property(property: 'tipo', type: 'string', enum: ['string', 'boolean', 'integer', 'json']),
            new OA\Property(property: 'grupo', type: 'string', example: 'email'),
        ])),
        responses: [new OA\Response(response: 200, description: 'Salvo', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))]
    )]
    public function salvar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chave' => 'required|string|max:255',
            'valor' => 'nullable|string',
            'tipo' => 'nullable|in:string,integer,boolean,json,float',
            'grupo' => 'nullable|string|max:100',
            'descricao' => 'nullable|string|max:500',
        ], [
            'chave.required' => 'A chave da configuração é obrigatória.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $filialId = $this->getFilialId($request);

            $config = Configuracao::updateOrCreate(
                [
                    'empresa_id' => $empresaId,
                    'filial_id' => $filialId,
                    'chave' => $request->chave,
                ],
                [
                    'valor' => $request->valor,
                    'tipo' => $request->tipo ?? 'string',
                    'grupo' => $request->grupo,
                    'descricao' => $request->descricao,
                ]
            );

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Configuração salva com sucesso.',
                'dados' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao salvar configuração.'], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/configuracoes/lote',
        summary: 'Salvar múltiplas configurações',
        tags: ['Configurações'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['configuracoes'], properties: [
            new OA\Property(property: 'configuracoes', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'chave', type: 'string'),
                new OA\Property(property: 'valor', type: 'string'),
                new OA\Property(property: 'grupo', type: 'string'),
            ])),
        ])),
        responses: [new OA\Response(response: 200, description: 'Salvas', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'mensagem', type: 'string')]))]
    )]
    public function salvarLote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'configuracoes' => 'required|array|min:1',
            'configuracoes.*.chave' => 'required|string|max:255',
            'configuracoes.*.valor' => 'nullable|string',
            'configuracoes.*.tipo' => 'nullable|in:string,integer,boolean,json,float',
            'configuracoes.*.grupo' => 'nullable|string|max:100',
            'configuracoes.*.descricao' => 'nullable|string|max:500',
        ], [
            'configuracoes.required' => 'Informe ao menos uma configuração.',
            'configuracoes.*.chave.required' => 'Cada configuração deve ter uma chave.',
        ]);

        if ($validator->fails()) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Dados inválidos.', 'erros' => $validator->errors()], 422);
        }

        try {
            $empresaId = $this->getEmpresaId($request);
            $filialId = $this->getFilialId($request);
            $salvas = [];

            foreach ($request->configuracoes as $item) {
                $config = Configuracao::updateOrCreate(
                    [
                        'empresa_id' => $empresaId,
                        'filial_id' => $filialId,
                        'chave' => $item['chave'],
                    ],
                    [
                        'valor' => $item['valor'] ?? null,
                        'tipo' => $item['tipo'] ?? 'string',
                        'grupo' => $item['grupo'] ?? null,
                        'descricao' => $item['descricao'] ?? null,
                    ]
                );
                $salvas[] = $config;
            }

            return response()->json([
                'sucesso' => true,
                'mensagem' => count($salvas) . ' configuração(ões) salva(s) com sucesso.',
                'dados' => $salvas,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao salvar configurações em lote.'], 500);
        }
    }

    #[OA\Delete(path: '/api/v1/configuracoes/{chave}', summary: 'Remover configuração', tags: ['Configurações'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'chave', in: 'path', required: true, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Removida', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true)]))])]
    public function deletar(Request $request, $chave)
    {
        try {
            $empresaId = $this->getEmpresaId($request);
            $filialId = $this->getFilialId($request);

            $config = Configuracao::where('empresa_id', $empresaId)
                ->where('chave', $chave)
                ->where('filial_id', $filialId)
                ->first();

            if (!$config) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Configuração não encontrada.'], 404);
            }

            $config->delete();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Configuração removida com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao remover configuração.'], 500);
        }
    }
}
