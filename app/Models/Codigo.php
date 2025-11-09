<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Codigo extends Model
{
    use HasFactory;

    protected $table = 'codigo';
    protected $primaryKey = 'id_codigo';
    public $timestamps = false;

    protected $fillable = ['codigo', 'id_pregunta'];

    public function pregunta()
    {
        return $this->belongsTo(PreguntaAdi::class, 'id_pregunta', 'id_pregunta');
    }
}
