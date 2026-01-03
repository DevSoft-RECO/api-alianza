<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiamos duplicados previos para poder aplicar la restricción
        // Mantiene el ID más reciente y borra los antiguos duplicados
        DB::statement("
            DELETE t1 FROM inscripciones t1
            INNER JOIN inscripciones t2
            WHERE
                t1.id < t2.id AND
                t1.estudiante_id = t2.estudiante_id AND
                t1.ciclo_id = t2.ciclo_id
        ");

        Schema::table('inscripciones', function (Blueprint $table) {
            // Evita que un estudiante se inscriba dos veces en el mismo ciclo (año escolar)
            $table->unique(['estudiante_id', 'ciclo_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropUnique(['estudiante_id', 'ciclo_id']);
        });
    }
};
