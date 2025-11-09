<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PacienteController extends Controller
{
    // GET /api/paciente/buscar-paciente/{id_usuario}
    public function buscarPacientePorUsuario($id_usuario)
    {
        if (! $id_usuario) {
            return response()->json(['message' => 'El id_usuario es requerido'], 400);
        }

        $paciente = DB::selectOne("SELECT * FROM paciente WHERE id_usuario = ?", [$id_usuario]);
        if (! $paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $payload = [
            'id_paciente' => (int)$paciente->id_paciente,
            'id_usuario'  => (int)$paciente->id_usuario,
            'iat' => time(),
            'exp' => time() + 2 * 60 * 60, // 2h
        ];
        $token = $this->generateJwt($payload);

        return response()->json([
            'message' => 'Paciente encontrado exitosamente',
            'token'   => $token,
            'paciente' => [
                'id_paciente'       => (int)$paciente->id_paciente,
                'id_usuario'        => (int)$paciente->id_usuario,
                'fecha_nacimiento'  => $paciente->fecha_nacimiento,
                'sexo'              => $paciente->sexo,
                'filtro_dsm_5'      => (int)($paciente->filtro_dsm_5 ?? 0),
                'terminos_privacida'=> (int)($paciente->terminos_privacida ?? 0),
            ],
        ]);
    }

    // POST /api/paciente/aceptar-consentimiento
    public function aceptarConsentimiento(Request $request)
    {
        $id_usuario = $request->input('id_usuario');
        $correo     = $request->input('correo');
        $nombres    = $request->input('nombres');
        $apellidos  = $request->input('apellidos');

        if (! $id_usuario || ! $correo) {
            return response()->json(['message' => 'Datos incompletos'], 400);
        }

        DB::update("UPDATE paciente SET terminos_privacida = 1 WHERE id_usuario = ?", [$id_usuario]);

        try {
            $this->enviarCorreoConsentimiento($correo, $nombres, $apellidos);
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de consentimiento: '.$e->getMessage());
        }

        return response()->json(['message' => 'Consentimiento registrado y correo enviado']);
    }

    // POST /api/paciente/guardar-dsm5
    public function guardarDsm5(Request $request)
    {
        $id_usuario = $request->input('id_usuario');
        $resultado  = $request->input('resultado');
        if (! $id_usuario) {
            return response()->json(['message' => 'Falta id_usuario'], 400);
        }

        $valor = ($resultado === "Se recomienda aplicar las pruebas ADOS-2 y ADI-R.") ? 1 : 0;
        DB::update("UPDATE paciente SET filtro_dsm_5 = ? WHERE id_usuario = ?", [$valor, $id_usuario]);

        return response()->json(['message' => 'Resultado guardado', 'filtro_dsm_5' => $valor]);
    }

    // GET /api/paciente/validar-terminos/{id_usuario}
    public function validarTerminos($id_usuario)
    {
        $row = DB::selectOne("SELECT terminos_privacida FROM paciente WHERE id_usuario = ?", [$id_usuario]);
        if (! $row) return response()->json(['permitido' => false]);
        return response()->json(['permitido' => ((int)$row->terminos_privacida === 1)]);
    }

    // PUT /api/paciente/desactivar/{id_usuario}
    public function desactivarCuenta($id_usuario)
    {
        $user = DB::selectOne("SELECT correo, nombres, apellidos FROM usuario WHERE id_usuario = ?", [$id_usuario]);
        if (! $user) {
            return response()->json(['message' => 'Error al obtener datos del usuario'], 500);
        }

        DB::update("UPDATE usuario SET estado = 0 WHERE id_usuario = ?", [$id_usuario]);

        try {
            $this->enviarCorreoDesactivacion($user->correo, $user->nombres, $user->apellidos);
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de desactivación: '.$e->getMessage());
        }

        return response()->json(['message' => 'Cuenta desactivada exitosamente']);
    }

    // GET /api/paciente/resultados/{id_paciente}?tipo=adir|ados|todos&fecha_inicio=YYYY-MM-DD&fecha_fin=YYYY-MM-DD
    public function listarResultadosPaciente(Request $request, $id_paciente)
    {
        if (! $id_paciente) {
            return response()->json(['message' => 'El id_paciente es requerido'], 400);
        }

        $tipo = $request->query('tipo');
        $fi   = $request->query('fecha_inicio');
        $ff   = $request->query('fecha_fin');

        $filtroAdi = '';
        $filtroAdos = '';
        $paramsAdi = [$id_paciente];
        $paramsAdos = [$id_paciente];

        if ($fi) {
            $filtroAdi  .= " AND fecha >= ? ";
            $filtroAdos .= " AND fecha >= ? ";
            $paramsAdi[] = $fi.' 00:00:00';
            $paramsAdos[] = $fi.' 00:00:00';
        }
        if ($ff) {
            $filtroAdi  .= " AND fecha <= ? ";
            $filtroAdos .= " AND fecha <= ? ";
            $paramsAdi[] = $ff.' 23:59:59';
            $paramsAdos[] = $ff.' 23:59:59';
        }

        $tests = [];

        if (! $tipo || $tipo === 'todos' || $tipo === 'adir') {
            $sqlAdi = "
                SELECT id_adir as id, fecha, diagnostico, 'ADI-R' as tipo
                FROM test_adi_r
                WHERE id_paciente = ? AND estado = 1
                $filtroAdi
            ";
            $tests = array_merge($tests, DB::select($sqlAdi, $paramsAdi));
        }

        if (! $tipo || $tipo === 'todos' || $tipo === 'ados') {
            $sqlAdos = "
                SELECT id_ados as id, fecha, diagnostico, clasificacion, modulo, 'ADOS-2' as tipo
                FROM test_ados_2
                WHERE id_paciente = ? AND estado = 0
                $filtroAdos
            ";
            $tests = array_merge($tests, DB::select($sqlAdos, $paramsAdos));
        }

        usort($tests, function ($a, $b) {
            return strtotime($b->fecha) <=> strtotime($a->fecha);
        });

        return response()->json($tests);
    }

    // ------------------ Helpers ------------------

    private function enviarCorreoConsentimiento(string $destinatario, ?string $nombre, ?string $apellidos): void
    {
        $cons = "Consentimiento informado para el uso de la aplicación de evaluación del Trastorno del Espectro Autista (TEA)

Estimado/a ".trim(($nombre ?? '').' '.($apellidos ?? '')).",

Usted ha aceptado los términos y condiciones para el uso de la aplicación TEA.
Fecha de aceptación: ".now()->format('d/m/Y H:i')."

Gracias por su confianza.
";

        $pdfUrl = 'https://ajvlsndqsmfllxnuahsq.supabase.co/storage/v1/object/public/tea_docs/Consentimiento_TEA.pdf';
        $pdfData = null;
        try {
            $pdfData = @file_get_contents($pdfUrl);
        } catch (\Throwable $e) {
            $pdfData = null;
        }

        Mail::raw($cons, function ($m) use ($destinatario, $pdfData) {
            $m->from('aplicaciondediagnosticodetea@gmail.com', 'TEA Diagnóstico')
              ->to($destinatario)
              ->subject('Consentimiento informado aceptado - TEA Diagnóstico');

            if ($pdfData) {
                $m->attachData($pdfData, 'Consentimiento_TEA.pdf', ['mime' => 'application/pdf']);
            }
        });
    }

    private function enviarCorreoDesactivacion(string $destinatario, string $nombre, string $apellidos): void
    {
        $mensaje = "
Hola {$nombre} {$apellidos},

Te informamos que tu cuenta en la aplicación de diagnóstico TEA ha sido desactivada exitosamente.

Si esto fue un error o necesitas reactivar tu cuenta, por favor contacta al administrador al correo aplicaciondediagnosticodetea@gmail.com.

Saludos,
Equipo TEA Diagnóstico
";
        Mail::raw($mensaje, function ($m) use ($destinatario) {
            $m->from('aplicaciondediagnosticodetea@gmail.com', 'TEA Diagnóstico')
              ->to($destinatario)
              ->subject('Cuenta desactivada - TEA Diagnóstico');
        });
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
