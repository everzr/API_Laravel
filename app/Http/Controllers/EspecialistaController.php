<?php
// filepath: c:\Users\EverZr\Desktop\CICLOVIII\Desarrollo Web con Software [EspecialistaController.php](http://_vscodecontentref_/0)
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Especialista; // <- usar modelo en vez de DB

class EspecialistaController extends Controller
{
    // Buscar especialista por id_usuario -> devuelve { especialista: { ... } }
    public function buscarEspe($id_usuario)
    {
        $especialista = Especialista::where('id_usuario', $id_usuario)->first();

        if (! $especialista) {
            return response()->json(['especialista' => null], 200);
        }

        return response()->json(['especialista' => $especialista], 200);
    }
}