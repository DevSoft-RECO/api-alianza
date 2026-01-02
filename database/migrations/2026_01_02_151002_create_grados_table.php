<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->onDelete('cascade');
            $table->string('nombre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grados');
    }
};
