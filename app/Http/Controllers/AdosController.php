<?php
// filepath: c:\Users\EverZr\Desktop\CICLOVIII\Desarrollo Web con Software Libre\Periodo3\Semana2\Parcial03\API_Laravel\app\Http\Controllers\AdosController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $actividades = Actividad::where('modulo', (string)$modulo)
            ->get();

        return response()->json($actividades);
    }

    // GET /api/ados/paciente/{id_paciente}
    public function paciente($id_paciente)
    {
        $paciente = Paciente::where('id_paciente', $id_paciente)->first();

        if (! $paciente) {
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
            'id_paciente'    => 'required|integer|exists:pacientes,id_paciente',
            'modulo'         => 'required',
            'id_especialista'=> 'required|integer|exists:especialistas,id_especialista',
            'estado'         => 'required|integer',
        ]);

        $test = TestAdos::create([
            'id_paciente'     => $data['id_paciente'],
            'modulo'          => $data['modulo'],
            'id_especialista' => $data['id_especialista'],
            'fecha'           => now(),
            'diagnostico'     => null,
            'total_punto'     => null,
            'estado'          => $data['estado'],
        ]);

        return response()->json(['id_ados' => $test->id_ados ?? $test->id], 201);
    }

    // PUT /api/ados/pausar/{id_ados}
    public function pausarTest(Request $request, $id_ados)
    {
        $data = $request->validate([
            'estado' => 'sometimes|integer',
        ]);

        $test = TestAdos::where('id_ados', $id_ados)->first();
        if (! $test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        if (isset($data['estado'])) $test->estado = $data['estado'];
        $test->save();

        return response()->json(['message' => 'Estado actualizado']);
    }

    // POST /api/ados/actividad-realizada
    // body: { id_ados, id_actividad, observacion }
    public function guardarActividadRealizada(Request $request)
    {
        $data = $request->validate([
            'id_ados'      => 'required|integer|exists:tests_ados,id_ados',
            'id_actividad' => 'required|integer|exists:actividades,id_actividad',
            'observacion'  => 'nullable|string',
        ]);

        ActividadRealizada::updateOrCreate(
            ['id_ados' => $data['id_ados'], 'id_actividad' => $data['id_actividad']],
            ['observacion' => $data['observacion'] ?? '']
        );

        return response()->json(['message' => 'Observación guardada']);
    }

    // GET /api/ados/codificacion/{id}
    public function codificacion($id)
    {
        $cod = Codificacion::where('id_codificacion', $id)->first();

        if (! $cod) return response()->json(['message' => 'Codificación no encontrada'], 404);

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

        RespuestaCodificacion::create([
            'id_ados' => $data['id_ados'],
            'id_puntuacion_codificacion' => $data['id_puntuacion_codificacion'],
        ]);

        return response()->json(['message' => 'Respuesta guardada']);
    }

    // GET /api/ados/algoritmo/{id}
    public function algoritmo($id)
    {
        $alg = Algoritmo::where('id_algoritmo', $id)->first();

        if (! $alg) return response()->json(['message' => 'Algoritmo no encontrado'], 404);

        return response()->json($alg);
    }
}