<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FilialController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PerfilController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Multi-tenant REST API — Laravel 12
|
*/

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('v1')->group(function () {

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {

        // Public endpoints
        Route::post('/login',           [AuthController::class, 'login']);
        Route::post('/recuperar-senha', [AuthController::class, 'recuperarSenha']);
        Route::post('/resetar-senha',   [AuthController::class, 'resetarSenha']);

        // Authenticated endpoints
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/logout',       [AuthController::class, 'logout']);
            Route::post('/refresh',      [AuthController::class, 'refresh']);
            Route::get('/me',            [AuthController::class, 'me']);
            Route::post('/trocar-senha', [AuthController::class, 'trocarSenha']);
        });
    });

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------
    Route::prefix('dashboard')->middleware(['auth.jwt', 'tenant'])->group(function () {
        Route::get('/super-admin', [DashboardController::class, 'superAdmin'])
            ->middleware('permission:dashboard.superadmin');
        Route::get('/empresa', [DashboardController::class, 'empresa']);
    });

    // -------------------------------------------------------------------------
    // Empresas
    // -------------------------------------------------------------------------
    Route::prefix('empresas')->middleware(['auth.jwt', 'log.api'])->group(function () {
        Route::get('/',    [EmpresaController::class, 'index'])
            ->middleware('permission:empresa.listar');
        Route::post('/',   [EmpresaController::class, 'store'])
            ->middleware('permission:empresa.criar');
        Route::get('/{id}', [EmpresaController::class, 'show'])
            ->middleware('permission:empresa.visualizar');
        Route::put('/{id}', [EmpresaController::class, 'update'])
            ->middleware('permission:empresa.editar');
        Route::delete('/{id}', [EmpresaController::class, 'destroy'])
            ->middleware('permission:empresa.excluir');
        Route::patch('/{id}/ativar',   [EmpresaController::class, 'ativar'])
            ->middleware('permission:empresa.editar');
        Route::patch('/{id}/inativar', [EmpresaController::class, 'inativar'])
            ->middleware('permission:empresa.editar');
        Route::post('/{id}/logo',      [EmpresaController::class, 'uploadLogo'])
            ->middleware('permission:empresa.editar');
    });

    // -------------------------------------------------------------------------
    // Filiais
    // -------------------------------------------------------------------------
    Route::prefix('filiais')->middleware(['auth.jwt', 'tenant', 'log.api'])->group(function () {
        Route::get('/',    [FilialController::class, 'index'])
            ->middleware('permission:filial.listar');
        Route::post('/',   [FilialController::class, 'store'])
            ->middleware('permission:filial.criar');
        Route::get('/{id}', [FilialController::class, 'show'])
            ->middleware('permission:filial.visualizar');
        Route::put('/{id}', [FilialController::class, 'update'])
            ->middleware('permission:filial.editar');
        Route::delete('/{id}', [FilialController::class, 'destroy'])
            ->middleware('permission:filial.excluir');
        Route::patch('/{id}/ativar',   [FilialController::class, 'ativar'])
            ->middleware('permission:filial.editar');
        Route::patch('/{id}/inativar', [FilialController::class, 'inativar'])
            ->middleware('permission:filial.editar');
    });

    // -------------------------------------------------------------------------
    // Usuarios
    // -------------------------------------------------------------------------
    Route::prefix('usuarios')->middleware(['auth.jwt', 'tenant', 'log.api'])->group(function () {
        Route::get('/',    [UsuarioController::class, 'index'])
            ->middleware('permission:usuario.listar');
        Route::post('/',   [UsuarioController::class, 'store'])
            ->middleware('permission:usuario.criar');
        Route::get('/{id}', [UsuarioController::class, 'show'])
            ->middleware('permission:usuario.visualizar');
        Route::put('/{id}', [UsuarioController::class, 'update'])
            ->middleware('permission:usuario.editar');
        Route::delete('/{id}', [UsuarioController::class, 'destroy'])
            ->middleware('permission:usuario.excluir');
        Route::patch('/{id}/bloquear',     [UsuarioController::class, 'bloquear'])
            ->middleware('permission:usuario.bloquear');
        Route::patch('/{id}/ativar',       [UsuarioController::class, 'ativar'])
            ->middleware('permission:usuario.editar');
        Route::post('/{id}/resetar-senha', [UsuarioController::class, 'resetarSenha'])
            ->middleware('permission:usuario.resetar-senha');
        Route::post('/{id}/roles',         [UsuarioController::class, 'atribuirRoles'])
            ->middleware('permission:usuario.editar');
        Route::post('/{id}/foto',          [UsuarioController::class, 'uploadFoto'])
            ->middleware('permission:usuario.editar');
    });

    // -------------------------------------------------------------------------
    // Roles
    // -------------------------------------------------------------------------
    Route::prefix('roles')->middleware(['auth.jwt', 'tenant', 'log.api'])->group(function () {
        Route::get('/',    [RoleController::class, 'index'])
            ->middleware('permission:role.listar');
        Route::post('/',   [RoleController::class, 'store'])
            ->middleware('permission:role.criar');
        Route::get('/{id}', [RoleController::class, 'show'])
            ->middleware('permission:role.visualizar');
        Route::put('/{id}', [RoleController::class, 'update'])
            ->middleware('permission:role.editar');
        Route::delete('/{id}', [RoleController::class, 'destroy'])
            ->middleware('permission:role.excluir');
        Route::post('/{id}/duplicar',    [RoleController::class, 'duplicar'])
            ->middleware('permission:role.criar');
        Route::patch('/{id}/ativar',     [RoleController::class, 'ativar'])
            ->middleware('permission:role.editar');
        Route::patch('/{id}/inativar',   [RoleController::class, 'inativar'])
            ->middleware('permission:role.editar');
        Route::post('/{id}/permissoes',  [RoleController::class, 'atribuirPermissoes'])
            ->middleware('permission:role.editar');
    });

    // -------------------------------------------------------------------------
    // Permissoes
    // -------------------------------------------------------------------------
    Route::prefix('permissoes')->middleware(['auth.jwt', 'tenant'])->group(function () {
        Route::get('/',           [PermissaoController::class, 'index']);
        Route::get('/por-modulo', [PermissaoController::class, 'porModulo']);
        Route::post('/',          [PermissaoController::class, 'store'])
            ->middleware('permission:permissao.criar');
        Route::get('/{id}',       [PermissaoController::class, 'show']);
        Route::put('/{id}',       [PermissaoController::class, 'update'])
            ->middleware('permission:permissao.editar');
        Route::delete('/{id}',    [PermissaoController::class, 'destroy'])
            ->middleware('permission:permissao.excluir');
    });

    // -------------------------------------------------------------------------
    // Configuracoes
    // -------------------------------------------------------------------------
    Route::prefix('configuracoes')->middleware(['auth.jwt', 'tenant'])->group(function () {
        Route::get('/',           [ConfiguracaoController::class, 'index']);
        Route::post('/',          [ConfiguracaoController::class, 'salvar']);
        Route::post('/lote',      [ConfiguracaoController::class, 'salvarLote']);
        Route::get('/{chave}',    [ConfiguracaoController::class, 'show']);
        Route::delete('/{chave}', [ConfiguracaoController::class, 'deletar']);
    });

    // -------------------------------------------------------------------------
    // Logs
    // -------------------------------------------------------------------------
    Route::prefix('logs')->middleware(['auth.jwt', 'tenant'])->group(function () {
        Route::get('/',          [LogController::class, 'index'])
            ->middleware('permission:log.listar');
        Route::get('/exportar',  [LogController::class, 'exportar'])
            ->middleware('permission:log.exportar');
        Route::get('/{id}',      [LogController::class, 'show'])
            ->middleware('permission:log.visualizar');
    });

    // -------------------------------------------------------------------------
    // Perfil (authenticated user — no tenant middleware required)
    // -------------------------------------------------------------------------
    Route::prefix('perfil')->middleware('auth.jwt')->group(function () {
        Route::get('/',              [PerfilController::class, 'show']);
        Route::put('/',              [PerfilController::class, 'update']);
        Route::post('/trocar-senha', [PerfilController::class, 'trocarSenha']);
        Route::post('/foto',         [PerfilController::class, 'uploadFoto']);
        Route::get('/permissoes',    [PerfilController::class, 'permissoes']);
    });
});
