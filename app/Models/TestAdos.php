<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAdos extends Model
{
    use HasFactory;

    protected $table = 'test_ados_2';
    protected $primaryKey = 'id_ados';
    public $timestamps = true;

    protected $fillable = [
        'id_paciente',
        'fecha',
        'modulo',
        'id_especialista',
        'diagnostico',
        'total_punto',
        'clasificacion',
        'puntuacion_comparativa',
        'estado',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function especialista()
    {
        return $this->belongsTo(Especialista::class, 'id_especialista', 'id_especialista');
    }
}
