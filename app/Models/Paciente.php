<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'paciente';
    protected $primaryKey = 'id_paciente';
    public $timestamps = false; // La tabla no tiene created_at/updated_at

    protected $fillable = [
        'id_usuario',
        'fecha_nacimiento',
        'sexo',
        'filtro_dsm_5',
        'terminos_privacida',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
