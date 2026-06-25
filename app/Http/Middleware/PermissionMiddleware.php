<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Enforces RBAC permission checks using a permission slug passed as a
     * middleware parameter.
     *
     * Usage in routes:
     *   ->middleware('permission:empresa.criar')
     *   ->middleware('permission:usuario.editar')
     *
     * Superadmins bypass all permission checks.
     * All other users must possess the specified permission via their roles.
     *
     * @param  string  $permission  The permission slug to check (e.g. "empresa.criar").
     */
    public function handle(Request $request, Closure $next, string $permission): Response
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

        // Superadmins have unrestricted access to every resource.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Evaluate permission through the user's assigned roles.
        if (! $user->hasPermission($permission)) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Você não tem permissão para executar esta ação.',
                'codigo'   => 'PERMISSAO_NEGADA',
                'permissao_requerida' => $permission,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
