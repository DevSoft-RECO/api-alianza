<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encabezado extends Model
{
    protected $fillable = ['seccion', 'titulo', 'subtitulo', 'descripcion'];
}
