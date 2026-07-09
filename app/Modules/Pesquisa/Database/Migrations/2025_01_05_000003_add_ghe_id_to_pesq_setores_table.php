<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_setores', function (Blueprint $table) {
            // Um setor pertence a no máximo um GHE (Grupo Homogêneo de Exposição).
            $table->foreignId('ghe_id')->nullable()->after('empresa_id')
                ->constrained('pesq_ghes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesq_setores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ghe_id');
        });
    }
};
