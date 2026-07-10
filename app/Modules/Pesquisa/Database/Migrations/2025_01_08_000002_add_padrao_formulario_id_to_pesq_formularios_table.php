<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_formularios', function (Blueprint $table) {
            $table->foreignId('padrao_formulario_id')->nullable()->after('empresa_id')
                ->constrained('pesq_padroes_formulario')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesq_formularios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('padrao_formulario_id');
        });
    }
};
