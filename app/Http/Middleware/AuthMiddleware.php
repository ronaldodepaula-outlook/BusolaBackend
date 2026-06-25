<?php

namespace App\Http\Middleware;

use App\Models\TokenBlacklist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates the JWT token, checks the blacklist, verifies user status
     * and binds the authenticated user to the request as `auth_user`.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Token expirado. Por favor, faça login novamente.',
                'codigo'   => 'TOKEN_EXPIRADO',
            ], Response::HTTP_UNAUTHORIZED);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Token inválido. Por favor, faça login novamente.',
                'codigo'   => 'TOKEN_INVALIDO',
            ], Response::HTTP_UNAUTHORIZED);
        } catch (JWTException $e) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Token não fornecido ou mal formatado.',
                'codigo'   => 'TOKEN_AUSENTE',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Usuário não encontrado.',
                'codigo'   => 'USUARIO_NAO_ENCONTRADO',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verify the raw token string against the blacklist table.
        $rawToken = JWTAuth::getToken()->get();

        $isBlacklisted = TokenBlacklist::where('token', $rawToken)->exists();

        if ($isBlacklisted) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Token revogado. Por favor, faça login novamente.',
                'codigo'   => 'TOKEN_REVOGADO',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Ensure the user account is active.
        if ($user->status !== 'ativo') {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Conta de usuário inativa. Entre em contato com o administrador.',
                'codigo'   => 'USUARIO_INATIVO',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Bind the authenticated user to the request for downstream middleware and controllers.
        $request->auth_user = $user;

        return $next($request);
    }
}
