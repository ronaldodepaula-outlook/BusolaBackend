<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mapeamento usuário → setor isolado dentro do módulo (não altera a tabela
        // `users` do core) para manter o módulo de Pesquisas desacoplado do restante
        // do sistema.
        Schema::create('pesq_usuario_setores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('setor_id')->constrained('pesq_setores')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_usuario_setores');
    }
};
