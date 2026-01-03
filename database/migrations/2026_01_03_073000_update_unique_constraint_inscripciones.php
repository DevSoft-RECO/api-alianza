<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            // 1. Soltar claves foráneas que podrían depender del índice
            $table->dropForeign(['estudiante_id']);
            $table->dropForeign(['ciclo_id']);

            // 2. Eliminar la restricción única anterior
            $table->dropUnique('inscripciones_estudiante_id_ciclo_id_unique');

            // 3. Crear la nueva restricción (Estudiante + Ciclo + Grado)
            // Nueva restricción: Permite varias inscripciones en el mismo ciclo, SIEMPRE Y CUANDO sean grados diferentes.
            // Ejemplo válido:
            // - Estudiante 1, Ciclo 2025, Grado A (Plan Diario)
            // - Estudiante 1, Ciclo 2025, Grado B (Plan Fin de Semana)
            $table->unique(['estudiante_id', 'ciclo_id', 'grado_id'], 'inscripciones_est_cic_gra_unique');

            // 4. Restaurar las claves foráneas
            $table->foreign('estudiante_id')->references('id')->on('estudiantes')->onDelete('cascade');
            $table->foreign('ciclo_id')->references('id')->on('ciclos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropUnique('inscripciones_est_cic_gra_unique');
            $table->unique(['estudiante_id', 'ciclo_id']);
        });
    }
};
