<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Usuario;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\TestAdos;
use App\Models\TestAdir;
use App\Models\Actividad;
use App\Models\ActividadRealizada;

class EstadisticaController extends Controller
{
    // GET /api/estadisticas/admin/usuarios
   public function usuarios(Request $req)
    {
        $byTipo = Usuario::select('privilegio', DB::raw('COUNT(*) as total'))
            ->groupBy('privilegio')->get();

        // conteo por tipo SOLO usuarios activos (estado = 1)
        $byTipoActivos = Usuario::where('estado', 1)
            ->select('privilegio', DB::raw('COUNT(*) as total'))
            ->groupBy('privilegio')->get();

        $byEstado = Usuario::select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')->get();

        $total = Usuario::count();
        $totalActivos = Usuario::where('estado', 1)->count();

        return response()->json([
            'total' => (int) $total,
            'total_activos' => (int) $totalActivos,
            'por_tipo' => $byTipo,
            'por_tipo_activos' => $byTipoActivos,
            'por_estado' => $byEstado,
        ]);
    }

    // GET /api/estadisticas/admin/especialistas
    public function especialistas(Request $req)
    {
        // especialistas activos (considero activo si su usuario.estado = 1)
        $activos = Especialista::join('usuario', 'especialista.id_usuario', '=', 'usuario.id_usuario')
            ->where('usuario.estado', 1)
            ->distinct('especialista.id_especialista')
            ->count('especialista.id_especialista');

        // ranking: casos atendidos por especialista (pacientes distintos)
        $ranking = TestAdos::join('especialista', 'test_ados_2.id_especialista', '=', 'especialista.id_especialista')
            ->join('usuario', 'especialista.id_usuario', '=', 'usuario.id_usuario')
            ->select('especialista.id_especialista', 'usuario.nombres', 'usuario.apellidos', DB::raw('COUNT(DISTINCT test_ados_2.id_paciente) as pacientes_atendidos'))
            ->groupBy('especialista.id_especialista', 'usuario.nombres', 'usuario.apellidos')
            ->orderByDesc('pacientes_atendidos')
            ->get();

        return response()->json([
            'especialistas_activos' => (int) $activos,
            'ranking_por_casos' => $ranking
        ]);
    }

    // GET /api/estadisticas/admin/pacientes
    // opcionales: ?min_age=&max_age= no implementados aquí (filtrado simple)
    public function pacientes(Request $req)
    {
        $total = Paciente::count();

        $porSexo = Paciente::select('sexo', DB::raw('COUNT(*) as total'))
            ->groupBy('sexo')->get();

        // distribución por rangos de edad (usando TIMESTAMPDIFF YEAR en DB)
        $edadDistrib = Paciente::selectRaw("
            CASE
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 0 AND 2 THEN '0-2'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 3 AND 4 THEN '3-4'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 5 AND 10 THEN '5-10'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 11 AND 17 THEN '11-17'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 31 AND 50 THEN '31-50'
                ELSE '51+' END as rango
        ")->selectRaw('COUNT(*) as total')->groupBy('rango')->pluck('total', 'rango');

        $conDsm5 = Paciente::where('filtro_dsm_5', 1)->count();

        return response()->json([
            'total' => (int) $total,
            'por_sexo' => $porSexo,
            'edad_distribucion' => $edadDistrib,
            'pacientes_con_dsm5' => (int) $conDsm5
        ]);
    }

    // GET /api/estadisticas/admin/evaluaciones
    // retorna totales ADOS/ADIR y distribución por módulo (ADOS)
    public function evaluaciones(Request $req)
    {
        $totalAdos = TestAdos::count();
        $totalAdir = TestAdir::count();

        $porModulo = TestAdos::select('modulo', DB::raw('COUNT(*) as total'))
            ->groupBy('modulo')->get();

        return response()->json([
            'total_ados' => (int) $totalAdos,
            'total_adir' => (int) $totalAdir,
            'ados_por_modulo' => $porModulo
        ]);
    }

    // GET /api/estadisticas/especialista/pacientes/{id_usuario}
    // Retorna: total pacientes (activos), distribucion por sexo, distribucion por rangos de edad, edad promedio
    public function pacientesEspecialista($id_usuario)
    {
        $espe = Especialista::where('id_usuario', $id_usuario)->first();
        if (! $espe) {
            return response()->json([
                'total' => 0,
                'por_sexo' => [],
                'edad_distribucion' => [],
                'average_age_years' => 0.0
            ]);
        }
        $id_especialista = $espe->id_especialista;

        $patientIds = TestAdos::where('id_especialista', $id_especialista)
            ->pluck('id_paciente')->unique()->values()->all();

        if (empty($patientIds)) {
            return response()->json([
                'total' => 0,
                'por_sexo' => [],
                'edad_distribucion' => [],
                'average_age_years' => 0.0
            ]);
        }

        $pacientesQuery = Paciente::join('usuario', 'paciente.id_usuario', '=', 'usuario.id_usuario')
            ->whereIn('paciente.id_paciente', $patientIds)
            ->where('usuario.estado', 1)
            ->select('paciente.*');

        $total = $pacientesQuery->count();

        $porSexo = $pacientesQuery->groupBy('sexo')
            ->select('sexo', DB::raw('COUNT(*) as total'))
            ->get();

        $avgRow = $pacientesQuery->selectRaw('AVG(TIMESTAMPDIFF(MONTH, fecha_nacimiento, CURDATE())/12) as avg_age')->first();
        $average = $avgRow ? round((float)$avgRow->avg_age, 2) : 0.0;

        $edadDistrib = $pacientesQuery->selectRaw("
            CASE
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 0 AND 2 THEN '0-2'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 3 AND 4 THEN '3-4'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 5 AND 10 THEN '5-10'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 11 AND 17 THEN '11-17'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 31 AND 50 THEN '31-50'
                ELSE '51+' END as rango
        ")->selectRaw('COUNT(*) as total')->groupBy('rango')->pluck('total','rango');

        return response()->json([
            'total' => (int) $total,
            'por_sexo' => $porSexo,
            'edad_distribucion' => $edadDistrib,
            'average_age_years' => $average
        ]);
    }

    // GET /api/estadisticas/especialista/evaluaciones/{id_usuario}
    // Retorna: totales (ADOS, ADI-R) realizados por el especialista
    public function evaluacionesEspecialista($id_usuario)
    {
        $espe = Especialista::where('id_usuario', $id_usuario)->first();
        if (! $espe) {
            return response()->json([
                'total_ados' => 0,
                'total_adir' => 0,
                'ados_por_modulo' => []
            ]);
        }
        $id_especialista = $espe->id_especialista;

        $totalAdos = TestAdos::where('id_especialista', $id_especialista)->count();
        $totalAdir = TestAdir::where('id_especialista', $id_especialista)->count();

        $porModulo = TestAdos::where('id_especialista', $id_especialista)
            ->groupBy('modulo')
            ->select('modulo', DB::raw('COUNT(*) as total'))
            ->get();

        return response()->json([
            'total_ados' => (int) $totalAdos,
            'total_adir' => (int) $totalAdir,
            'ados_por_modulo' => $porModulo
        ]);
    }

    // GET /api/estadisticas/especialista/diagnosticos/{id_usuario}
    // Retorna: conteo por diagnostico y % de pacientes con diagnóstico confirmado
    public function diagnosticosEspecialista($id_usuario)
    {
        $espe = Especialista::where('id_usuario', $id_usuario)->first();
        if (! $espe) {
            return response()->json([
                'por_diagnostico' => [],
                'percent_confirmed' => 0.0,
                'total_patients' => 0,
                'confirmed_patients' => 0
            ]);
        }
        $id_especialista = $espe->id_especialista;

        $patientIds = TestAdos::where('id_especialista', $id_especialista)->pluck('id_paciente')->unique()->values()->all();
        if (empty($patientIds)) {
            return response()->json([
                'por_diagnostico' => [],
                'percent_confirmed' => 0.0,
                'total_patients' => 0,
                'confirmed_patients' => 0
            ]);
        }

        $totalActive = Paciente::join('usuario', 'paciente.id_usuario', '=', 'usuario.id_usuario')
            ->whereIn('paciente.id_paciente', $patientIds)
            ->where('usuario.estado', 1)
            ->distinct('paciente.id_paciente')
            ->count('paciente.id_paciente');

        $porDiag = TestAdos::where('id_especialista', $id_especialista)
            ->whereNotNull('diagnostico')
            ->where('diagnostico', '<>', '')
            ->groupBy('diagnostico')
            ->select('diagnostico', DB::raw('COUNT(*) as total'))
            ->orderByDesc('total')
            ->get();

        $confirmedPatients = TestAdos::where('id_especialista', $id_especialista)
            ->whereNotNull('diagnostico')->where('diagnostico', '<>')
            ->distinct('id_paciente')->pluck('id_paciente')->unique()->values()->all();

        $confirmedActiveCount = Paciente::join('usuario', 'paciente.id_usuario', '=', 'usuario.id_usuario')
            ->whereIn('paciente.id_paciente', $confirmedPatients)
            ->where('usuario.estado', 1)
            ->distinct('paciente.id_paciente')->count('paciente.id_paciente');

        $percent = $totalActive ? round(100 * $confirmedActiveCount / $totalActive, 2) : 0.0;

        return response()->json([
            'por_diagnostico' => $porDiag,
            'percent_confirmed' => $percent,
            'total_patients' => (int) $totalActive,
            'confirmed_patients' => (int) $confirmedActiveCount
        ]);
    }

    // GET /api/estadisticas/especialista/actividades/{id_usuario}
    // Retorna: actividades más aplicadas y actividades pendientes/recomendadas por módulo
    public function actividadesEspecialista($id_usuario)
    {
        $espe = Especialista::where('id_usuario', $id_usuario)->first();
        if (! $espe) {
            return response()->json([
                'aplicadas_top' => [],
                'pending_by_module' => [],
                'modules' => []
            ]);
        }
        $id_especialista = $espe->id_especialista;

        $applied = ActividadRealizada::join('test_ados_2', 'actividad_realizada.id_ados', '=', 'test_ados_2.id_ados')
            ->where('test_ados_2.id_especialista', $id_especialista)
            ->groupBy('actividad_realizada.id_actividad')
            ->select('actividad_realizada.id_actividad', DB::raw('COUNT(*) as aplicadas'))
            ->orderByDesc('aplicadas')
            ->get();

        $appliedWithNames = $applied->map(function ($row) {
            $act = Actividad::find($row->id_actividad);
            return [
                'id_actividad' => $row->id_actividad,
                'nombre_actividad' => $act->nombre_actividad ?? null,
                'aplicadas' => (int) $row->aplicadas
            ];
        });

        $modules = TestAdos::where('id_especialista', $id_especialista)->distinct()->pluck('modulo')->filter()->values()->all();

        $pendingByModule = [];
        foreach ($modules as $mod) {
            $allIds = Actividad::where('modulo', $mod)->pluck('id_actividad')->all();
            $appliedIds = ActividadRealizada::join('test_ados_2', 'actividad_realizada.id_ados', '=', 'test_ados_2.id_ados')
                ->where('test_ados_2.id_especialista', $id_especialista)
                ->where('test_ados_2.modulo', $mod)
                ->pluck('actividad_realizada.id_actividad')->unique()->values()->all();

            $pendingIds = array_values(array_diff($allIds, $appliedIds));
            $pending = Actividad::whereIn('id_actividad', $pendingIds)->get(['id_actividad', 'nombre_actividad']);

            $pendingByModule[] = [
                'modulo' => $mod,
                'pending_count' => count($pendingIds),
                'pending_activities' => $pending
            ];
        }

        return response()->json([
            'aplicadas_top' => $appliedWithNames,
            'pending_by_module' => $pendingByModule,
            'modules' => $modules
        ]);
    }
}