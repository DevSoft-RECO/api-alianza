<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Conceptos (El "Qué" se cobra)
        Schema::create('conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "Inscripción", "Colegiatura"
            $table->text('descripcion')->nullable(); // Ej: "Pago correspondiente a la actividad X"
            $table->enum('tipo', ['mensual', 'unico']);
            $table->boolean('tiene_mora')->default(false);
            $table->decimal('mora_monto', 8, 2)->nullable(); // Ej: 15.00
            $table->integer('dias_gracia')->nullable(); // Ej: 5 dias despues de fecha limite
            $table->timestamps();
        });

        // 2. Concepto por Colegio (El "Precio" vigente)
        Schema::create('concepto_colegio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concepto_id')->constrained('conceptos')->onDelete('cascade');
            $table->foreignId('colegio_id')->constrained('colegios')->onDelete('cascade');
            $table->decimal('precio', 8, 2); // Q800.00
            $table->date('fecha_limite_absoluta')->nullable(); // Para pagos únicos (Inscripción)
            $table->integer('mes_inicio')->nullable(); // Para mensualidades (1 = Enero)
            $table->integer('mes_fin')->nullable();    // Para mensualidades (10 = Octubre)
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 3. Asignación a Grado (La "Plantilla" de cobros)
        Schema::create('grado_concepto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grado_id')->constrained('grados')->onDelete('cascade');
            $table->foreignId('concepto_colegio_id')->constrained('concepto_colegio')->onDelete('cascade');
            $table->boolean('obligatorio')->default(true);
            $table->timestamps();
        });

        // 4. Cargos (La Deuda Real congelada)
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->onDelete('cascade');
            $table->foreignId('concepto_id')->constrained('conceptos'); // FK referencia histórica
            $table->string('nombre_concepto'); // Copia del nombre para histórico
            $table->integer('mes')->nullable(); // 1, 2, 3...
            $table->integer('anio'); // 2026
            $table->decimal('monto_base', 8, 2); // Precio congelado al inscribir
            $table->decimal('mora_monto', 8, 2)->nullable(); // Copia de la regla de mora
            $table->date('fecha_limite_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
            $table->timestamps();
        });

        // 5. Pagos (Cabecera Recibo)
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            // $table->foreignId('usuario_id')->constrained('users'); // Opcional, si tienes Auth
            $table->decimal('total', 10, 2);
            $table->enum('forma_pago', ['efectivo', 'tarjeta', 'transferencia']);
            $table->dateTime('fecha_pago');
            $table->timestamps();
        });

        // 6. Detalle del Pago
        Schema::create('pago_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->onDelete('cascade');
            $table->foreignId('cargo_id')->constrained('cargos');
            $table->decimal('monto_pagado', 8, 2);
            $table->boolean('exonerado')->default(false); // Si se le perdonó la mora (boolean legacy)
            $table->decimal('descuento_monto', 8, 2)->default(0); // Monto descontado del base
            $table->string('descuento_motivo')->nullable(); // Ej: "Beca parcial", "Promo pronto pago"
            $table->text('justificacion')->nullable(); // Razón de la exoneración de mora
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pago_detalle');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('grado_concepto');
        Schema::dropIfExists('concepto_colegio');
        Schema::dropIfExists('conceptos');
    }
};
