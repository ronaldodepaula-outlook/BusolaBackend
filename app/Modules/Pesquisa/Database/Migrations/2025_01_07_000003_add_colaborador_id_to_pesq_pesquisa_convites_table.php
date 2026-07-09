<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O convite individual passa a ser gerado por Colaborador, não mais por
     * User — nem todo colaborador da empresa tem uma conta de acesso ao
     * sistema. user_id vira nullable e deixa de ser usado por convites
     * novos, mantido só por compatibilidade com convites já existentes.
     */
    public function up(): void
    {
        Schema::table('pesq_pesquisa_convites', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->foreignId('colaborador_id')->nullable()->after('pesquisa_id')
                ->constrained('pesq_colaboradores')->cascadeOnDelete();
            $table->unique(['pesquisa_id', 'colaborador_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisa_convites', function (Blueprint $table) {
            $table->dropUnique(['pesquisa_id', 'colaborador_id']);
            $table->dropConstrainedForeignId('colaborador_id');
        });
    }
};
