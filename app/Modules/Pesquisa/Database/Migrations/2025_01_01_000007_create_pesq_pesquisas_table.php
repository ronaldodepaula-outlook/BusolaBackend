<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esqueleto mínimo da Fase 1 — viabiliza a checagem de versionamento do
     * Formulario ("já usado em pesquisa encerrada?"). A Fase 2 completa esta
     * tabela via ALTER TABLE com as colunas de campanha (datas, público-alvo).
     */
    public function up(): void
    {
        Schema::create('pesq_pesquisas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('formulario_id')->constrained('pesq_formularios')->restrictOnDelete();
            $table->string('status', 20)->default('rascunho');
            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
            $table->index('formulario_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_pesquisas');
    }
};
