<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Filial;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Log;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dashboard/super-admin',
        summary: 'Dashboard do Super Administrador',
        description: 'Totais globais, últimos acessos, empresas bloqueadas e logs. Apenas Super Admin.',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dados do dashboard',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                    new OA\Property(property: 'dados', type: 'object', properties: [
                        new OA\Property(property: 'total_empresas', type: 'integer', example: 5),
                        new OA\Property(property: 'total_empresas_ativas', type: 'integer', example: 4),
                        new OA\Property(property: 'total_usuarios', type: 'integer', example: 50),
                        new OA\Property(property: 'total_filiais', type: 'integer', example: 12),
                        new OA\Property(property: 'ultimos_acessos', type: 'array', items: new OA\Items(ref: '#/components/schemas/Usuario')),
                        new OA\Property(property: 'empresas_bloqueadas', type: 'array', items: new OA\Items(ref: '#/components/schemas/Empresa')),
                        new OA\Property(property: 'ultimos_logs', type: 'array', items: new OA\Items(ref: '#/components/schemas/Log')),
                    ]),
                ])
            ),
            new OA\Response(response: 403, description: 'Acesso negado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function superAdmin(Request $request)
    {
        if ($request->auth_user->tipo !== 'superadmin') {
            return response()->json(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'], 403);
        }

        try {
            $totalEmpresas = Empresa::count();
            $totalEmpresasAtivas = Empresa::where('status', 'ativo')->count();
            $totalEmpresasInativas = Empresa::where('status', 'inativo')->count();
            $totalUsuarios = User::count();
            $totalFiliais = Filial::count();

            $ultimosAcessos = User::with('empresa:id,nome')
                ->whereNotNull('ultimo_login')
                ->orderBy('ultimo_login', 'desc')
                ->limit(10)
                ->get(['id', 'nome', 'email', 'empresa_id', 'ultimo_login', 'tipo']);

            $empresasBloqueadas = Empresa::where('status', 'bloqueado')
                ->withCount(['filiais', 'users'])
                ->get();

            $ultimosLogs = Log::with('empresa:id,nome')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $empresasPorPlano = Empresa::select('plano', DB::raw('count(*) as total'))
                ->groupBy('plano')
                ->orderBy('total', 'desc')
                ->get();

            return response()->json([
                'sucesso' => true,
                'dados' => [
                    'total_empresas' => $totalEmpresas,
                    'total_empresas_ativas' => $totalEmpresasAtivas,
                    'total_empresas_inativas' => $totalEmpresasInativas,
                    'total_usuarios' => $totalUsuarios,
                    'total_filiais' => $totalFiliais,
                    'ultimos_acessos' => $ultimosAcessos,
                    'empresas_bloqueadas' => $empresasBloqueadas,
                    'ultimos_logs' => $ultimosLogs,
                    'empresas_por_plano' => $empresasPorPlano,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao carregar dashboard.'], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/dashboard/empresa',
        summary: 'Dashboard da Empresa',
        description: 'Métricas da empresa: usuários, filiais, acessos e logs recentes.',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dados do dashboard',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'sucesso', type: 'boolean', example: true),
                    new OA\Property(property: 'dados', type: 'object', properties: [
                        new OA\Property(property: 'total_usuarios', type: 'integer', example: 20),
                        new OA\Property(property: 'total_filiais', type: 'integer', example: 3),
                        new OA\Property(property: 'total_filiais_ativas', type: 'integer', example: 3),
                        new OA\Property(property: 'ultimos_acessos', type: 'array', items: new OA\Items(ref: '#/components/schemas/Usuario')),
                        new OA\Property(property: 'logs_recentes', type: 'array', items: new OA\Items(ref: '#/components/schemas/Log')),
                    ]),
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro')),
        ]
    )]
    public function empresa(Request $request)
    {
        try {
            $empresaId = $request->empresa->id ?? $request->auth_user->empresa_id;

            $totalUsuarios = User::where('empresa_id', $empresaId)->count();
            $totalFiliais = Filial::where('empresa_id', $empresaId)->count();
            $totalFiliaisAtivas = Filial::where('empresa_id', $empresaId)->where('status', 'ativo')->count();

            $totalRoles = Role::where(function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId)->orWhere('sistema', true);
            })->count();

            $totalPermissions = Permission::count();

            $ultimosAcessos = User::where('empresa_id', $empresaId)
                ->whereNotNull('ultimo_login')
                ->orderBy('ultimo_login', 'desc')
                ->limit(10)
                ->get(['id', 'nome', 'email', 'ultimo_login', 'tipo']);

            $logsRecentes = Log::where('empresa_id', $empresaId)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $usuariosPorTipo = User::where('empresa_id', $empresaId)
                ->select('tipo', DB::raw('count(*) as total'))
                ->groupBy('tipo')
                ->get();

            $usuariosPorStatus = User::where('empresa_id', $empresaId)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            return response()->json([
                'sucesso' => true,
                'dados' => [
                    'total_usuarios' => $totalUsuarios,
                    'total_filiais' => $totalFiliais,
                    'total_filiais_ativas' => $totalFiliaisAtivas,
                    'total_roles' => $totalRoles,
                    'total_permissions' => $totalPermissions,
                    'ultimos_acessos' => $ultimosAcessos,
                    'logs_recentes' => $logsRecentes,
                    'usuarios_por_tipo' => $usuariosPorTipo,
                    'usuarios_por_status' => $usuariosPorStatus,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => 'Erro ao carregar dashboard.'], 500);
        }
    }
}
