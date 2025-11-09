<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaAdi extends Model
{
    use HasFactory;

    protected $table = 'respuesta_adi';
    protected $primaryKey = 'id_respuesta';
    public $timestamps = false;

    protected $fillable = ['id_adir', 'id_pregunta', 'codigo', 'observacion'];

    public function adir()
    {
        return $this->belongsTo(TestAdir::class, 'id_adir', 'id_adir');
    }

    public function pregunta()
    {
        return $this->belongsTo(PreguntaAdi::class, 'id_pregunta', 'id_pregunta');
    }
}
