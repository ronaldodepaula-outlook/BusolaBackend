<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_categorias', function (Blueprint $table) {
            // Vincula a categoria a um dos 11 fatores de risco psicossociais oficiais
            // (COPSOQ II / Portaria GM/MS nº 5.674/2024). Quando preenchida, a
            // severidade fixa oficial é aplicada automaticamente pelo RiscoCalculator.
            $table->string('categoria_referencia', 60)->nullable()->after('descricao');
            $table->unsignedTinyInteger('severidade')->nullable()->after('categoria_referencia');
        });
    }

    public function down(): void
    {
        Schema::table('pesq_categorias', function (Blueprint $table) {
            $table->dropColumn(['categoria_referencia', 'severidade']);
        });
    }
};
