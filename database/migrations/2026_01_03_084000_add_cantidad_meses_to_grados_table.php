<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            // Cantidad de meses de colegiatura (Default 10 para Ene-Oct)
            $table->integer('cantidad_meses')->nullable()->default(10)->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropColumn('cantidad_meses');
        });
    }
};
