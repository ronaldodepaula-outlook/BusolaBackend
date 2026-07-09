<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesq_subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('pesq_categorias')->cascadeOnDelete();
            $table->foreignId('formulario_id')->constrained('pesq_formularios')->cascadeOnDelete();
            $table->foreignId('origem_id')->nullable()->constrained('pesq_subcategorias')->nullOnDelete();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('categoria_id');
            $table->index('formulario_id');
            $table->index(['categoria_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_subcategorias');
    }
};
