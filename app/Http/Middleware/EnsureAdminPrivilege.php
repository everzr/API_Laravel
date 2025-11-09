<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Usuario;

class EnsureAdminPrivilege
{
    /**
     * Handle an incoming request and ensure the authenticated user has admin privilege (3).
     */
    public function handle(Request $request, Closure $next)
    {
        $id = $request->get('auth_usuario_id') ?? $request->input('auth_usuario_id');
        if (!$id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Usuario::find($id);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ((int) $user->privilegio !== 3) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // attach full user to request for controllers to use if needed
        $request->attributes->set('user', $user);
        return $next($request);
    }
}
