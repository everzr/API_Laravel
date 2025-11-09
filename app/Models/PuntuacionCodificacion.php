<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntuacionCodificacion extends Model
{
    use HasFactory;

    protected $table = 'puntuacion_codificacion';
    protected $primaryKey = 'id_puntuacion_codificacion';
    public $timestamps = false;

    protected $fillable = ['puntaje', 'descripcion', 'id_codificacion'];

    public function codificacion()
    {
        return $this->belongsTo(Codificacion::class, 'id_codificacion', 'id_codificacion');
    }
}
