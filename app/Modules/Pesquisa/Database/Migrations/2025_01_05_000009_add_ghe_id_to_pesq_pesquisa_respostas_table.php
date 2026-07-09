<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag de GRUPO (não de indivíduo) na resposta anônima, para permitir a
     * tabulação de resultados por GHE exigida pela metodologia. Isso não
     * reintroduz identificação individual: o GHE agrega vários colaboradores,
     * e o ResultadoService ainda aplica o quantitativo mínimo de respondentes
     * antes de exibir qualquer agregado por GHE.
     */
    public function up(): void
    {
        Schema::table('pesq_pesquisa_respostas', function (Blueprint $table) {
            $table->foreignId('ghe_id')->nullable()->after('pesquisa_id')
                ->constrained('pesq_ghes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisa_respostas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ghe_id');
        });
    }
};
