<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O Fluxo 1 (ativação de conta) cria o usuário sem senha — ele só é
     * definido quando o próprio usuário conclui a ativação pelo link
     * recebido por e-mail. `->change()` exige doctrine/dbal (já instalado
     * neste projeto), garantindo portabilidade entre o MySQL de produção e
     * o SQLite usado nos testes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('senha')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('senha')->nullable(false)->change();
        });
    }
};
