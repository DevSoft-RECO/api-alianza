<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoDetalle extends Model {
    protected $table = 'pago_detalle';
    protected $fillable = ['pago_id', 'cargo_id', 'monto_pagado', 'exonerado', 'justificacion', 'descuento_monto', 'descuento_motivo'];

    public function cargo() {
        return $this->belongsTo(Cargo::class);
    }
}
