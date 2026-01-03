<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_estudiante',
        'nombres',
        'apellidos',
        'nombre_encargado',
        'fecha_nacimiento',
        'telefono',
        'direccion'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date:Y-m-d',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }
}
