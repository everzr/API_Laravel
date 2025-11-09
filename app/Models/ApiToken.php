<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $table = 'api_tokens';
    protected $fillable = ['id_usuario', 'token', 'expires_at'];
    public $timestamps = false; // evitar columnas created_at/updated_at

    protected $dates = ['expires_at'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
