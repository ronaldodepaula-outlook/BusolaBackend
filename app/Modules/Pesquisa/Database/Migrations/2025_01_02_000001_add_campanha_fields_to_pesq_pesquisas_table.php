<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_pesquisas', function (Blueprint $table) {
            $table->string('nome', 150)->nullable()->after('formulario_id');
            $table->text('descricao')->nullable()->after('nome');
            $table->date('data_inicio')->nullable()->after('descricao');
            $table->date('data_fim')->nullable()->after('data_inicio');
            $table->boolean('anonima')->default(false)->after('data_fim');
            $table->foreignId('criado_por')->nullable()->after('anonima')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('criado_por');
            $table->dropColumn(['nome', 'descricao', 'data_inicio', 'data_fim', 'anonima']);
        });
    }
};
