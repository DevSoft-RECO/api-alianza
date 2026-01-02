<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colegio_id')->constrained('colegios')->onDelete('cascade');
            $table->string('nombre');
            $table->string('descripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles');
    }
};
