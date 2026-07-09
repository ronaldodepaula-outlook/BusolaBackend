<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesq_pesquisa_respostas_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_resposta_id')->constrained('pesq_pesquisa_respostas')->cascadeOnDelete();
            $table->foreignId('pergunta_id')->constrained('pesq_perguntas')->cascadeOnDelete();
            $table->foreignId('conceito_item_id')->nullable()->constrained('pesq_conceito_itens')->nullOnDelete();
            $table->text('valor_texto')->nullable();
            $table->decimal('valor_numero', 12, 2)->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('pesquisa_resposta_id');
            $table->index('pergunta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_pesquisa_respostas_itens');
    }
};
