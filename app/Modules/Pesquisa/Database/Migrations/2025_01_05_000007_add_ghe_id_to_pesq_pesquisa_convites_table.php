<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_pesquisa_convites', function (Blueprint $table) {
            // Snapshot do GHE do colaborador convidado, capturado na geração do
            // convite. Usado apenas para agregação por grupo nos resultados — não
            // compromete o anonimato do conteúdo da resposta em si.
            $table->foreignId('ghe_id')->nullable()->after('user_id')
                ->constrained('pesq_ghes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisa_convites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ghe_id');
        });
    }
};
