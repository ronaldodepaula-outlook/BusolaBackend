<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesq_conceitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->string('tipo', 30);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_conceitos');
    }
};
