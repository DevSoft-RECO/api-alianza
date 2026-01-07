<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';

    protected $fillable = [
        'estudiante_id',
        'ciclo_id',
        'colegio_id',
        'grado_id',
        'seccion',
        'estado'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }

    public function colegio()
    {
        return $this->belongsTo(Colegio::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    // Relación para Módulo 3 V3 (Final)
    public function cargos()
    {
        return $this->hasMany(\App\Models\Cargo::class);
    }
}
