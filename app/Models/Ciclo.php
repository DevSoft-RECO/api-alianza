<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ciclo extends Model
{
    use HasFactory;

    protected $fillable = ['anio', 'fecha_inicio', 'fecha_fin', 'activo'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];
}
