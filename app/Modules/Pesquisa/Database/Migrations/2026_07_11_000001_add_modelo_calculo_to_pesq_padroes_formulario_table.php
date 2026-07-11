<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Define qual motor de cálculo de risco (ModeloCalculoRisco) as campanhas
     * de um Padrão de Formulário usam. Default 'nr1_completo' preserva o
     * comportamento de todo padrão/formulário já existente.
     */
    public function up(): void
    {
        Schema::table('pesq_padroes_formulario', function (Blueprint $table) {
            $table->string('modelo_calculo', 30)->default('nr1_completo')->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('pesq_padroes_formulario', function (Blueprint $table) {
            $table->dropColumn('modelo_calculo');
        });
    }
};
