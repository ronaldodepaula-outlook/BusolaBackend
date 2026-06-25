<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LogController extends Controller
{
    #[OA\Get(
        path: '/api/v1/logs',
        summary: 'Listar logs de auditoria',
        description: 'Super Admin vê todos; demais veem da própria empresa.',
        tags: ['Logs'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'acao', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'modulo', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'data_inicio', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'data_fim', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-12-31')),
            new OA\Parameter(name: 'ip', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Logs paginados', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'sucesso', type: 'boolean', example: true),
            new OA\Property(property: 'dados', type: 'object', properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Log')),
                new OA\Property(property: 'total', type: 'integer'),
            ]),
        ]))]
    )]
    public function index(Request $request)
    {
        try {
            $query = Log::orderBy('created_at', 'desc');

            // Superadmin pode filtrar por empresa; outros veem apenas da própria empresa
            if ($request->auth_user->tipo === 'superadmin') {
                if ($request->filled('empresa_id')) {
                    $query->where('empresa_id', $request->empresa_id);
                }
            } else {
                $empresaId = $request->empresa->id ?? $request->auth_user->empresa_id;
                $query->where('empresa_id', $empresaId);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('acao')) {
                $query->where('acao', 'like', "%{$request->acao}%");
            }

            if ($request->filled('modulo')) {
                $query->where('modulo', $request->modulo);
            }

            if ($request->filled('data_inicio')) {
                $query->whereDate('created_at', '>=', $request->data_inicio);
            }

            if ($request->filled('data_fim')) {
                $query->whereDate('created_at', '<=', $request->data_fim);
            }

            if ($request->filled('ip')) {
                $query->where('ip', $request->ip);
            }

            if ($request->filled('status_code')) {
                $query->where('status_code', $request->status_code);
            }

            $logs = $query->paginate(20);

            return response()->json([
                'sucesso' => true,
                'dados' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao listar logs.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/logs/{id}',
        summary: 'Visualizar log completo',
        description: 'Retorna o log com payload enviado e payload anterior.',
        tags: ['Logs'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Log detalhado', content: new OA\JsonContent(properties: [new OA\Property(property: 'sucesso', type: 'boolean', example: true), new OA\Property(property: 'dados', ref: '#/components/schemas/Log')])),
            new OA\Response(response: 404, description: 'Não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function show(Request $request, $id)
    {
        try {
            $query = Log::query();

            if ($request->auth_user->tipo !== 'superadmin') {
                $empresaId = $request->empresa->id ?? $request->auth_user->empresa_id;
                $query->where('empresa_id', $empresaId);
            }

            $log = $query->find($id);

            if (!$log) {
                return response()->json(['sucesso' => false, 'mensagem' => 'Log não encontrado.'], 404);
            }

            return response()->json(['sucesso' => true, 'dados' => $log]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao buscar log.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/logs/exportar',
        summary: 'Exportar logs',
        description: 'Exporta logs como JSON. Máximo 1000 registros.',
        tags: ['Logs'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'data_inicio', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'data_fim', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'modulo', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Logs exportados', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'sucesso', type: 'boolean', example: true),
            new OA\Property(property: 'dados', type: 'array', items: new OA\Items(ref: '#/components/schemas/Log')),
            new OA\Property(property: 'total', type: 'integer'),
        ]))]
    )]
    public function exportar(Request $request)
    {
        try {
            $query = Log::orderBy('created_at', 'desc');

            if ($request->auth_user->tipo === 'superadmin') {
                if ($request->filled('empresa_id')) {
                    $query->where('empresa_id', $request->empresa_id);
                }
            } else {
                $empresaId = $request->empresa->id ?? $request->auth_user->empresa_id;
                $query->where('empresa_id', $empresaId);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('acao')) {
                $query->where('acao', 'like', "%{$request->acao}%");
            }

            if ($request->filled('modulo')) {
                $query->where('modulo', $request->modulo);
            }

            if ($request->filled('data_inicio')) {
                $query->whereDate('created_at', '>=', $request->data_inicio);
            }

            if ($request->filled('data_fim')) {
                $query->whereDate('created_at', '<=', $request->data_fim);
            }

            $logs = $query->limit(1000)->get();

            return response()->json([
                'sucesso' => true,
                'mensagem' => "Exportação com {$logs->count()} registro(s).",
                'dados' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao exportar logs.'], 500);
        }
    }
}
