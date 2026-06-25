<?php

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\FilialMiddleware;
use App\Http\Middleware\LogMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'auth.jwt'   => AuthMiddleware::class,
            'tenant'     => TenantMiddleware::class,
            'filial'     => FilialMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'log.api'    => LogMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sucesso'  => false,
                    'mensagem' => $e->getMessage() ?: 'Erro interno do servidor.',
                ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
            }
        });
    })->create();
