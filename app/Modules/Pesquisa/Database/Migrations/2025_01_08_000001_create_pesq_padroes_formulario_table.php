<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Padrão/norma que um formulário segue (ex.: "COPSOQ II", "NR-1",
     * "ISO 45003" ou um padrão específico de uma empresa). empresa_id nulo =
     * padrão global, disponível para todas as empresas — mesma convenção de
     * escopo já usada em pesq_formularios.tipo/empresa_id.
     */
    public function up(): void
    {
        Schema::create('pesq_padroes_formulario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_padroes_formulario');
    }
};
