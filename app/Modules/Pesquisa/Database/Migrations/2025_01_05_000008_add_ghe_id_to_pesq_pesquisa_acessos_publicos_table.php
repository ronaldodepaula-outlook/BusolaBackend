<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_pesquisa_acessos_publicos', function (Blueprint $table) {
            // No link global não há usuário identificado; o respondente escolhe o
            // próprio setor no início do fluxo e o GHE é resolvido a partir dele.
            $table->foreignId('ghe_id')->nullable()->after('pesquisa_id')
                ->constrained('pesq_ghes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisa_acessos_publicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ghe_id');
        });
    }
};
