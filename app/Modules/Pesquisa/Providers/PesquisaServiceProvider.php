<?php

namespace App\Modules\Pesquisa\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PesquisaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pesquisa');

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
