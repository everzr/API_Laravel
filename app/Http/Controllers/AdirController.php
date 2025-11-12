<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdirController extends Controller
{
    // GET /api/adir/listar/{id_paciente}
    public function listarTestsPorPaciente($id_paciente)
    {
        try {
            $results = DB::select("
                SELECT t.id_adir, t.fecha, t.diagnostico, t.estado
                FROM test_adi_r t
                WHERE t.id_paciente = ?
                ORDER BY t.fecha DESC
            ", [$id_paciente]);

            return response()->json($results);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener tests.'], 500);
        }
    }

    // GET /api/adir/listar-con-diagnostico/{id_paciente}
    public function listarTestsConDiagnosticoPorPaciente($id_paciente)
    {
        try {
            $results = DB::select("
                SELECT t.id_adir, t.fecha, t.diagnostico
                FROM test_adi_r t
                WHERE t.id_paciente = ? AND t.diagnostico IS NOT NULL
                ORDER BY t.fecha DESC
            ", [$id_paciente]);

            return response()->json($results);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener tests.'], 500);
        }
    }

    // GET /api/adir/resumen/{id_adir}
    public function obtenerResumenEvaluacion($id_adir)
    {
        $test = DB::selectOne("
            SELECT
                t.id_adir,
                t.fecha AS fecha_entrevista,
                t.diagnostico,
                t.algoritmo,
                t.tipo_sujeto,
                t.estado,
                t.id_especialista,
                p.id_paciente,
                u.nombres,
                u.apellidos,
                p.sexo,
                p.fecha_nacimiento,
                ue.nombres AS especialista_nombre,
                ue.apellidos AS especialista_apellidos
            FROM test_adi_r t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            JOIN usuario u ON p.id_usuario = u.id_usuario
            LEFT JOIN especialista e ON t.id_especialista = e.id_especialista
            LEFT JOIN usuario ue ON e.id_usuario = ue.id_usuario
            WHERE t.id_adir = ?
        ", [$id_adir]);

        if (!$test) {
            return response()->json(['message' => 'Test no encontrado.'], 404);
        }

        $respuestas = DB::select("
            SELECT r.id_pregunta, a.area, q.pregunta, r.codigo, r.observacion
            FROM respuesta_adi r
            JOIN pregunta_adi q ON r.id_pregunta = q.id_pregunta
            JOIN area a ON q.id_area = a.id_area
            WHERE r.id_adir = ?
        ", [$id_adir]);

        $testArr = (array) $test;
        $testArr['especialista'] = $test->especialista_nombre
            ? trim(($test->especialista_nombre ?? '') . ' ' . ($test->especialista_apellidos ?? ''))
            : "";
        unset($testArr['especialista_nombre'], $testArr['especialista_apellidos']);

        return response()->json([
            'test' => $testArr,
            'respuestas' => $respuestas,
        ]);
    }

    // PUT /api/adir/diagnostico/{id_adir}
    public function guardarDiagnostico(Request $request, $id_adir)
    {
        $diagnostico = $request->input('diagnostico');
        $id_especialista = $request->input('id_especialista');

        try {
            DB::update(
                "UPDATE test_adi_r SET diagnostico = ?, id_especialista = ? WHERE id_adir = ?",
                [$diagnostico, $id_especialista, $id_adir]
            );

            $info = DB::selectOne("
                SELECT u.correo, u.nombres, u.apellidos
                FROM test_adi_r t
                JOIN paciente p ON t.id_paciente = p.id_paciente
                JOIN usuario u ON p.id_usuario = u.id_usuario
                WHERE t.id_adir = ?
            ", [$id_adir]);

            if ($info) {
                $this->enviarCorreoDiagnostico($info->correo, $info->nombres, $info->apellidos, $diagnostico);
            }

            return response()->json(['message' => 'Diagnóstico guardado correctamente.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al guardar el diagnóstico.'], 500);
        }
    }

    // GET /api/adir/resumen-ultimo/{id_paciente}
    public function resumenUltimoTestPorPaciente($id_paciente)
    {
        $test = DB::selectOne("
            SELECT t.id_adir, t.fecha, t.diagnostico, p.id_paciente, u.nombres, u.apellidos, u.sexo, u.fecha_nacimiento
            FROM test_adi_r t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            JOIN usuario u ON p.id_usuario = u.id_usuario
            WHERE t.id_paciente = ?
            ORDER BY t.fecha DESC
            LIMIT 1
        ", [$id_paciente]);

        if (!$test) {
            return response()->json(['message' => 'El paciente no tiene tests ADIR.'], 404);
        }

        $respuestas = DB::select("
            SELECT r.id_pregunta, q.pregunta, r.codigo, r.observacion
            FROM respuesta_adi r
            JOIN pregunta_adi q ON r.id_pregunta = q.id_pregunta
            JOIN area a ON q.id_area = a.id_area
            WHERE r.id_adir = ?
        ", [$test->id_adir]);

        return response()->json([
            'test' => $test,
            'respuestas' => $respuestas,
        ]);
    }

    // GET /api/adir/pdf/{id_adir}
    public function generarPdfAdir($id_adir)
    {
        // Para generar PDF en Laravel, instala dompdf:
        // composer require barryvdh/laravel-dompdf
        // Si no está instalado, devolvemos 501 como placeholder.
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response()->json([
                'message' => 'PDF no disponible. Instala barryvdh/laravel-dompdf para habilitarlo.'
            ], 501);
        }

        // Carga de datos (similar a Node)
        $adir = DB::selectOne("SELECT * FROM test_adi_r WHERE id_adir = ?", [$id_adir]);
        if (!$adir)
            return response()->json(['message' => 'No existe el test.'], 404);

        $paciente = DB::selectOne("SELECT * FROM paciente WHERE id_paciente = ?", [$adir->id_paciente]);
        if (!$paciente)
            return response()->json(['message' => 'Paciente no encontrado.'], 404);

        $usuarioPaciente = DB::selectOne("SELECT * FROM usuario WHERE id_usuario = ?", [$paciente->id_usuario]);
        if (!$usuarioPaciente)
            return response()->json(['message' => 'Usuario paciente no encontrado.'], 404);

        $especialista = DB::selectOne("SELECT * FROM especialista WHERE id_especialista = ?", [$adir->id_especialista]);
        $usuarioEspecialista = $especialista
            ? DB::selectOne("SELECT * FROM usuario WHERE id_usuario = ?", [$especialista->id_usuario])
            : null;

        $respuestas = DB::select("
            SELECT r.*, p.pregunta
            FROM respuesta_adi r
            JOIN pregunta_adi p ON p.id_pregunta = r.id_pregunta
            WHERE r.id_adir = ?
        ", [$id_adir]);

        $html = view('pdf.adir', [
            'adir' => $adir,
            'paciente' => $paciente,
            'usuarioPaciente' => $usuarioPaciente,
            'usuarioEspecialista' => $usuarioEspecialista,
            'respuestas' => $respuestas,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        return $pdf->download("adir_{$id_adir}.pdf");
    }

    // GET /api/adir/preguntas
    public function obtenerPreguntasAdir()
    {
        try {
            $results = DB::select("SELECT id_pregunta, pregunta FROM pregunta_adi ORDER BY id_pregunta");
            return response()->json($results);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener preguntas.'], 500);
        }
    }

    // POST /api/adir/crear-test
    public function crearTestAdir(Request $request)
    {
        $id_paciente = $request->input('id_paciente');
        $id_especialista = $request->input('id_especialista');
        $algoritmo = $request->input('algoritmo', 0);
        $tipo_sujeto = $request->input('tipo_sujeto', '');
        $edad_mental_confirmada = $request->boolean('edad_mental_confirmada');

        if (!$id_paciente || !$id_especialista || !$edad_mental_confirmada) {
            return response()->json(['message' => 'Faltan datos obligatorios.'], 400);
        }
        if (!$edad_mental_confirmada) {
            return response()->json(['message' => 'Debe confirmar que el paciente tiene al menos 2 años de edad mental.'], 400);
        }

        $pac = DB::selectOne("SELECT terminos_privacida, filtro_dsm_5 FROM paciente WHERE id_paciente = ?", [$id_paciente]);
        if (!$pac)
            return response()->json(['message' => 'Paciente no encontrado.'], 404);
        if (($pac->terminos_privacida ?? 0) != 1 || ($pac->filtro_dsm_5 ?? 0) != 1) {
            return response()->json(['message' => 'El paciente debe aceptar los términos y cumplir el filtro DSM-5.'], 403);
        }

        $esp = DB::selectOne("SELECT * FROM especialista WHERE id_especialista = ?", [$id_especialista]);
        if (!$esp)
            return response()->json(['message' => 'Especialista no válido.'], 403);

        DB::insert("
            INSERT INTO test_adi_r (id_paciente, id_especialista, fecha, algoritmo, tipo_sujeto, estado)
            VALUES (?, ?, NOW(), ?, ?, 0)
        ", [$id_paciente, $id_especialista, $algoritmo, $tipo_sujeto]);

        $id = DB::getPdo()->lastInsertId();
        return response()->json(['id_adir' => (int) $id]);
    }

    // GET /api/adir/preguntas-con-respuestas/{id_adir}
    public function obtenerPreguntasConRespuestas($id_adir)
    {
        $rows = DB::select("
            SELECT p.id_pregunta, p.pregunta, p.id_area, a.area,
                   r.codigo as codigo_respuesta, r.observacion
            FROM pregunta_adi p
            JOIN area a ON p.id_area = a.id_area
            LEFT JOIN respuesta_adi r ON r.id_pregunta = p.id_pregunta AND r.id_adir = ?
            ORDER BY a.id_area, p.id_pregunta
        ", [$id_adir]);

        $preguntas = [];
        $respuestas = [];
        foreach ($rows as $row) {
            $preguntas[] = $row;
            if ($row->codigo_respuesta !== null) {
                $respuestas[$row->id_pregunta] = [
                    'codigo' => $row->codigo_respuesta,
                    'observacion' => $row->observacion,
                ];
            }
        }

        $pac = DB::selectOne("
            SELECT p.id_paciente, u.nombres, u.apellidos, p.sexo, p.fecha_nacimiento
            FROM test_adi_r t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            JOIN usuario u ON p.id_usuario = u.id_usuario
            WHERE t.id_adir = ?
        ", [$id_adir]);

        if (!$pac) {
            return response()->json(['preguntas' => $preguntas, 'respuestas' => $respuestas]);
        }

        return response()->json([
            'preguntas' => $preguntas,
            'respuestas' => $respuestas,
            'paciente' => $pac,
        ]);
    }

    // GET /api/adir/codigos-por-pregunta
    public function obtenerCodigosPorPregunta()
    {
        $results = DB::select("SELECT c.id_codigo, c.codigo, c.id_pregunta FROM codigo c");
        $map = [];
        foreach ($results as $r) {
            if (!isset($map[$r->id_pregunta]))
                $map[$r->id_pregunta] = [];
            $map[$r->id_pregunta][] = ['id_codigo' => $r->id_codigo, 'codigo' => $r->codigo];
        }
        return response()->json($map);
    }

    // POST /api/adir/guardar-respuesta
    public function guardarRespuestaAdir(Request $request)
    {
        try {
            // Leer body y convertir de forma segura
            $idAdirRaw = $request->input('id_adir');
            $idPreguntaRaw = $request->input('id_pregunta');
            // Acepta 'codigo' o 'calificacion' desde el front
            $codigoRaw = $request->input('codigo', $request->input('calificacion'));
            $observacion = (string) ($request->input('observacion') ?? '');

            $id_adir = $this->toIntOrNull($idAdirRaw);
            $id_pregunta = $this->toIntOrNull($idPreguntaRaw);
            $codigo = $this->toIntOrNull($codigoRaw);

            // Si viene vacío/null, usa 0 por defecto (igual que en Spring)
            if ($codigo === null) {
                $codigo = 0;
            }

            if ($id_adir === null || $id_pregunta === null) {
                return response()->json(['message' => 'Datos incompletos o inválidos'], 400);
            }

            // Existe respuesta previa
            $exists = DB::selectOne(
                "SELECT 1 AS e FROM respuesta_adi WHERE id_adir = ? AND id_pregunta = ? LIMIT 1",
                [$id_adir, $id_pregunta]
            );

            if ($exists) {
                DB::update(
                    "UPDATE respuesta_adi SET codigo = ?, observacion = ? WHERE id_adir = ? AND id_pregunta = ?",
                    [$codigo, $observacion, $id_adir, $id_pregunta]
                );
                return response()->json(['message' => 'Respuesta actualizada.']);
            } else {
                DB::insert(
                    "INSERT INTO respuesta_adi (id_adir, id_pregunta, codigo, observacion) VALUES (?, ?, ?, ?)",
                    [$id_adir, $id_pregunta, $codigo, $observacion]
                );
                return response()->json(['message' => 'Respuesta guardada.']);
            }
        } catch (\Throwable $e) {
            Log::error('ADIR guardarRespuesta error: ' . $e->getMessage());
            return response()->json(['message' => 'Error al guardar respuesta: ' . $e->getMessage()], 500);
        }
    }

    // Conversión segura a entero (null si no es numérico)
    private function toIntOrNull($value): ?int
    {
        if (is_int($value))
            return $value;
        if (is_float($value))
            return (int) $value;
        if (is_string($value)) {
            $s = trim($value);
            if ($s !== '' && preg_match('/^-?\d+$/', $s))
                return (int) $s;
        }
        if (is_numeric($value))
            return (int) $value;
        return null;
    }

    // GET /api/adir/id-paciente/{id_adir}
    public function obtenerIdPacientePorAdir($id_adir)
    {
        $row = DB::selectOne("SELECT id_paciente FROM test_adi_r WHERE id_adir = ?", [$id_adir]);
        if (!$row)
            return response()->json(['message' => 'Test no encontrado.'], 404);
        return response()->json(['id_paciente' => $row->id_paciente]);
    }

    // PUT /api/adir/determinar-tipo-sujeto/{id_adir}
    public function determinarYActualizarTipoSujeto($id_adir)
    {
        $r = DB::selectOne("
            SELECT codigo FROM respuesta_adi WHERE id_adir = ? AND id_pregunta = 30 LIMIT 1
        ", [$id_adir]);

        if (!$r)
            return response()->json(['message' => 'No existe respuesta para la pregunta 30.'], 404);

        $codigo = (int) $r->codigo;
        $tipo = 'no-verbal';
        if ($codigo === 0)
            $tipo = 'verbal';
        else if ($codigo === 1 || $codigo === 2)
            $tipo = 'no-verbal';

        DB::update("UPDATE test_adi_r SET tipo_sujeto = ? WHERE id_adir = ?", [$tipo, $id_adir]);

        return response()->json(['tipo_sujeto' => $tipo]);
    }

    // GET /api/adir/fecha-entrevista/{id_adir}
    public function obtenerFechaEntrevistaPorAdir($id_adir)
    {
        $row = DB::selectOne("SELECT fecha FROM test_adi_r WHERE id_adir = ?", [$id_adir]);
        if (!$row)
            return response()->json(['message' => 'Test no encontrado.'], 404);
        return response()->json(['fecha_entrevista' => $row->fecha]);
    }

    // PUT /api/adir/guardar-diagnostico-final/{id_adir}
    public function guardarDiagnosticoFinal(Request $request, $id_adir)
    {
        $algoritmo = $request->input('algoritmo');
        $diagnostico = $request->input('diagnostico');
        $estado = $request->input('estado');

        try {
            DB::update(
                "UPDATE test_adi_r SET algoritmo = ?, diagnostico = ?, estado = ? WHERE id_adir = ?",
                [$algoritmo, $diagnostico, $estado, $id_adir]
            );

            $info = DB::selectOne("
                SELECT u.correo, u.nombres, u.apellidos
                FROM test_adi_r t
                JOIN paciente p ON t.id_paciente = p.id_paciente
                JOIN usuario u ON p.id_usuario = u.id_usuario
                WHERE t.id_adir = ?
            ", [$id_adir]);

            if ($info) {
                $this->enviarCorreoDiagnosticoFinalADI($info->correo, $info->nombres, $info->apellidos);
            }

            return response()->json(['message' => 'Diagnóstico final guardado correctamente.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al guardar el diagnóstico final.'], 500);
        }
    }

    // GET /api/adir/resumen-paciente/{id_adir}
    public function obtenerResumenPacienteAdir($id_adir)
    {
        $datos = DB::selectOne("
            SELECT
                t.id_adir, t.fecha AS fecha_entrevista, t.diagnostico, t.algoritmo, t.tipo_sujeto, t.estado,
                u.nombres, u.apellidos,
                ue.nombres AS especialista_nombre, ue.apellidos AS especialista_apellidos
            FROM test_adi_r t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            JOIN usuario u ON p.id_usuario = u.id_usuario
            LEFT JOIN especialista e ON t.id_especialista = e.id_especialista
            LEFT JOIN usuario ue ON e.id_usuario = ue.id_usuario
            WHERE t.id_adir = ?
        ", [$id_adir]);

        if (!$datos)
            return response()->json(['message' => 'No se encontró el test.'], 404);

        $respuestas = DB::select("
            SELECT
                a.area,
                q.id_pregunta,
                q.pregunta,
                r.codigo,
                r.observacion
            FROM respuesta_adi r
            JOIN pregunta_adi q ON r.id_pregunta = q.id_pregunta
            JOIN area a ON q.id_area = a.id_area
            WHERE r.id_adir = ?
            ORDER BY a.id_area, q.id_pregunta
        ", [$id_adir]);

        return response()->json([
            'nombres' => $datos->nombres,
            'apellidos' => $datos->apellidos,
            'fecha' => $datos->fecha_entrevista,
            'especialista' => $datos->especialista_nombre
                ? trim(($datos->especialista_nombre ?? '') . ' ' . ($datos->especialista_apellidos ?? '')) : '',
            'diagnostico' => $datos->diagnostico ?? 'Aquí aparecerá el resumen de tu diagnóstico.',
            'algoritmo' => $datos->algoritmo ?? 'No disponible',
            'tipo_sujeto' => $datos->tipo_sujeto ?? 'No disponible',
            'respuestas' => $respuestas,
        ]);
    }

    private function enviarCorreoDiagnostico($destinatario, $nombre, $apellidos, $diagnostico): void
    {
        try {
            $texto = "Hola {$nombre} {$apellidos},\n\nSe ha registrado un nuevo diagnóstico ADIR para ti:\n\nConsulta tu diagnostico en la sección de Resultados\n\nSi tienes dudas, contacta a tu especialista.\n\nSaludos.";
            Mail::raw($texto, function ($m) use ($destinatario) {
                $m->from('aplicaciondediagnosticodetea@gmail.com', 'TEA Diagnóstico')
                    ->to($destinatario)
                    ->subject('Nuevo diagnóstico ADIR');
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de diagnóstico: ' . $e->getMessage());
        }
    }

    private function enviarCorreoDiagnosticoFinalADI($destinatario, $nombre, $apellidos): void
    {
        try {
            $mensaje = "
Hola {$nombre} {$apellidos},

Te informamos que el diagnóstico de tu test ADI-R ha sido actualizado por el especialista.

Ya puedes consultar el resultado desde la sección de \"Resultados\" en el sistema TEA Diagnóstico.

Saludos,
Equipo TEA Diagnóstico
";
            Mail::raw($mensaje, function ($m) use ($destinatario) {
                $m->from('aplicaciondediagnosticodetea@gmail.com', 'TEA Diagnóstico')
                    ->to($destinatario)
                    ->subject('Diagnóstico actualizado - Test ADI-R');
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de diagnóstico ADI-R: ' . $e->getMessage());
        }
    }
}
