<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model {
    protected $fillable = ['estudiante_id', 'usuario_id', 'total', 'forma_pago', 'fecha_pago'];

    public function detalles() {
        return $this->hasMany(PagoDetalle::class);
    }

    public function usuario() {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function estudiante() {
        return $this->belongsTo(Estudiante::class);
    }
}
