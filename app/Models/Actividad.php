<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;

    protected $table = 'actividad';
    protected $primaryKey = 'id_actividad';
    public $timestamps = false;

    protected $fillable = [
        'nombre_actividad',
        'modulo',
        'objetivo',
        'materiales',
        'intrucciones',
        'aspectos_observar',
        'info_complementaria',
    ];
}
