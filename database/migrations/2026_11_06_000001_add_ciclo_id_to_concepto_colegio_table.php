<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concepto_colegio', function (Blueprint $table) {
            $table->foreignId('ciclo_id')->nullable()->after('id')->constrained('ciclos')->cascadeOnDelete();
        });

        // Asignar el ciclo 2026 (ID 1) a todos los precios existentes
        $primerCiclo = DB::table('ciclos')->orderBy('id', 'asc')->first();
        if ($primerCiclo) {
            DB::table('concepto_colegio')->whereNull('ciclo_id')->update(['ciclo_id' => $primerCiclo->id]);
        }
    }

    public function down(): void
    {
        Schema::table('concepto_colegio', function (Blueprint $table) {
            $table->dropForeign(['ciclo_id']);
            $table->dropColumn('ciclo_id');
        });
    }
};
