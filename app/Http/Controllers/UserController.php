<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    // POST /api/user/login
    public function login(Request $request)
    {
        $correo = $request->input('correo');
        $contrasena = $request->input('contrasena');

        if (!$correo || !$contrasena) {
            return response()->json(['message' => 'Correo y contraseña son requeridos'], 400);
        }

        $user = DB::selectOne("SELECT * FROM usuario WHERE correo = ?", [$correo]);
        if (!$user)
            return response()->json(['message' => 'Correo o contraseña incorrectos'], 401);

        if ((int) ($user->estado ?? 1) === 0) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Por favor, contacta al administrador al correo aplicaciondediagnosticodetea@gmail.com'
            ], 403);
        }

        if (!Hash::check($contrasena, $user->contrasena)) {
            return response()->json(['message' => 'Correo o contraseña incorrectos'], 401);
        }

        $payload = [
            'id_usuario' => (int) $user->id_usuario,
            'correo' => $user->correo,
            'privilegio' => (int) $user->privilegio,
            'iat' => time(),
            'exp' => time() + 2 * 60 * 60, // 2h
        ];
        $token = $this->generateJwt($payload);

        if ((int) ($user->requiere_cambio_contrasena ?? 0) === 1) {
            return response()->json([
                'message' => 'Contraseña genérica detectada, debe cambiarla',
                'requirePasswordChange' => true,
                'token' => $token,
                'user' => [
                    'id_usuario' => (int) $user->id_usuario,
                    'correo' => $user->correo,
                ]
            ]);
        }

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'requirePasswordChange' => false,
            'token' => $token,
            'user' => [
                'id_usuario' => (int) $user->id_usuario,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'direccion' => $user->direccion,
                'telefono' => $user->telefono,
                'correo' => $user->correo,
                'privilegio' => (int) $user->privilegio,
                'imagen' => $user->imagen,
                'estado' => (int) $user->estado,
            ]
        ]);
    }

    // POST /api/user/registrar
    public function registrar(Request $request)
    {
        $data = $request->validate([
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'direccion' => 'required|string',
            'telefono' => 'required|string',
            'correo' => 'required|email',
            'privilegio' => 'required|integer|in:0,1',
            'imagen' => 'nullable|string',
            'fecha_nacimiento' => 'nullable|date',
            'sexo' => 'nullable|string|in:M,F',
            'especialidad' => 'nullable|string',

            // Responsables legales (opcional, cuando privilegio=1)
            'responsables_legales' => 'sometimes|array|min:1',
            'responsables_legales.*.nombre' => 'required_with:responsables_legales|string|max:50',
            'responsables_legales.*.apellido' => 'required_with:responsables_legales|string|max:50',
            'responsables_legales.*.num_identificacion' => 'required_with:responsables_legales|string|max:50',
            'responsables_legales.*.parentesco' => 'required_with:responsables_legales|string|max:50',
            'responsables_legales.*.telefono' => 'nullable|string|max:30',
            'responsables_legales.*.direccion' => 'nullable|string|max:200',
            'responsables_legales.*.correo' => 'nullable|email|max:50',
        ]);

        if ((int) $data['privilegio'] === 1) {
            if (empty($data['fecha_nacimiento']) || empty($data['sexo'])) {
                return response()->json(['message' => 'Fecha de nacimiento y sexo son requeridos para paciente.'], 400);
            }
        }
        if ((int) $data['privilegio'] === 0) {
            if (empty($data['especialidad'])) {
                return response()->json(['message' => 'Especialidad es requerida para especialista.'], 400);
            }
        }

        $contrasenaGenerica = $this->generateStrongPassword(15);
        $hash = Hash::make($contrasenaGenerica);

        try {
            DB::beginTransaction();

            $id_usuario = DB::table('usuario')->insertGetId([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'direccion' => $data['direccion'],
                'telefono' => $data['telefono'],
                'correo' => $data['correo'],
                'contrasena' => $hash,
                'privilegio' => (int) $data['privilegio'],
                'imagen' => $data['imagen'] ?? null,
                'estado' => 1,
                'requiere_cambio_contrasena' => 1,
            ]);

            $id_paciente = null;

            if ((int) $data['privilegio'] === 1) {
                $id_paciente = DB::table('paciente')->insertGetId([
                    'id_usuario' => $id_usuario,
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'sexo' => $data['sexo'],
                ]);

                if (!empty($data['responsables_legales'])) {
                    $rows = [];
                    foreach ($data['responsables_legales'] as $r) {
                        $rows[] = [
                            'id_paciente' => $id_paciente,
                            'nombre' => $r['nombre'],
                            'apellido' => $r['apellido'],
                            'num_identificacion' => $r['num_identificacion'],
                            'parentesco' => $r['parentesco'],
                            'telefono' => $r['telefono'] ?? null,
                            'direccion' => $r['direccion'] ?? null,
                            'correo' => $r['correo'] ?? null,
                        ];
                    }
                    DB::table('responsable_legal')->insert($rows);
                }
            } else {
                DB::table('especialista')->insert([
                    'id_usuario' => $id_usuario,
                    'especialidad' => $data['especialidad'],
                ]);
            }

            DB::commit();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if (($e->errorInfo[1] ?? null) === 1062) {
                return response()->json(['message' => 'El correo ya está registrado'], 409);
            }
            return response()->json(['message' => 'Error en el servidor'], 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error registrando perfil/resp: ' . $e->getMessage());
            return response()->json(['message' => 'Error al registrar perfil'], 500);
        }

        $this->enviarCorreoBienvenida($data['correo'], $contrasenaGenerica, $data['nombres'], $data['apellidos']);

        return response()->json([
            'message' => (int) $data['privilegio'] === 1 ? 'Paciente registrado exitosamente' : 'Especialista registrado exitosamente',
            'userId' => (int) $id_usuario,
            'id_paciente' => $id_paciente
        ], 201);
    }

    // POST /api/user/cambiar-contrasena
    public function cambiarContrasena(Request $request)
    {
        $id_usuario = $request->input('id_usuario');
        $nuevaContra = $request->input('nuevaContra');

        if (!$id_usuario || !$nuevaContra) {
            return response()->json(['message' => 'ID de usuario y nueva contraseña son requeridos'], 400);
        }

        if (!$this->passwordIsSecure($nuevaContra)) {
            return response()->json(['message' => 'La contraseña no cumple con los requisitos de seguridad.'], 400);
        }

        $hash = Hash::make($nuevaContra);
        DB::update("UPDATE usuario SET contrasena = ?, requiere_cambio_contrasena = 0 WHERE id_usuario = ?", [$hash, $id_usuario]);

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    // PUT /api/user/cambiar-password
    public function cambiarPasswordConActual(Request $request)
    {
        $id_usuario = $request->input('id_usuario');
        $currentPassword = $request->input('currentPassword');
        $newPassword = $request->input('newPassword');

        if (!$id_usuario || !$currentPassword || !$newPassword) {
            return response()->json(['message' => 'Todos los campos son requeridos.'], 400);
        }

        if (!$this->passwordIsSecure($newPassword)) {
            return response()->json(['message' => 'La nueva contraseña no cumple con los requisitos de seguridad.'], 400);
        }

        $user = DB::selectOne("SELECT correo, nombres, apellidos, contrasena FROM usuario WHERE id_usuario = ?", [$id_usuario]);
        if (!$user)
            return response()->json(['message' => 'Usuario no encontrado'], 404);

        if (!Hash::check($currentPassword, $user->contrasena)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta.'], 401);
        }

        $hash = Hash::make($newPassword);
        DB::update("UPDATE usuario SET contrasena = ?, requiere_cambio_contrasena = 0 WHERE id_usuario = ?", [$hash, $id_usuario]);

        $this->enviarCorreoCambioContrasena($user->correo, $user->nombres, $user->apellidos);

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    // GET /api/user/pacientes
    public function listarPacientes()
    {
        $rows = DB::select("
            SELECT p.id_paciente, u.nombres, u.apellidos, p.sexo, p.fecha_nacimiento
            FROM paciente p
            JOIN usuario u ON p.id_usuario = u.id_usuario
            ORDER BY u.nombres
        ");
        return response()->json($rows);
    }

    // POST /api/user/recuperar-contrasena
    public function recuperarContrasena(Request $request)
    {
        $correo = $request->input('correo');
        if (!$correo)
            return response()->json(['message' => 'Correo es requerido'], 400);

        $user = DB::selectOne("SELECT id_usuario, nombres, apellidos FROM usuario WHERE correo = ?", [$correo]);
        if (!$user) {
            return response()->json(['message' => 'Si el correo está registrado, recibirás una nueva contraseña.']);
        }

        $nuevaContra = $this->generateStrongPassword(12);
        $hash = Hash::make($nuevaContra);

        DB::update("UPDATE usuario SET contrasena = ?, requiere_cambio_contrasena = 1 WHERE id_usuario = ?", [$hash, $user->id_usuario]);

        $this->enviarCorreoRecuperacion($correo, $nuevaContra, $user->nombres, $user->apellidos);

        return response()->json(['message' => 'Si el correo está registrado, recibirás una nueva contraseña.']);
    }

    // ------------------ Helpers ------------------

    private function enviarCorreoBienvenida(string $destinatario, string $contrasena, string $nombre, string $apellidos): void
    {
        try {
            $texto = "Hola {$nombre} {$apellidos},\n\nTu usuario ha sido creado exitosamente.\n\nUsuario: {$destinatario}\nContraseña: {$contrasena}\n\nPor favor, cambia tu contraseña al iniciar sesión.\n\nSaludos.";
            Mail::raw($texto, function ($m) use ($destinatario) {
                $m->from(config('mail.from.address'), config('mail.from.name'))
                    ->to($destinatario)
                    ->subject('Bienvenido a la Aplicación de Diagnóstico de TEA');
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo bienvenida: ' . $e->getMessage());
        }
    }

    private function enviarCorreoCambioContrasena(string $destinatario, string $nombre, string $apellidos): void
    {
        try {
            $texto = "Hola {$nombre} {$apellidos},\n\nTu contraseña ha sido cambiada exitosamente.\n\nSi no realizaste este cambio, por favor contacta al soporte técnico inmediatamente al correo: aplicaciondediagnosticodetea@gmail.com\n\nSaludos.";
            Mail::raw($texto, function ($m) use ($destinatario) {
                $m->from(config('mail.from.address'), config('mail.from.name'))
                    ->to($destinatario)
                    ->subject('Aviso de cambio de contraseña');
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo cambio contraseña: ' . $e->getMessage());
        }
    }

    private function enviarCorreoRecuperacion(string $destinatario, string $contrasena, string $nombre, string $apellidos): void
    {
        try {
            $texto = "Hola {$nombre} {$apellidos},\n\nSe ha solicitado recuperar tu contraseña.\n\nTu nueva contraseña temporal es: {$contrasena}\n\nPor seguridad, deberás cambiarla al iniciar sesión.\n\nSi no solicitaste este cambio, contacta al soporte técnico: aplicaciondediagnosticodetea@gmail.com\n\nSaludos.";
            Mail::raw($texto, function ($m) use ($destinatario) {
                $m->from(config('mail.from.address'), config('mail.from.name'))
                    ->to($destinatario)
                    ->subject('Recuperación de contraseña');
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo recuperación: ' . $e->getMessage());
        }
    }

    private function generateStrongPassword(int $length = 12): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '0123456789';
        $symbols = '@$!%*?&';
        $all = $upper . $lower . $digits . $symbols;

        $password = '';
        // Garantizar al menos uno de cada tipo
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Mezclar
        return str_shuffle($password);
    }

    private function passwordIsSecure(string $pwd): bool
    {
        return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pwd);
    }

    private function generateJwt(array $payload): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $secret = env('JWT_SECRET', 'secret');

        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
