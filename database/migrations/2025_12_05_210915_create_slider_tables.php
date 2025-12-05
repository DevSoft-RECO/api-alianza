<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla para el Título y Subtítulo General
        Schema::create('slider_settings', function (Blueprint $table) {
            $table->id();
            // Campos nullable para evitar errores si no se envían
            $table->string('main_title')->nullable()->default('Nuestras Carreras');
            $table->text('subtitle')->nullable()->default('Descubre tu futuro con nosotros');
            $table->timestamps();
        });

        // Insertar registro inicial
        DB::table('slider_settings')->insert([
            'main_title' => 'Nuestras Carreras',
            'subtitle' => 'Explora las oportunidades académicas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tabla para las Imágenes
        Schema::create('slider_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_path')->nullable(); // La ruta de la imagen
            // Enum nullable por si en el futuro hay imágenes sin categoría
            $table->enum('category', ['EPAE', 'CCI', 'LEDNA'])->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slider_images');
        Schema::dropIfExists('slider_settings');
    }
};