<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar de grados
        Schema::table('grados', function (Blueprint $table) {
            $table->dropColumn('cantidad_meses');
        });

        // 2. Agregar a niveles (YA NO SE USA)
        // Schema::table('niveles', function (Blueprint $table) {
        //     $table->integer('cantidad_colegiaturas')->nullable()->default(10)->after('descripcion');
        // });
    }

    public function down(): void
    {
        // Revertir cambios
        Schema::table('niveles', function (Blueprint $table) {
            $table->dropColumn('cantidad_colegiaturas');
        });

        Schema::table('grados', function (Blueprint $table) {
            $table->integer('cantidad_meses')->nullable()->default(10);
        });
    }
};
