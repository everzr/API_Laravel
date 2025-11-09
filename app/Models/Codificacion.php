<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Codificacion extends Model
{
    use HasFactory;

    protected $table = 'codificacion';
    protected $primaryKey = 'id_codificacion';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'grupo',
        'titulo',
        'descripcion',
        'modulo',
    ];
}
