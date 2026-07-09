<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colaborador da empresa — a pessoa física que efetivamente recebe o
     * convite individual da pesquisa, independente de possuir (ou não) uma
     * conta de acesso ao sistema (`users`). Dados de identificação sensíveis
     * (CPF, data de nascimento) são armazenados apenas de forma criptografada
     * (cast `encrypted` no model): no banco, essas colunas contêm apenas o
     * texto cifrado, nunca o valor em claro — só a aplicação, com a
     * APP_KEY, consegue decifrá-los. `cpf_hash` é um hash determinístico
     * (não reversível) usado exclusivamente para checar duplicidade por
     * empresa sem nunca expor ou comparar o CPF em claro.
     */
    public function up(): void
    {
        Schema::create('pesq_colaboradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('filial_id')->nullable()->constrained('filiais')->nullOnDelete();
            $table->foreignId('setor_id')->nullable()->constrained('pesq_setores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('matricula', 40)->nullable();
            $table->string('nome', 150);
            $table->string('email', 150)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->boolean('ativo')->default(true);

            // Dados sensíveis (LGPD) — sempre armazenados criptografados.
            $table->text('cpf')->nullable();
            $table->string('cpf_hash', 64)->nullable();
            $table->text('data_nascimento')->nullable();

            // Rastreabilidade / responsabilização (accountability, Art. 6º VIII da LGPD).
            $table->string('origem', 20)->default('manual');
            $table->foreignId('importado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('base_legal_lgpd', 255)->nullable();
            $table->timestamp('consentimento_em')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'cpf_hash']);
            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_colaboradores');
    }
};
