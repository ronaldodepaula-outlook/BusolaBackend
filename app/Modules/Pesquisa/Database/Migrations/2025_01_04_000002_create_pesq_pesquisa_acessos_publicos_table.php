<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rastreia acessos ao link GLOBAL (compartilhado) de uma campanha, para
     * permitir no máximo uma resposta por dispositivo/navegador — via um
     * token de sessão gerado no primeiro acesso e guardado em cookie no
     * navegador do respondente, com o IP registrado apenas para auditoria
     * (não é usado sozinho para bloquear, já que várias pessoas podem
     * legitimamente compartilhar o mesmo IP de rede). Assim como
     * pesq_pesquisa_respostas, esta tabela nunca é referenciada pela
     * resposta em si — sabemos que "aquele dispositivo" respondeu, não o
     * que foi respondido.
     */
    public function up(): void
    {
        Schema::create('pesq_pesquisa_acessos_publicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_id')->constrained('pesq_pesquisas')->cascadeOnDelete();
            $table->string('sessao_token', 64);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('respondido_em')->nullable();
            $table->timestamps();

            $table->unique(['pesquisa_id', 'sessao_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_pesquisa_acessos_publicos');
    }
};
