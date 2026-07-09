<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesq_conceito_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conceito_id')->constrained('pesq_conceitos')->cascadeOnDelete();
            $table->string('descricao', 150);
            $table->decimal('valor', 8, 2);
            $table->string('cor', 7)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('conceito_id');
            $table->index(['conceito_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_conceito_itens');
    }
};
