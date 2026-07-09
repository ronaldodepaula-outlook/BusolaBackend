<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_pesquisas', function (Blueprint $table) {
            // Quantitativo mínimo de respondentes por GHE para que o resultado seja
            // exibido de forma isolada nos resultados; abaixo disso o GHE é agrupado
            // com outros para preservar o anonimato (conforme metodologia COPSOQ II).
            $table->unsignedTinyInteger('minimo_respondentes')->default(5)->after('anonima');
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisas', function (Blueprint $table) {
            $table->dropColumn('minimo_respondentes');
        });
    }
};
