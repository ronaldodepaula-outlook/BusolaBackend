<?php

namespace App\Http\Middleware;

use App\Models\Filial;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilialMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Resolves the current Filial context and binds it to the request as `filial`.
     *
     * Resolution rules:
     * - superadmin / admin: may pass an `X-Filial-Id` header. If omitted, no
     *   filial context is set (these roles often perform cross-filial operations).
     * - gerente / usuario: filial is resolved strictly from auth_user->filial_id.
     *
     * This middleware is OPTIONAL — routes that do not require a filial context
     * will simply have `$request->filial === null`. No error is returned when a
     * filial cannot be resolved unless the user is a gerente/usuario and their
     * filial_id is set but the record is missing/invalid.
     *
     * When a filial IS resolved it is validated to belong to $request->empresa
     * (when empresa context is available).
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->auth_user;

        if (! $user) {
            // Auth has not run yet or failed — skip silently; AuthMiddleware
            // is responsible for blocking unauthenticated requests.
            $request->filial = null;

            return $next($request);
        }

        $filial  = null;
        $tipo    = $user->tipo;

        if (in_array($tipo, ['superadmin', 'admin'], true)) {
            // Privileged roles: honour the X-Filial-Id header if present.
            $filialIdHeader = $request->header('X-Filial-Id');

            if ($filialIdHeader) {
                $filial = Filial::find($filialIdHeader);

                if (! $filial) {
                    return response()->json([
                        'sucesso'  => false,
                        'mensagem' => 'Filial informada no cabeçalho não encontrada.',
                        'codigo'   => 'FILIAL_NAO_ENCONTRADA',
                    ], Response::HTTP_FORBIDDEN);
                }

                // Validate that the filial belongs to the current empresa context.
                if ($request->empresa && $filial->empresa_id !== $request->empresa->id) {
                    return response()->json([
                        'sucesso'  => false,
                        'mensagem' => 'A filial informada não pertence à empresa atual.',
                        'codigo'   => 'FILIAL_EMPRESA_DIVERGENTE',
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            // No header — $filial stays null (no filial scoping needed).
        } else {
            // gerente / usuario: always bound to their own filial.
            if ($user->filial_id) {
                $filial = Filial::find($user->filial_id);

                if (! $filial) {
                    return response()->json([
                        'sucesso'  => false,
                        'mensagem' => 'Filial do usuário não encontrada.',
                        'codigo'   => 'FILIAL_NAO_ENCONTRADA',
                    ], Response::HTTP_FORBIDDEN);
                }

                // Cross-check against the empresa context when available.
                if ($request->empresa && $filial->empresa_id !== $request->empresa->id) {
                    return response()->json([
                        'sucesso'  => false,
                        'mensagem' => 'A filial do usuário não pertence à empresa atual.',
                        'codigo'   => 'FILIAL_EMPRESA_DIVERGENTE',
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            // If filial_id is null the user has no filial binding — continue without error.
        }

        $request->filial = $filial;

        return $next($request);
    }
}
