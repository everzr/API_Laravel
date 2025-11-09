<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAdir extends Model
{
    use HasFactory;

    protected $table = 'test_adi_r';
    protected $primaryKey = 'id_adir';
    public $timestamps = true;

    protected $fillable = [
        'id_paciente',
        'id_especialista',
        'fecha',
        'algoritmo',
        'tipo_sujeto',
        'diagnostico',
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
