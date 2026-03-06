<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('colegios', function (Blueprint $table) {
            $table->string('label')->nullable()->after('direccion');
            $table->text('descripcion_web')->nullable()->after('label');
            $table->string('theme')->nullable()->after('descripcion_web');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colegios', function (Blueprint $table) {
            $table->dropColumn(['label', 'descripcion_web', 'theme']);
        });
    }
};
