<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de cada Relatório Técnico (PDF) gerado para uma campanha.
     * empresa_id é desnormalizado a partir da pesquisa para permitir a listagem
     * e o filtro cross-empresa na tela de gestão do super administrador.
     */
    public function up(): void
    {
        Schema::create('pesq_relatorios_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_id')->constrained('pesq_pesquisas')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('gerado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsavel_tecnico_nome', 150)->nullable();
            $table->string('responsavel_tecnico_registro', 60)->nullable();
            $table->string('arquivo_path', 255);
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->timestamp('gerado_em');
            $table->timestamps();

            $table->index(['empresa_id', 'gerado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_relatorios_tecnicos');
    }
};
