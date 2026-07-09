<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ausência de linhas para uma pesquisa = público-alvo "toda a empresa".
     * Linhas com filial_id = segmentação por filial. Linhas com user_id = usuários específicos.
     */
    public function up(): void
    {
        Schema::create('pesq_pesquisa_publico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_id')->constrained('pesq_pesquisas')->cascadeOnDelete();
            $table->foreignId('filial_id')->nullable()->constrained('filiais')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('pesquisa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_pesquisa_publico');
    }
};
