<?php

namespace App\Http\Middleware;

use App\Models\Log;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogMiddleware
{
    /**
     * Route prefixes that should never be logged (e.g. health checks).
     */
    private const IGNORED_PATHS = [
        'health',
        'ping',
        'up',
    ];

    /**
     * Request payload keys that must be redacted before persisting to the log.
     */
    private const SENSITIVE_KEYS = [
        'senha',
        'password',
        'token',
        'token_reset_senha',
        'nova_senha',
        'confirmacao_senha',
        'senha_atual',
        'secret',
        'api_key',
    ];

    /**
     * Handle an incoming request.
     *
     * The actual log entry is written AFTER the response is produced so that
     * the HTTP status code can be captured.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip logging for health-check and similar utility routes.
        if ($this->shouldSkip($request)) {
            return $response;
        }

        $this->log($request, $response);

        return $response;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Determine whether this request should be excluded from logging.
     */
    private function shouldSkip(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        foreach (self::IGNORED_PATHS as $ignored) {
            if ($path === $ignored || str_starts_with($path, $ignored . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Persist the log entry, failing gracefully so that a logging error never
     * breaks the application response.
     */
    private function log(Request $request, Response $response): void
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = $request->auth_user ?? null;

            $empresaId  = null;
            $filialId   = null;
            $userName   = null;

            if ($user) {
                $empresaId = $user->empresa_id;
                $filialId  = $user->filial_id;
                $userName  = $user->nome ?? $user->email;
            }

            // Override empresa/filial with the resolved tenant context when available.
            if ($request->empresa) {
                $empresaId = $request->empresa->id;
            }

            if ($request->filial) {
                $filialId = $request->filial->id;
            }

            // Build the sanitised payload from the request body.
            $payload = $this->sanitizePayload($request->except([]));

            // Derive the module name from the first significant path segment.
            $modulo = $this->resolveModule($request);

            Log::create([
                'user_id'      => $user?->id,
                'empresa_id'   => $empresaId,
                'filial_id'    => $filialId,
                'usuario_nome' => $userName,
                'acao'         => strtoupper($request->method()),
                'modulo'       => $modulo,
                'rota'         => '/' . ltrim($request->path(), '/'),
                'metodo'       => strtoupper($request->method()),
                'payload'      => ! empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                'ip'           => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'status_code'  => $response->getStatusCode(),
            ]);
        } catch (Throwable $e) {
            // Never allow a logging failure to propagate to the caller.
            // In production consider writing to a fallback channel (e.g. Laravel Log).
            \Illuminate\Support\Facades\Log::warning(
                'LogMiddleware: falha ao gravar log no banco de dados.',
                ['erro' => $e->getMessage()]
            );
        }
    }

    /**
     * Recursively remove sensitive keys from the payload array.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizePayload($value);
            }
        }

        return $data;
    }

    /**
     * Resolve a human-readable module name from the request path.
     *
     * For a path like `/api/v1/empresas/5/usuarios` the module is `empresas`.
     * Falls back to the raw path when no meaningful segment is found.
     */
    private function resolveModule(Request $request): string
    {
        $segments = array_values(
            array_filter(
                explode('/', $request->path()),
                static fn (string $s) => $s !== '' && $s !== 'api' && ! preg_match('/^v\d+$/', $s)
            )
        );

        return $segments[0] ?? $request->path();
    }
}
