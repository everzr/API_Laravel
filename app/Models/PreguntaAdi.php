<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreguntaAdi extends Model
{
    use HasFactory;

    protected $table = 'pregunta_adi';
    protected $primaryKey = 'id_pregunta';
    public $timestamps = false;

    protected $fillable = ['pregunta', 'id_area'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area', 'id_area');
    }
}
