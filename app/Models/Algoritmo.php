<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Algoritmo extends Model
{
    use HasFactory;

    protected $table = 'algoritmo';
    protected $primaryKey = 'id_algoritmo';
    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'descripcion',
        'modulo',
    ];
}
