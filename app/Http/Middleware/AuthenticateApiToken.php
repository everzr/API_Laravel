<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ApiToken;
use Carbon\Carbon;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     * Accepts either a JWT (HS256) signed with env('JWT_SECRET')
     * or an opaque API token stored hashed (sha256) in api_tokens table.
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $rawToken = substr($header, 7);

        // 1) Try to treat as JWT first (three parts with dots)
        if (substr_count($rawToken, '.') === 2) {
            $payload = $this->validateJwt($rawToken, (string) env('JWT_SECRET'));
            if ($payload && isset($payload['id_usuario'])) {
                $request->merge(['auth_usuario_id' => $payload['id_usuario']]);
                return $next($request);
            }
            // If JWT provided but invalid
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2) Fallback to opaque token persisted in DB
        $hashed = hash('sha256', $rawToken);
        $record = ApiToken::where('token', $hashed)->first();
        if (!$record) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($record->expires_at && Carbon::now()->greaterThan($record->expires_at)) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        $request->merge(['auth_usuario_id' => $record->id_usuario]);
        return $next($request);
    }

    private function base64url_decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }

    private function validateJwt(string $token, string $secret): ?array
    {
        try {
            [$h, $p, $s] = explode('.', $token);
            $header = json_decode($this->base64url_decode($h), true);
            $payload = json_decode($this->base64url_decode($p), true);
            $sig = $this->base64url_decode($s);

            if (!is_array($header) || !is_array($payload))
                return null;
            if (($header['alg'] ?? '') !== 'HS256')
                return null;

            $expected = hash_hmac('sha256', $h . '.' . $p, $secret, true);
            if (!hash_equals($expected, $sig))
                return null;

            // exp validation if present
            if (isset($payload['exp']) && time() >= (int) $payload['exp'])
                return null;

            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
