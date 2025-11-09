<?php
// filepath: c:\Users\EverZr\Desktop\CICLOVIII\Desarrollo Web con Software [EspecialistaController.php](http://_vscodecontentref_/0)
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Especialista;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EspecialistaController extends Controller
{
    // GET /api/especialistas/buscar-espe/{id_usuario}
    public function buscarEspe($id_usuario)
    {
        if (empty($id_usuario)) {
            return response()->json(['message' => 'El id_usuario es requerido'], 400);
        }

        $especialista = Especialista::select('id_especialista', 'id_usuario', 'especialidad', 'terminos_privacida')
            ->where('id_usuario', $id_usuario)
            ->first();

        if (! $especialista) {
            return response()->json(['message' => 'Especialista no encontrado'], 404);
        }

        return response()->json([
            'message' => 'Especialista encontrado exitosamente',
            // No token (según solicitud)
            'especialista' => $especialista,
        ], 200);
    }

    // POST /api/especialistas/aceptar-consentimiento
    public function aceptarConsentimiento(Request $request)
    {
        $id_usuario = $request->input('id_usuario');
        $correo     = $request->input('correo');
        $nombres    = $request->input('nombres', '');
        $apellidos  = $request->input('apellidos', '');

        if (! $id_usuario || ! $correo) {
            return response()->json(['message' => 'Datos incompletos'], 400);
        }

        $actualizados = Especialista::where('id_usuario', $id_usuario)
            ->update(['terminos_privacida' => 1]);

        if ($actualizados === 0) {
            return response()->json(['message' => 'Especialista no encontrado'], 404);
        }

        $this->enviarCorreoConsentimientoEspecialista($correo, $nombres, $apellidos);

        return response()->json(['message' => 'Consentimiento registrado y correo enviado'], 200);
    }

    // GET /api/especialistas/reportes/pacientes-con-tests
    public function pacientesConTests()
    {
        try {
            $sql = "
                SELECT
                    p.id_paciente, u.nombres, u.apellidos, u.imagen, p.fecha_nacimiento, p.sexo,
                    (
                        SELECT MAX(fecha) FROM test_adi_r WHERE id_paciente = p.id_paciente AND estado = 1
                    ) AS fecha_ultimo_adir,
                    (
                        SELECT MAX(fecha) FROM test_ados_2 WHERE id_paciente = p.id_paciente AND estado = 0
                    ) AS fecha_ultimo_ados
                FROM paciente p
                JOIN usuario u ON p.id_usuario = u.id_usuario
                WHERE u.estado = 1
                ORDER BY
                    GREATEST(
                        IFNULL((SELECT MAX(fecha) FROM test_adi_r WHERE id_paciente = p.id_paciente AND estado = 1), '1970-01-01'),
                        IFNULL((SELECT MAX(fecha) FROM test_ados_2 WHERE id_paciente = p.id_paciente AND estado = 0), '1970-01-01')
                    ) DESC
            ";

            $pacientes = DB::select($sql);

            $resultado = array_map(function ($paciente) {
                $pacArr = (array) $paciente;

                $tests_adir = DB::select(
                    "SELECT id_adir, fecha, algoritmo, diagnostico, estado
                     FROM test_adi_r
                     WHERE id_paciente = ? AND estado = 1
                     ORDER BY fecha DESC",
                    [$paciente->id_paciente]
                );

                $tests_ados = DB::select(
                    "SELECT id_ados, fecha, modulo, diagnostico, clasificacion, total_punto, puntuacion_comparativa, estado
                     FROM test_ados_2
                     WHERE id_paciente = ? AND estado = 0
                     ORDER BY fecha DESC",
                    [$paciente->id_paciente]
                );

                $pacArr['tests_adir'] = $tests_adir;
                $pacArr['tests_ados'] = $tests_ados;

                return $pacArr;
            }, $pacientes);

            return response()->json($resultado, 200);
        } catch (\Throwable $e) {
            Log::error('Error en pacientesConTests: '.$e->getMessage());
            return response()->json(['message' => 'Error en el servidor'], 500);
        }
    }

    private function enviarCorreoConsentimientoEspecialista(string $destinatario, string $nombre, string $apellidos): void
    {
        $consentimiento = "
Consentimiento y declaración de uso profesional del sistema de evaluación del Trastorno del Espectro Autista (TEA)

Estimado/a {$nombre} {$apellidos},

Usted ha aceptado los términos y condiciones profesionales para el uso de la plataforma TEA.
Fecha de aceptación: ".now()->toDateTimeString()."

Gracias por su compromiso profesional.
";

        try {
            Mail::raw($consentimiento, function ($message) use ($destinatario) {
                $message->to($destinatario)
                    ->subject('Consentimiento profesional aceptado - TEA Diagnóstico');

                // Adjuntar PDF desde URL pública
                $pdfUrl = 'https://ajvlsndqsmfllxnuahsq.supabase.co/storage/v1/object/public/tea_docs/Consentimiento_Profesional_TEA.pdf';
                try {
                    $pdf = @file_get_contents($pdfUrl);
                    if ($pdf !== false) {
                        $message->attachData($pdf, 'Consentimiento_Profesional_TEA.pdf', ['mime' => 'application/pdf']);
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo adjuntar el PDF: '.$e->getMessage());
                }
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de consentimiento profesional: '.$e->getMessage());
        }
    }
}
