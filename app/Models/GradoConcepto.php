<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradoConcepto extends Model {
    protected $table = 'grado_concepto';
    protected $fillable = ['grado_id', 'concepto_colegio_id', 'obligatorio'];

    public function conceptoColegio() {
        return $this->belongsTo(ConceptoColegio::class, 'concepto_colegio_id');
    }
}
