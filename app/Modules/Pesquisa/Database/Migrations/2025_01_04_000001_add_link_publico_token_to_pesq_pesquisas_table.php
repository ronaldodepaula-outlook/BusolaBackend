<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesq_pesquisas', function (Blueprint $table) {
            $table->string('link_publico_token', 64)->nullable()->unique()->after('anonima');
        });
    }

    public function down(): void
    {
        Schema::table('pesq_pesquisas', function (Blueprint $table) {
            $table->dropColumn('link_publico_token');
        });
    }
};
