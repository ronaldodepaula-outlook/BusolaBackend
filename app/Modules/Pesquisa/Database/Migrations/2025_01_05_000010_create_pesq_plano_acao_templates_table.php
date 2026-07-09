<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biblioteca de referência (não específica de empresa) que gera o texto do
     * plano de ação a partir de Categoria oficial × Nível de risco × Tipo de
     * controle, seguindo o padrão observado na planilha de cálculo oficial
     * (BASE_ACAO): a ação-base é fixa por categoria/tipo, e o nível de risco
     * apenas altera o prefixo/urgência/prazo (ver Enums\NivelBaseAcao).
     */
    public function up(): void
    {
        Schema::create('pesq_plano_acao_templates', function (Blueprint $table) {
            $table->id();
            $table->string('categoria_referencia', 60);
            $table->string('nivel_base_acao', 20);
            $table->string('tipo_controle', 20);
            $table->string('acao', 255);
            $table->text('como_executar');
            $table->string('evidencia', 255);
            $table->string('responsavel_padrao', 150);
            $table->string('prazo', 30);
            $table->timestamps();

            $table->unique(['categoria_referencia', 'nivel_base_acao', 'tipo_controle'], 'pesq_pat_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_plano_acao_templates');
    }
};
