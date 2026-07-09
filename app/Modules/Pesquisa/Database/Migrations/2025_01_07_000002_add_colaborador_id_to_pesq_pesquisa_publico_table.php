<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O público-alvo por "usuários específicos" passa a apontar para
     * Colaborador (a pessoa da empresa), não mais para User (conta de
     * acesso ao sistema). user_id é mantido apenas por compatibilidade
     * com dados já existentes.
     */
    public function up(): void
    {
        Schema::table('pesq_pesquisa_publico', function (Blueprint $table) {
            $table->foreignId('colaborador_id')->nullable()->after('filial_id')
                ->constrained('pesq_colaboradores')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisa_publico', function (Blueprint $table) {
            $table->dropConstrainedForeignId('colaborador_id');
        });
    }
};
