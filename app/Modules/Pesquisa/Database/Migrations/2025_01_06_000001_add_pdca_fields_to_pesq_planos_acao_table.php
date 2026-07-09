<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modela o Plano de Ação como um ciclo PDCA explícito (Planejar → Executar
     * → Verificar → Agir), conforme a Seção 3.1 da metodologia: cada ação
     * nasce em "planejar" (já com ação/responsável/prazo definidos na
     * geração automática) e avança de fase mediante evidência registrada.
     * Se o "Agir" concluir que a ação não foi eficaz, um novo ciclo é aberto
     * automaticamente (ciclo_pdca é incrementado e a fase volta a "planejar"),
     * com o ciclo anterior preservado em historico_pdca.
     */
    public function up(): void
    {
        Schema::table('pesq_planos_acao', function (Blueprint $table) {
            $table->string('fase_pdca', 20)->default('planejar')->after('status');
            $table->unsignedInteger('ciclo_pdca')->default(1)->after('fase_pdca');

            $table->timestamp('executado_em')->nullable()->after('ciclo_pdca');
            $table->text('evidencia_execucao')->nullable()->after('executado_em');

            $table->timestamp('verificado_em')->nullable()->after('evidencia_execucao');
            $table->foreignId('verificado_por')->nullable()->after('verificado_em')->constrained('users')->nullOnDelete();
            $table->text('parecer_verificacao')->nullable()->after('verificado_por');

            $table->timestamp('agido_em')->nullable()->after('parecer_verificacao');
            $table->string('eficacia', 25)->nullable()->after('agido_em');
            $table->boolean('necessita_nova_acao')->default(false)->after('eficacia');

            $table->json('historico_pdca')->nullable()->after('necessita_nova_acao');
        });
    }

    public function down(): void
    {
        Schema::table('pesq_planos_acao', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verificado_por');
            $table->dropColumn([
                'fase_pdca', 'ciclo_pdca', 'executado_em', 'evidencia_execucao',
                'verificado_em', 'parecer_verificacao', 'agido_em', 'eficacia',
                'necessita_nova_acao', 'historico_pdca',
            ]);
        });
    }
};
