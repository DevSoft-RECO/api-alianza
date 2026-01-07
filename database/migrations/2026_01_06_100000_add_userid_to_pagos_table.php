<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Agregamos usuario_id para saber quién cobró (cajero/admin)
            // Nullable por compatibilidad inicial, pero idealmente obligatorio.
            $table->foreignId('usuario_id')
                  ->nullable()
                  ->after('estudiante_id')
                  ->constrained('users');
        });
    }

    public function down()
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropColumn('usuario_id');
        });
    }
};
