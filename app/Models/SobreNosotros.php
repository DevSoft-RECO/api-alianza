<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SobreNosotros extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo', // 'colegio' o 'fundador'
        'nombre', // ej. "Juan Perez" o "Colegio XYZ"
        'direccion', // nullable, ej. "San Ildefonso Ixtahuacán"
        'titulo', // ej. "Historia de Fundación" o "Director General"
        'descripcion', // texto largo
        'foto' // nullable
    ];
}
