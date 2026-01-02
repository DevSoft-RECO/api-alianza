<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grado extends Model
{
    use HasFactory;

    protected $fillable = ['nivel_id', 'nombre'];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}
