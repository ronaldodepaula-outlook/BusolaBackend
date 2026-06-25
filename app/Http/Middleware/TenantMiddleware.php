<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Resolves the current tenant (Empresa) from the authenticated user and
     * binds it to the request as `empresa`.
     *
     * - Superadmin: may pass an `X-Empresa-Id` header to impersonate any tenant.
     *   If no header is provided, $request->empresa is left null (superadmin has
     *   global access and some routes may not require a specific tenant context).
     * - All other roles: empresa is resolved strictly from auth_user->empresa_id.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->auth_user;

        if (! $user) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Usuário não autenticado.',
                'codigo'   => 'NAO_AUTENTICADO',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $empresa = null;

        if ($user->isSuperAdmin()) {
            // Superadmin can scope the request to a specific tenant via header.
            $empresaIdHeader = $request->header('X-Empresa-Id');

            if ($empresaIdHeader) {
                $empresa = Empresa::find($empresaIdHeader);

                if (! $empresa) {
                    return response()->json([
                        'sucesso'  => false,
                        'mensagem' => 'Empresa informada no cabeçalho não encontrada.',
                        'codigo'   => 'EMPRESA_NAO_ENCONTRADA',
                    ], Response::HTTP_FORBIDDEN);
                }

                if ($empresa->status !== 'ativo') {
                    return response()->json([
                        'sucesso'  => false,
                        'mensagem' => 'Empresa informada está inativa.',
                        'codigo'   => 'EMPRESA_INATIVA',
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            // If no header, $empresa stays null — superadmin has unrestricted access.
        } else {
            // Regular users are always bound to their own empresa.
            if (! $user->empresa_id) {
                return response()->json([
                    'sucesso'  => false,
                    'mensagem' => 'Usuário não está vinculado a nenhuma empresa.',
                    'codigo'   => 'EMPRESA_NAO_VINCULADA',
                ], Response::HTTP_FORBIDDEN);
            }

            $empresa = Empresa::find($user->empresa_id);

            if (! $empresa) {
                return response()->json([
                    'sucesso'  => false,
                    'mensagem' => 'Empresa do usuário não encontrada.',
                    'codigo'   => 'EMPRESA_NAO_ENCONTRADA',
                ], Response::HTTP_FORBIDDEN);
            }

            if ($empresa->status !== 'ativo') {
                return response()->json([
                    'sucesso'  => false,
                    'mensagem' => 'A empresa vinculada ao usuário está inativa. Entre em contato com o administrador.',
                    'codigo'   => 'EMPRESA_INATIVA',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $request->empresa = $empresa;

        return $next($request);
    }
}
