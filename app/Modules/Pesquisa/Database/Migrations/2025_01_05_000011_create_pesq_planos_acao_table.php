<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Instância do plano de ação de uma campanha: gerada automaticamente a
     * partir da classificação de risco (categoria × GHE) e do template
     * correspondente, mas editável/acompanhável pela empresa depois.
     */
    public function up(): void
    {
        Schema::create('pesq_planos_acao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_id')->constrained('pesq_pesquisas')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('pesq_categorias')->cascadeOnDelete();
            $table->foreignId('ghe_id')->nullable()->constrained('pesq_ghes')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('pesq_plano_acao_templates')->nullOnDelete();
            $table->string('tipo_controle', 20);
            $table->string('nivel_risco', 20);
            $table->string('farol', 20);
            $table->text('acao');
            $table->text('como_executar')->nullable();
            $table->string('evidencia', 255)->nullable();
            $table->string('responsavel', 150)->nullable();
            $table->string('prazo', 30)->nullable();
            $table->string('status', 20)->default('pendente');
            $table->timestamp('concluido_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['pesquisa_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_planos_acao');
    }
};
