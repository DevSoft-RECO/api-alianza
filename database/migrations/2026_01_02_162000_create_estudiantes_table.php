<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            // Carnet vitalicio, indexado para búsquedas rápidas y único
            $table->string('codigo_estudiante')->unique();

            // Nombres y apellidos indexados individualmente para búsquedas
            $table->string('nombres')->index();
            $table->string('apellidos')->index();

            $table->string('nombre_encargado')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono')->nullable(); // Para API WhatsApp
            $table->text('direccion')->nullable(); // Text para mayor capacidad, nullable para no bloquear

            $table->timestamps();

            // Índice compuesto opcional si se busca mucho por nombre completo,
            // pero los individuales cubren la mayoría de casos 'LIKE %...%'
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
