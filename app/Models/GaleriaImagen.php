<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaleriaImagen extends Model
{
    protected $table = 'galeria_imagenes';
    protected $fillable = ['imagen', 'descripcion', 'orden'];
}
