<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsableLegal extends Model
{
    use HasFactory;

    protected $table = 'responsable_legal';
    protected $primaryKey = 'id_responsable_legal';
    public $timestamps = true;

    protected $fillable = [
        'id_paciente',
        'nombre',
        'apellido',
        'num_identificacion',
        'parentesco',
        'telefono',
        'direccion',
        'correo',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }
}
