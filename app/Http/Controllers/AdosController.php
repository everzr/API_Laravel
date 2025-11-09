<?php
// filepath: c:\Users\EverZr\Desktop\CICLOVIII\Desarrollo Web con Software Libre\Periodo3\Semana2\Parcial03\API_Laravel\app\Http\Controllers\AdosController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Actividad;
use App\Models\Paciente;
use App\Models\TestAdos;
use App\Models\ActividadRealizada;
use App\Models\Codificacion;
use App\Models\PuntuacionCodificacion;
use App\Models\RespuestaCodificacion;
use App\Models\Algoritmo;

class AdosController extends Controller
{
    // GET /api/ados/actividades/{modulo}
    public function actividadesPorModulo($modulo)
    {
        $actividades = Actividad::where('modulo', (string) $modulo)
            ->get();

        return response()->json($actividades);
    }

    // GET /api/ados/paciente/{id_paciente}
    public function paciente($id_paciente)
    {
        $paciente = Paciente::where('id_paciente', $id_paciente)->first();

        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        return response()->json($paciente);
    }

    // GET /api/ados/actividades-realizadas/{id_ados}
    public function actividadesRealizadas($id_ados)
    {
        $obs = ActividadRealizada::where('id_ados', $id_ados)
            ->get(['id_actividad', 'observacion']);

        return response()->json($obs);
    }

    // POST /api/ados/crear  -> retorna { id_ados: <id> }
    public function crearTest(Request $request)
    {
        $data = $request->validate([
            'id_paciente' => 'required|integer|exists:pacientes,id_paciente',
            'modulo' => 'required',
            'id_especialista' => 'required|integer|exists:especialistas,id_especialista',
            'estado' => 'required|integer',
        ]);

        // Nota: Node usa tabla test_ados_2. Ajusta el modelo si tu tabla difiere.
        $id = DB::table('test_ados_2')->insertGetId([
            'id_paciente' => $data['id_paciente'],
            'fecha' => now(),
            'modulo' => $data['modulo'],
            'id_especialista' => $data['id_especialista'],
            'estado' => $data['estado'],
            'diagnostico' => null,
            'total_punto' => null,
        ]);

        return response()->json(['id_ados' => (int) $id], 201);
    }

    // PUT /api/ados/pausar/{id_ados}
    public function pausarTest(Request $request, $id_ados)
    {
        $data = $request->validate([
            'estado' => 'sometimes|integer',
        ]);

        $test = TestAdos::where('id_ados', $id_ados)->first();
        if (!$test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        if (isset($data['estado']))
            $test->estado = $data['estado'];
        $test->save();

        return response()->json(['message' => 'Estado actualizado']);
    }

    // POST /api/ados/actividad-realizada
    // body: { id_ados, id_actividad, observacion }
    public function guardarActividadRealizada(Request $request)
    {
        $data = $request->validate([
            'id_ados' => 'required|integer|exists:tests_ados,id_ados',
            'id_actividad' => 'required|integer|exists:actividades,id_actividad',
            'observacion' => 'nullable|string',
        ]);

        DB::insert("
            INSERT INTO actividad_realizada (id_ados, id_actividad, observacion)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE observacion = VALUES(observacion)
        ", [$data['id_ados'], $data['id_actividad'], $data['observacion'] ?? '']);

        return response()->json(['message' => 'Observación guardada']);
    }

    // GET /api/ados/codificacion/{id}
    public function codificacion($id)
    {
        $cod = Codificacion::where('id_codificacion', $id)->first();

        if (!$cod)
            return response()->json(['message' => 'Codificación no encontrada'], 404);

        return response()->json($cod);
    }

    // GET /api/ados/puntuaciones-codificacion/{id}
    public function puntuacionesCodificacion($id)
    {
        $p = PuntuacionCodificacion::where('id_codificacion', $id)
            ->select('id_puntuacion_codificacion', 'descripcion', 'puntaje')
            ->get();

        return response()->json($p);
    }

    // POST /api/ados/responder-codificacion
    public function responderCodificacion(Request $request)
    {
        $data = $request->validate([
            'id_ados' => 'required|integer|exists:tests_ados,id_ados',
            'id_puntuacion_codificacion' => 'required|integer|exists:puntuaciones_codificacion,id_puntuacion_codificacion',
        ]);

        // Lógica de reemplazo (similar a Node)
        $id_puntuacion_codificacion = $data['id_puntuacion_codificacion'];
        $id_ados = $data['id_ados'];

        $id_codificacion = DB::selectOne(
            "SELECT id_codificacion FROM puntuacion_codificacion WHERE id_puntuacion_codificacion = ?",
            [$id_puntuacion_codificacion]
        );
        if (!$id_codificacion) {
            return response()->json(['message' => 'Puntuación inválida'], 400);
        }

        $prev = DB::selectOne("
            SELECT pa.id_puntuacion_aplicada
            FROM puntuacion_aplicada pa
            JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
            WHERE pa.id_ados = ? AND pc.id_codificacion = ?
            LIMIT 1
        ", [$id_ados, $id_codificacion->id_codificacion]);

        if ($prev) {
            DB::update(
                "UPDATE puntuacion_aplicada SET id_puntuacion_codificacion = ? WHERE id_puntuacion_aplicada = ?",
                [$id_puntuacion_codificacion, $prev->id_puntuacion_aplicada]
            );
            return response()->json(['message' => 'Respuesta actualizada']);
        }

        DB::insert(
            "INSERT INTO puntuacion_aplicada (id_puntuacion_codificacion, id_ados) VALUES (?, ?)",
            [$id_puntuacion_codificacion, $id_ados]
        );
        return response()->json(['message' => 'Respuesta guardada']);
    }

    // GET /api/ados/algoritmo/{id}
    public function algoritmo($id)
    {
        $alg = Algoritmo::where('id_algoritmo', $id)->first();

        if (!$alg)
            return response()->json(['message' => 'Algoritmo no encontrado'], 404);

        return response()->json($alg);
    }

    // NUEVOS (PARIDAD CON adosController.js) ----------------------------------

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

    public function validarFiltrosPaciente($id_paciente)
    {
        $row = DB::selectOne("SELECT terminos_privacida, filtro_dsm_5 FROM paciente WHERE id_paciente = ?", [$id_paciente]);
        if (!$row)
            return response()->json(['message' => 'Paciente no encontrado.'], 404);
        if ($row->terminos_privacida != 1 || $row->filtro_dsm_5 != 1) {
            return response()->json([
                'permitido' => false,
                'message' => 'El paciente no ha aceptado los términos o no cumple el filtro DSM-5.'
            ]);
        }
        return response()->json(['permitido' => true]);
    }

    public function buscarTestPausado(Request $request)
    {
        $id_paciente = $request->query('id_paciente');
        $modulo = $request->query('modulo');
        $id_especialista = $request->query('id_especialista');

        $row = DB::selectOne("
            SELECT id_ados FROM test_ados_2
            WHERE id_paciente = ? AND modulo = ? AND id_especialista = ? AND estado = 1
            ORDER BY fecha DESC LIMIT 1
        ", [$id_paciente, $modulo, $id_especialista]);

        return response()->json($row ? ['id_ados' => $row->id_ados] : []);
    }

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

    public function puntuacionesPorCodificacion($id_codificacion)
    {
        $rows = DB::select("
            SELECT * FROM puntuacion_codificacion WHERE id_codificacion = ?
        ", [$id_codificacion]);
        return response()->json($rows);
    }

    public function obtenerPacientePorId($id_paciente)
    {
        $row = DB::selectOne("SELECT * FROM paciente WHERE id_paciente = ?", [$id_paciente]);
        if (!$row)
            return response()->json(['message' => 'Paciente no encontrado.'], 404);
        return response()->json($row);
    }

    public function codificacionPorId($id_codificacion)
    {
        $row = DB::selectOne("SELECT * FROM codificacion WHERE id_codificacion = ?", [$id_codificacion]);
        if (!$row)
            return response()->json(['message' => 'Codificación no encontrada.'], 404);
        return response()->json($row);
    }

    public function obtenerAlgoritmoPorId($id_algoritmo)
    {
        $row = DB::selectOne("SELECT * FROM algoritmo WHERE id_algoritmo = ?", [$id_algoritmo]);
        if (!$row)
            return response()->json(['message' => 'Algoritmo no encontrado.'], 404);
        return response()->json($row);
    }

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

    public function obtenerTestPorId($id_ados)
    {
        $row = DB::selectOne("SELECT * FROM test_ados_2 WHERE id_ados = ?", [$id_ados]);
        if (!$row)
            return response()->json(['message' => 'Test no encontrado'], 404);
        return response()->json($row);
    }

    public function obtenerAlgoritmoPorTest($id_ados)
    {
        $test = DB::selectOne("
            SELECT t.modulo, p.fecha_nacimiento, t.fecha
            FROM test_ados_2 t
            JOIN paciente p ON t.id_paciente = p.id_paciente
            WHERE t.id_ados = ?
        ", [$id_ados]);

        if (!$test)
            return response()->json(['message' => 'Test no encontrado'], 404);

        $modulo = $test->modulo;
        $fecha_nacimiento = new \DateTime($test->fecha_nacimiento);
        $fecha_test = new \DateTime($test->fecha);

        $edadAnios = $fecha_nacimiento->diff($fecha_test)->y;
        $edadMesesTot = ($fecha_nacimiento->diff($fecha_test)->y * 12) + $fecha_nacimiento->diff($fecha_test)->m;

        $id_algoritmo = null;

        if ($modulo === '1') {
            $puntajeRow = DB::selectOne("
                SELECT pc.puntaje
                FROM puntuacion_aplicada pa
                JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
                WHERE pa.id_ados = ? AND pc.id_codificacion = 1
                ORDER BY pa.id_puntuacion_aplicada DESC LIMIT 1
            ", [$id_ados]);

            if (!$puntajeRow)
                return response()->json(['message' => 'No se encontró respuesta para selección de algoritmo'], 404);
            $puntaje = (int) $puntajeRow->puntaje;
            $id_algoritmo = ($puntaje === 3 || $puntaje === 4) ? 1 : 2;
        } elseif ($modulo === '2') {
            $id_algoritmo = ($edadAnios < 5) ? 3 : 4;
        } elseif ($modulo === '3') {
            $id_algoritmo = 5;
        } elseif ($modulo === '4') {
            $id_algoritmo = 6;
        } elseif ($modulo === 'T') {
            $puntajeA1 = DB::selectOne("
                SELECT pc.puntaje
                FROM puntuacion_aplicada pa
                JOIN puntuacion_codificacion pc ON pa.id_puntuacion_codificacion = pc.id_puntuacion_codificacion
                JOIN codificacion c ON pc.id_codificacion = c.id_codificacion
                WHERE pa.id_ados = ? AND c.codigo = 'A1'
                ORDER BY pa.id_puntuacion_aplicada DESC LIMIT 1
            ", [$id_ados]);

            $pA1 = $puntajeA1 ? (int) $puntajeA1->puntaje : null;

            if (
                ($edadMesesTot >= 12 && $edadMesesTot <= 20) ||
                ($edadMesesTot >= 21 && $edadMesesTot <= 30 && ($pA1 === 3 || $pA1 === 4))
            ) {
                $id_algoritmo = 7;
            } elseif ($edadMesesTot >= 21 && $edadMesesTot <= 30 && in_array($pA1, [0, 1, 2])) {
                $id_algoritmo = 8;
            }
        } else {
            return response()->json(['message' => 'Módulo inválido'], 400);
        }

        return response()->json(['id_algoritmo' => $id_algoritmo]);
    }

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

    public function actualizarClasificacion(Request $request, $id_ados)
    {
        $clasificacion = $request->input('clasificacion');
        $total_punto = $request->input('total_punto');
        DB::update("UPDATE test_ados_2 SET clasificacion = ?, total_punto = ? WHERE id_ados = ?", [$clasificacion, $total_punto, $id_ados]);
        return response()->json(['message' => 'Clasificación actualizada']);
    }

    public function actualizarPuntuacionComparativa(Request $request, $id_ados)
    {
        $p = $request->input('puntuacion_comparativa');
        DB::update("UPDATE test_ados_2 SET puntuacion_comparativa = ? WHERE id_ados = ?", [$p, $id_ados]);
        return response()->json(['message' => 'Puntuación comparativa actualizada']);
    }

    public function actualizarDiagnostico(Request $request, $id_ados)
    {
        $diagnostico = $request->input('diagnostico');
        DB::update("UPDATE test_ados_2 SET diagnostico = ? WHERE id_ados = ?", [$diagnostico, $id_ados]);

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

    public function obtenerActividadesPorTest($id_ados)
    {
        $rows = DB::select("
            SELECT ar.id_actividad_realizada, ar.id_actividad, a.nombre_actividad, ar.observacion
            FROM actividad_realizada ar
            JOIN actividad a ON ar.id_actividad = a.id_actividad
            WHERE ar.id_ados = ?
            ORDER BY ar.id_actividad_realizada
        ", [$id_ados]);

        return response()->json($rows);
    }

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

    // Reportes por módulo (T, 1, 2, 3, 4) comparten estructura auxiliar:
    private function datosBaseReporte($id_ados)
    {
        return DB::selectOne("
            SELECT
                t.id_ados, t.fecha, t.modulo, t.diagnostico, t.clasificacion, t.total_punto,
                t.puntuacion_comparativa,
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

    public function obtenerDatosReporteModuloT($id_ados)
    {
        $datos = $this->datosBaseReporte($id_ados);
        if (!$datos)
            return response()->json(['message' => 'No se pudo obtener datos del test'], 500);

        $puntuaciones = $this->puntuacionesAplicadas($id_ados);
        $puntajeA1 = optional(collect($puntuaciones)->firstWhere('codigo', 'A1'))->puntaje;

        $nac = new \DateTime($datos->fecha_nacimiento);
        $fechaTest = new \DateTime($datos->fecha);
        $diff = $nac->diff($fechaTest);
        $edadMeses = $diff->y * 12 + $diff->m - ($fechaTest->format('d') < $nac->format('d') ? 1 : 0);

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

    public function obtenerDatosReporteModulo2($id_ados)
    {
        $datos = $this->datosBaseReporte($id_ados);
        if (!$datos)
            return response()->json(['message' => 'No se pudo obtener datos del test'], 500);

        $nac = new \DateTime($datos->fecha_nacimiento);
        $fechaTest = new \DateTime($datos->fecha);
        $edad = $nac->diff($fechaTest)->y;
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

    // Correo diagnóstico ADOS-2
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
                $m->from('aplicaciondediagnosticodetea@gmail.com', 'TEA Diagnóstico')
                    ->to($destinatario)
                    ->subject('Diagnóstico actualizado - Test ADOS-2');
            });
        } catch (\Throwable $e) {
            // Silencioso
        }
    }
}
