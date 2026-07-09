<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesq_pesquisa_convites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_id')->constrained('pesq_pesquisas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('respondido_em')->nullable();
            $table->timestamps();

            $table->index('pesquisa_id');
            $table->unique(['pesquisa_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesq_pesquisa_convites');
    }
};
