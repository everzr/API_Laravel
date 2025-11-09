<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'item';
    protected $primaryKey = 'id_item';
    public $timestamps = false;

    protected $fillable = ['titulo', 'grupo', 'id_codificacion'];

    public function codificacion()
    {
        return $this->belongsTo(Codificacion::class, 'id_codificacion', 'id_codificacion');
    }
}
