<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');
            $table->foreignId('filial_id')->nullable()->constrained('filiais')->onDelete('set null');
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha');
            $table->string('foto')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->enum('tipo', ['superadmin', 'admin', 'gerente', 'usuario'])->default('usuario');
            $table->enum('status', ['ativo', 'inativo', 'bloqueado'])->default('ativo');
            $table->timestamp('ultimo_login')->nullable();
            $table->string('token_reset_senha')->nullable();
            $table->timestamp('token_reset_expira_em')->nullable();
            $table->boolean('primeiro_acesso')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
