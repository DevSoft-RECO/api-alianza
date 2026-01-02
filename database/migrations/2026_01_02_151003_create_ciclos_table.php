<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
