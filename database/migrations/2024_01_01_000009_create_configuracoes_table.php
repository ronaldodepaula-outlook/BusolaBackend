<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');
            $table->foreignId('filial_id')->nullable()->constrained('filiais')->onDelete('cascade');
            $table->string('chave', 100);
            $table->text('valor')->nullable();
            $table->string('tipo', 50)->default('string'); // string, boolean, integer, json
            $table->string('grupo', 100)->nullable();
            $table->text('descricao')->nullable();
            $table->timestamps();
            $table->unique(['empresa_id', 'filial_id', 'chave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
