<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemAlgoritmo extends Model
{
    use HasFactory;

    protected $table = 'item_algoritmo';
    protected $primaryKey = 'id_item_algoritmo';
    public $timestamps = false;

    protected $fillable = ['id_item', 'id_algoritmo'];

    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item', 'id_item');
    }

    public function algoritmo()
    {
        return $this->belongsTo(Algoritmo::class, 'id_algoritmo', 'id_algoritmo');
    }
}
