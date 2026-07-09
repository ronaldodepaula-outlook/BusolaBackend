<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesq_formularios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_raiz_id')->nullable()->constrained('pesq_formularios')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->string('codigo', 50);
            $table->text('descricao')->nullable();
            $table->string('status', 20)->default('rascunho');
            $table->string('tipo', 10);
            $table->unsignedInteger('versao')->default(1);
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
            $table->index('status');
            $table->index(['codigo', 'empresa_id']);
            $table->unique(['formulario_raiz_id', 'versao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_formularios');
    }
};
