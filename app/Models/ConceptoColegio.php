<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConceptoColegio extends Model {
    protected $table = 'concepto_colegio';
    protected $fillable = ['ciclo_id', 'concepto_id', 'colegio_id', 'precio', 'fecha_limite_absoluta', 'mes_inicio', 'mes_fin', 'activo'];

    public function ciclo() {
        return $this->belongsTo(Ciclo::class);
    }

    public function concepto() {
        return $this->belongsTo(Concepto::class);
    }

    public function colegio() {
        return $this->belongsTo(Colegio::class);
    }

    public function asignaciones() {
        return $this->hasMany(GradoConcepto::class);
    }
}
