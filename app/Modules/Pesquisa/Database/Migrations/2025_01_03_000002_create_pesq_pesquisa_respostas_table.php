<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabeçalho da resposta — deliberadamente SEM user_id/convite_id. Essa
     * ausência de FK é o que garante que o conteúdo da resposta não pode ser
     * ligado a quem respondeu, mesmo sabendo (via pesq_pesquisa_convites)
     * quem foi convidado e quem já usou o link.
     */
    public function up(): void
    {
        Schema::create('pesq_pesquisa_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_id')->constrained('pesq_pesquisas')->cascadeOnDelete();
            $table->timestamp('iniciado_em');
            $table->timestamp('finalizado_em')->nullable();
            $table->string('status', 20)->default('em_andamento');
            $table->timestamps();

            $table->index('pesquisa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_pesquisa_respostas');
    }
};
