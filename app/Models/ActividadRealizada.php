<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadRealizada extends Model
{
    use HasFactory;

    protected $table = 'actividad_realizada';
    protected $primaryKey = 'id_actividad_realizada';
    public $timestamps = false;

    protected $fillable = [
        'id_ados',
        'id_actividad',
        'observacion',
    ];
}
