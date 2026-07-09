<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesq_perguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategoria_id')->constrained('pesq_subcategorias')->cascadeOnDelete();
            $table->foreignId('formulario_id')->constrained('pesq_formularios')->cascadeOnDelete();
            $table->foreignId('conceito_id')->nullable()->constrained('pesq_conceitos')->nullOnDelete();
            $table->foreignId('origem_id')->nullable()->constrained('pesq_perguntas')->nullOnDelete();
            $table->string('tipo_pergunta', 30);
            $table->string('texto', 500);
            $table->text('descricao')->nullable();
            $table->boolean('obrigatoria')->default(true);
            $table->boolean('permite_observacao')->default(false);
            $table->boolean('permite_anexo')->default(false);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('subcategoria_id');
            $table->index('formulario_id');
            $table->index('conceito_id');
            $table->index(['subcategoria_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_perguntas');
    }
};
