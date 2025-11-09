<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntuacionAplicada extends Model
{
    use HasFactory;

    protected $table = 'puntuacion_aplicada';
    protected $primaryKey = 'id_puntuacion_aplicada';
    public $timestamps = false;

    protected $fillable = ['id_puntuacion_codificacion', 'id_ados'];

    public function puntuacionCodificacion()
    {
        return $this->belongsTo(PuntuacionCodificacion::class, 'id_puntuacion_codificacion', 'id_puntuacion_codificacion');
    }

    public function ados()
    {
        return $this->belongsTo(TestAdos::class, 'id_ados', 'id_ados');
    }
}
