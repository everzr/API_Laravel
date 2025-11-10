<?php
// filepath: c:\Users\EverZr\Desktop\CICLOVIII\Desarrollo Web con Software Libre\Periodo3\Semana2\Parcial03\API_Laravel\app\Http\Controllers\AdosController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdosController extends Controller
{
    // Paridad: listar actividades por módulo (igual a listarActividadesPorModulo en Node)
    // GET /api/ados/actividades/{modulo}
    public function actividadesPorModulo($modulo)
    {
        $rows = DB::select("
            SELECT id_actividad,
                   nombre_actividad,
                   objetivo,
                   CAST(materiales AS CHAR) AS materiales,
                   CAST(intrucciones AS CHAR) AS intrucciones,
                   CAST(aspectos_observar AS CHAR) AS aspectos_observar,
                   CAST(info_complementaria AS CHAR) AS info_complementaria
            FROM actividad
            WHERE modulo = ?
            ORDER BY id_actividad
        ", [(string) $modulo]);

        return response()->json($rows);
    }

    // GET /api/ados/actividades-realizadas/{id_ados}
    public function actividadesRealizadas($id_ados)
    {
        $rows = DB::select("
            SELECT id_actividad, observacion
            FROM actividad_realizada
            WHERE id_ados = ?
        ", [$id_ados]);

        return response()->json($rows);
    }

    // POST /api/ados/crear
    public function crearTest(Request $request)
    {
        $data = $request->validate([
            'id_paciente' => 'required|integer',
            'modulo' => 'required|string',
            'id_especialista' => 'required|integer',
        ]);

        $id = DB::table('test_ados_2')->insertGetId([
            'id_paciente' => $data['id_paciente'],
            'fecha' => now(),
            'modulo' => $data['modulo'],
            'id_especialista' => $data['id_especialista'],
            'estado' => 1, // pausado/en curso
            'diagnostico' => null,
            'total_punto' => null,
            'clasificacion' => null,
            'puntuacion_comparativa' => null,
        ]);

        return response()->json(['id_ados' => (int) $id]);
    }

    // PUT /api/ados/pausar/{id_ados}
    public function pausarTest(Request $request, $id_ados)
    {
        $estado = (int) $request->input('estado', 1);
        DB::update("UPDATE test_ados_2 SET estado = ? WHERE id_ados = ?", [$estado, $id_ados]);
        return response()->json(['message' => 'Test actualizado']);
    }

    // POST /api/ados/actividad-realizada
    public function guardarActividadRealizada(Request $request)
    {
        $data = $request->validate([
            'id_ados' => 'required|integer',
            'id_actividad' => 'required|integer',
            'observacion' => 'nullable|string',
        ]);

        DB::insert("
            INSERT INTO actividad_realizada (id_ados, id_actividad, observacion)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE observacion = VALUES(observacion)
        ", [$data['id_ados'], $data['id_actividad'], $data['observacion'] ?? '']);

        return response()->json(['message' => 'Observación guardada']);
    }

    // GET /api/ados/test-pausado?id_paciente=&modulo=&id_especialista=
    public function buscarTestPausado(Request $request)
    {
        $row = DB::selectOne("
            SELECT id_ados
            FROM test_ados_2
            WHERE id_paciente = ? AND modulo = ? AND id_especialista = ? AND estado = 1
            ORDER BY fecha DESC LIMIT 1
        ", [
            $request->query('id_paciente'),
            $request->query('modulo'),
            $request->query('id_especialista'),
        ]);

        return response()->json($row ? ['id_ados' => $row->id_ados] : []);
    }

    // POST /api/ados/responder-item
    public function responderItem(Request $request)
    {
        $data = $request->validate([
            'id_ados' => 'required|integer',
            'id_item' => 'required|integer',
            'puntaje' => 'required|integer',
        ]);

        DB::insert("
            INSERT INTO puntuacion_aplicada (id_item, puntaje, id_ados)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE puntaje = VALUES(puntaje)
        ", [$data['id_item'], $data['puntaje'], $data['id_ados']]);

        return response()->json(['message' => 'Respuesta guardada']);
    }

    // POST /api/ados/responder-codificacion
    public function responderCodificacion(Request $request)
    {
        $data = $request->validate([
            'id_ados' => 'required|integer',
            'id_puntuacion_codificacion' => 'required|integer',
        ]);

        $pc = DB::selectOne("SELECT id_codificacion FROM puntuacion_codificacion WHERE id_puntuacion_codificacion = ?", [$data['id_puntuacion_codificacion']]);
        if (!$pc)
            return response()->json(['message' => 'Puntuación inválida'], 400);

        $prev = DB::selectOne("
            SELECT pa.id_puntuacion_aplicada
            FROM puntuacion_aplicada pa
            JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
            WHERE pa.id_ados = ? AND pc.id_codificacion = ?
            LIMIT 1
        ", [$data['id_ados'], $pc->id_codificacion]);

        if ($prev) {
            DB::update("
                UPDATE puntuacion_aplicada
                SET id_puntuacion_codificacion = ?
                WHERE id_puntuacion_aplicada = ?
            ", [$data['id_puntuacion_codificacion'], $prev->id_puntuacion_aplicada]);

            return response()->json(['message' => 'Respuesta actualizada']);
        }

        DB::insert("
            INSERT INTO puntuacion_aplicada (id_puntuacion_codificacion, id_ados)
            VALUES (?, ?)
        ", [$data['id_puntuacion_codificacion'], $data['id_ados']]);

        return response()->json(['message' => 'Respuesta guardada']);
    }

    // GET /api/ados/puntuaciones-codificacion/{id_codificacion}
    public function puntuacionesPorCodificacion($id_codificacion)
    {
        $rows = DB::select("
            SELECT * FROM puntuacion_codificacion WHERE id_codificacion = ?
        ", [$id_codificacion]);
        return response()->json($rows);
    }

    // GET /api/ados/codificaciones-algoritmo/{id_algoritmo}
    public function codificacionesPorAlgoritmo($id_algoritmo)
    {
        $rows = DB::select("
            SELECT c.*
            FROM item_algoritmo ia
            JOIN item i ON ia.id_item = i.id_item
            JOIN codificacion c ON i.id_codificacion = c.id_codificacion
            WHERE ia.id_algoritmo = ?
        ", [$id_algoritmo]);
        return response()->json($rows);
    }

    // GET /api/ados/respuestas-algoritmo/{id_ados}
    public function respuestasAlgoritmo($id_ados)
    {
        $rows = DB::select("
            SELECT pc.id_codificacion, pa.id_puntuacion_codificacion
            FROM puntuacion_aplicada pa
            JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
            WHERE pa.id_ados = ?
        ", [$id_ados]);
        return response()->json($rows);
    }

    // GET /api/ados/test/{id_ados}
    public function obtenerTestPorId($id_ados)
    {
        $row = DB::selectOne("SELECT * FROM test_ados_2 WHERE id_ados = ?", [$id_ados]);
        if (!$row)
            return response()->json(['message' => 'Test no encontrado'], 404);
        return response()->json($row);
    }

    // GET /api/ados/algoritmo-por-test/{id_ados}
    public function obtenerAlgoritmoPorTest($id_ados)
    {
        $row = DB::selectOne("
            SELECT t.modulo, p.fecha_nacimiento, t.fecha
            FROM test_ados_2 t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            WHERE t.id_ados = ?
        ", [$id_ados]);

        if (!$row)
            return response()->json(['message' => 'Test no encontrado'], 404);

        $modulo = $row->modulo;
        $fn = new \DateTime($row->fecha_nacimiento);
        $ft = new \DateTime($row->fecha);
        $diff = $fn->diff($ft);
        $edadAnios = $diff->y;
        $edadMeses = $diff->y * 12 + $diff->m - ($ft->format('d') < $fn->format('d') ? 1 : 0);

        $id_algoritmo = null;

        if ($modulo === '1') {
            $resp = DB::selectOne("
                SELECT pc.puntaje
                FROM puntuacion_aplicada pa
                JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
                WHERE pa.id_ados = ? AND pc.id_codificacion = 1
                ORDER BY pa.id_puntuacion_aplicada DESC LIMIT 1
            ", [$id_ados]);

            if (!$resp)
                return response()->json(['message' => 'No se encontró respuesta para selección de algoritmo'], 404);
            $id_algoritmo = (in_array((int) $resp->puntaje, [3, 4])) ? 1 : 2;
        } elseif ($modulo === '2') {
            $id_algoritmo = ($edadAnios < 5) ? 3 : 4;
        } elseif ($modulo === '3') {
            $id_algoritmo = 5;
        } elseif ($modulo === '4') {
            $id_algoritmo = 6;
        } elseif ($modulo === 'T') {
            $respA1 = DB::selectOne("
                SELECT pc.puntaje
                FROM puntuacion_aplicada pa
                JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
                JOIN codificacion c ON pc.id_codificacion = c.id_codificacion
                WHERE pa.id_ados = ? AND c.codigo = 'A1'
                ORDER BY pa.id_puntuacion_aplicada DESC LIMIT 1
            ", [$id_ados]);

            $pA1 = $respA1 ? (int) $respA1->puntaje : null;

            if (
                ($edadMeses >= 12 && $edadMeses <= 20) ||
                ($edadMeses >= 21 && $edadMeses <= 30 && in_array($pA1, [3, 4]))
            ) {
                $id_algoritmo = 7;
            } elseif ($edadMeses >= 21 && $edadMeses <= 30 && in_array($pA1, [0, 1, 2])) {
                $id_algoritmo = 8;
            }
        } else {
            return response()->json(['message' => 'Módulo inválido'], 400);
        }

        return response()->json(['id_algoritmo' => $id_algoritmo]);
    }

    // GET /api/ados/puntuaciones-aplicadas/{id_ados}
    public function puntuacionesAplicadasPorTest($id_ados)
    {
        $rows = DB::select("
            SELECT pa.id_puntuacion_aplicada, pc.puntaje, pc.id_codificacion
            FROM puntuacion_aplicada pa
            JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
            WHERE pa.id_ados = ?
        ", [$id_ados]);
        return response()->json($rows);
    }

    // PUT /api/ados/clasificacion/{id_ados}
    public function actualizarClasificacion(Request $request, $id_ados)
    {
        DB::update("
            UPDATE test_ados_2 SET clasificacion = ?, total_punto = ? WHERE id_ados = ?
        ", [$request->input('clasificacion'), $request->input('total_punto'), $id_ados]);

        return response()->json(['message' => 'Clasificación actualizada']);
    }

    // PUT /api/ados/puntuacion-comparativa/{id_ados}
    public function actualizarPuntuacionComparativa(Request $request, $id_ados)
    {
        DB::update("
            UPDATE test_ados_2 SET puntuacion_comparativa = ? WHERE id_ados = ?
        ", [$request->input('puntuacion_comparativa'), $id_ados]);

        return response()->json(['message' => 'Puntuación comparativa actualizada']);
    }

    // PUT /api/ados/diagnostico/{id_ados}
    public function actualizarDiagnostico(Request $request, $id_ados)
    {
        DB::update("UPDATE test_ados_2 SET diagnostico = ? WHERE id_ados = ?", [$request->input('diagnostico'), $id_ados]);

        $info = DB::selectOne("
            SELECT u.correo, u.nombres, u.apellidos
            FROM test_ados_2 t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            JOIN usuario u ON p.id_usuario = u.id_usuario
            WHERE t.id_ados = ?
        ", [$id_ados]);

        if ($info) {
            $this->enviarCorreoDiagnosticoADOS($info->correo, $info->nombres, $info->apellidos);
        }

        return response()->json(['message' => 'Diagnóstico actualizado y paciente notificado']);
    }

    // GET /api/ados/actividades-por-test/{id_ados}
    public function obtenerActividadesPorTest($id_ados)
    {
        $rows = DB::select("
            SELECT ar.id_actividad_realizada,
                   ar.id_actividad,
                   a.nombre_actividad,
                   ar.observacion
            FROM actividad_realizada ar
            JOIN actividad a ON ar.id_actividad = a.id_actividad
            WHERE ar.id_ados = ?
            ORDER BY ar.id_actividad_realizada
        ", [$id_ados]);

        return response()->json($rows);
    }

    // GET /api/ados/grupo-codificacion/{id_codificacion}
    public function obtenerGrupoPorCodificacion($id_codificacion)
    {
        $row = DB::selectOne("
            SELECT i.grupo
            FROM item i
            JOIN codificacion c ON c.id_codificacion = i.id_codificacion
            WHERE c.id_codificacion = ?
            LIMIT 1
        ", [$id_codificacion]);

        if (!$row)
            return response()->json(['message' => 'No se encontró grupo'], 404);
        return response()->json(['grupo' => $row->grupo]);
    }

    // Reportes (módulos T,1,2,3,4) - reutilizar helpers Node
    private function datosBaseReporte($id_ados)
    {
        return DB::selectOne("
            SELECT
                t.id_ados, t.fecha, t.modulo, t.diagnostico, t.clasificacion, t.total_punto, t.puntuacion_comparativa,
                u.nombres, u.apellidos, u.telefono,
                e.id_especialista,
                ue.nombres AS especialista_nombres, ue.apellidos AS especialista_apellidos,
                p.fecha_nacimiento
            FROM test_ados_2 t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            JOIN usuario u ON p.id_usuario = u.id_usuario
            LEFT JOIN especialista e ON t.id_especialista = e.id_especialista
            LEFT JOIN usuario ue ON e.id_usuario = ue.id_usuario
            WHERE t.id_ados = ?
        ", [$id_ados]);
    }

    private function puntuacionesAplicadas($id_ados)
    {
        return DB::select("
            SELECT pc.puntaje, pc.id_codificacion, c.codigo
            FROM puntuacion_aplicada pa
            JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
            JOIN codificacion c ON pc.id_codificacion = c.id_codificacion
            WHERE pa.id_ados = ?
        ", [$id_ados]);
    }

    private function observacionesFinales($id_ados)
    {
        return DB::select("
            SELECT ar.observacion, a.nombre_actividad
            FROM actividad_realizada ar
            JOIN actividad a ON ar.id_actividad = a.id_actividad
            WHERE ar.id_ados = ?
        ", [$id_ados]);
    }

    // GET /api/ados/reporte-modulo-t/{id_ados}
    public function obtenerDatosReporteModuloT($id_ados)
    {
        $datos = $this->datosBaseReporte($id_ados);
        if (!$datos)
            return response()->json(['message' => 'No se pudo obtener datos del test'], 500);

        $puntuaciones = $this->puntuacionesAplicadas($id_ados);
        $puntajeA1 = optional(collect($puntuaciones)->firstWhere('codigo', 'A1'))->puntaje;

        $nac = new \DateTime($datos->fecha_nacimiento);
        $ft = new \DateTime($datos->fecha);
        $diff = $nac->diff($ft);
        $edadMeses = $diff->y * 12 + $diff->m - ($ft->format('d') < $nac->format('d') ? 1 : 0);

        $id_algoritmo = null;
        if (
            ($edadMeses >= 12 && $edadMeses <= 20) ||
            ($edadMeses >= 21 && $edadMeses <= 30 && in_array((int) $puntajeA1, [3, 4]))
        ) {
            $id_algoritmo = 7;
        } elseif ($edadMeses >= 21 && $edadMeses <= 30 && in_array((int) $puntajeA1, [0, 1, 2])) {
            $id_algoritmo = 8;
        }

        $observaciones = $this->observacionesFinales($id_ados);

        return response()->json([
            'datos' => $datos,
            'puntuaciones' => $puntuaciones,
            'observaciones' => $observaciones,
            'id_algoritmo' => $id_algoritmo
        ]);
    }

    // GET /api/ados/reporte-modulo-1/{id_ados}
    public function obtenerDatosReporteModulo1($id_ados)
    {
        $datos = $this->datosBaseReporte($id_ados);
        if (!$datos)
            return response()->json(['message' => 'No se pudo obtener datos del test'], 500);

        $puntuaciones = $this->puntuacionesAplicadas($id_ados);
        $puntajeA1 = optional(collect($puntuaciones)->firstWhere('codigo', 'A1'))->puntaje;
        $id_algoritmo = in_array((int) $puntajeA1, [3, 4]) ? 1 : 2;

        $observaciones = $this->observacionesFinales($id_ados);

        return response()->json([
            'datos' => $datos,
            'puntuaciones' => $puntuaciones,
            'observaciones' => $observaciones,
            'id_algoritmo' => $id_algoritmo
        ]);
    }

    // GET /api/ados/reporte-modulo-2/{id_ados}
    public function obtenerDatosReporteModulo2($id_ados)
    {
        $datos = $this->datosBaseReporte($id_ados);
        if (!$datos)
            return response()->json(['message' => 'No se pudo obtener datos del test'], 500);

        $nac = new \DateTime($datos->fecha_nacimiento);
        $ft = new \DateTime($datos->fecha);
        $edad = $nac->diff($ft)->y;
        $id_algoritmo = ($edad < 5) ? 3 : 4;

        $puntuaciones = $this->puntuacionesAplicadas($id_ados);
        $observaciones = $this->observacionesFinales($id_ados);

        return response()->json([
            'datos' => $datos,
            'puntuaciones' => $puntuaciones,
            'observaciones' => $observaciones,
            'id_algoritmo' => $id_algoritmo
        ]);
    }

    // GET /api/ados/reporte-modulo-3/{id_ados}
    public function obtenerDatosReporteModulo3($id_ados)
    {
        $datos = $this->datosBaseReporte($id_ados);
        if (!$datos)
            return response()->json(['message' => 'No se pudo obtener datos del test'], 500);

        $id_algoritmo = 5;
        $puntuaciones = $this->puntuacionesAplicadas($id_ados);
        $observaciones = $this->observacionesFinales($id_ados);

        return response()->json([
            'datos' => $datos,
            'puntuaciones' => $puntuaciones,
            'observaciones' => $observaciones,
            'id_algoritmo' => $id_algoritmo
        ]);
    }

    // GET /api/ados/reporte-modulo-4/{id_ados}
    public function obtenerDatosReporteModulo4($id_ados)
    {
        $datos = $this->datosBaseReporte($id_ados);
        if (!$datos)
            return response()->json(['message' => 'No se pudo obtener datos del test'], 500);

        $id_algoritmo = 6;
        $puntuaciones = $this->puntuacionesAplicadas($id_ados);
        $observaciones = $this->observacionesFinales($id_ados);

        return response()->json([
            'datos' => $datos,
            'puntuaciones' => $puntuaciones,
            'observaciones' => $observaciones,
            'id_algoritmo' => $id_algoritmo
        ]);
    }

    private function enviarCorreoDiagnosticoADOS($destinatario, $nombre, $apellidos): void
    {
        try {
            $mensaje = "
Hola {$nombre} {$apellidos},

Te informamos que el diagnóstico de tu test ADOS-2 ha sido actualizado por el especialista.

Ya puedes consultar el resultado desde la sección de \"Resultados\" en el sistema TEA Diagnóstico.

Saludos,
Equipo TEA Diagnóstico
";
            Mail::raw($mensaje, function ($m) use ($destinatario) {
                $m->from(config('mail.from.address'), config('mail.from.name'))
                    ->to($destinatario)
                    ->subject('Diagnóstico actualizado - Test ADOS-2');
            });
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    // GET /api/ados/pacientes
    public function listarPacientesConAdos()
    {
        $rows = DB::select("
            SELECT p.id_paciente, u.nombres, u.apellidos, p.sexo, p.fecha_nacimiento
            FROM paciente p
            JOIN usuario u ON p.id_usuario = u.id_usuario
            ORDER BY u.apellidos, u.nombres
        ");
        return response()->json($rows);
    }

    // GET /api/ados/tests/{id_paciente}
    public function listarTestsAdosPorPaciente($id_paciente)
    {
        $rows = DB::select("
            SELECT t.id_ados, t.fecha, t.modulo, t.diagnostico, t.total_punto, t.clasificacion,
                   t.puntuacion_comparativa, t.estado, t.id_paciente
            FROM test_ados_2 t
            WHERE t.id_paciente = ?
            ORDER BY t.fecha DESC
        ", [$id_paciente]);

        return response()->json($rows);
    }

    // GET /api/ados/validar-filtros/{id_paciente}
    public function validarFiltrosPaciente($id_paciente)
    {
        $row = DB::selectOne("
            SELECT terminos_privacida, filtro_dsm_5
            FROM paciente
            WHERE id_paciente = ?
        ", [$id_paciente]);

        if (!$row) {
            return response()->json(['message' => 'Paciente no encontrado.'], 404);
        }

        $permitido = ((int)($row->terminos_privacida ?? 0) === 1) && ((int)($row->filtro_dsm_5 ?? 0) === 1);
        if (!$permitido) {
            return response()->json([
                'permitido' => false,
                'message' => 'El paciente no ha aceptado los términos de privacidad o no cumple el filtro DSM-5.'
            ], 200);
        }

        return response()->json(['permitido' => true], 200);
    }

    // GET /api/ados/paciente/{id_paciente}
    public function paciente($id_paciente)
    {
        $row = DB::selectOne("SELECT * FROM paciente WHERE id_paciente = ?", [$id_paciente]);
        if (!$row) {
            return response()->json(['message' => 'Paciente no encontrado.'], 404);
        }
        return response()->json($row);
    }

    // GET /api/ados/codificacion/{id_codificacion}
    public function codificacion($id_codificacion)
    {
        $row = DB::selectOne("SELECT * FROM codificacion WHERE id_codificacion = ?", [$id_codificacion]);
        if (!$row) {
            return response()->json(['message' => 'Codificación no encontrada.'], 404);
        }
        return response()->json($row);
    }

    // GET /api/ados/algoritmo/{id_algoritmo}
    public function obtenerAlgoritmoPorId($id_algoritmo)
    {
        $row = DB::selectOne("SELECT * FROM algoritmo WHERE id_algoritmo = ?", [$id_algoritmo]);
        if (!$row) {
            return response()->json(['message' => 'Algoritmo no encontrado.'], 404);
        }
        return response()->json($row);
    }

    // Alias para compatibilidad con rutas antiguas
    public function puntuacionesCodificacion($id)
    {
        return $this->puntuacionesPorCodificacion($id);
    }
}
