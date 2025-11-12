<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Actividad;
use App\Models\Area;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\PreguntaAdi;
use App\Models\TestAdir;
use App\Models\TestAdos; // <-- agregado
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // ------------------ USUARIOS (Administrador) ------------------
    // Listar todos los usuarios
    public function usuariosIndex(Request $request)
    {
        $usuarios = Usuario::all();
        return response()->json($usuarios);
    }

    // Crear usuario
    public function usuariosStore(Request $request)
    {
        $data = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'required|email|unique:usuario,correo', // <--- usuario (singular)
            'contrasena' => 'required|string|min:6',
            'privilegio' => 'nullable|integer',
            'imagen' => 'nullable|string',
            'estado' => 'nullable|integer',
        ]);

        $data['contrasena'] = Hash::make($data['contrasena']);

        $usuario = Usuario::create($data);

        return response()->json($usuario, 201);
    }

    // Actualizar usuario
    public function usuariosUpdate(Request $request, $id)
    {
        $usuario = Usuario::where('id_usuario', $id)->first();
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $data = $request->validate([
            'nombres' => 'sometimes|required|string|max:255',
            'apellidos' => 'sometimes|nullable|string|max:255',
            'direccion' => 'sometimes|nullable|string|max:500',
            'telefono' => 'sometimes|nullable|string|max:50',
            'correo' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('usuario', 'correo')->ignore($usuario->id_usuario, 'id_usuario') // <--- usuario (singular)
            ],
            'contrasena' => 'sometimes|nullable|string|min:6',
            'privilegio' => 'sometimes|nullable|integer',
            'imagen' => 'sometimes|nullable|string',
            'estado' => 'sometimes|nullable|integer',
        ]);

        if (!empty($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        } else {
            unset($data['contrasena']);
        }

        $usuario->update($data);

        return response()->json($usuario);
    }

    // Eliminar usuario
    public function usuariosDestroy($id)
    {
        $usuario = Usuario::where('id_usuario', $id)->first();
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }

    // ------------------ ACTIVIDADES (Administrador) ------------------
    // Listar actividades
    public function actividadesIndex(Request $request)
    {
        $actividades = Actividad::all();
        return response()->json($actividades);
    }

    // Crear actividad
    public function actividadesStore(Request $request)
    {
        $data = $request->validate([
            'id_ados' => 'nullable|integer',
            'nombre_actividad' => 'required|string|max:255',
            'observacion' => 'nullable|string',
            'puntuacion' => 'nullable|numeric',
        ]);

        $actividad = Actividad::create($data);

        return response()->json($actividad, 201);
    }

    // Actualizar actividad
    public function actividadesUpdate(Request $request, $id)
    {
        $actividad = Actividad::where('id_actividad', $id)->first();
        if (!$actividad) {
            return response()->json(['message' => 'Actividad no encontrada'], 404);
        }

        $data = $request->validate([
            'id_ados' => 'sometimes|nullable|integer',
            'nombre_actividad' => 'sometimes|required|string|max:255',
            'observacion' => 'sometimes|nullable|string',
            'puntuacion' => 'sometimes|nullable|numeric',
        ]);

        $actividad->update($data);

        return response()->json($actividad);
    }

    // Eliminar actividad
    public function actividadesDestroy($id)
    {
        $actividad = Actividad::where('id_actividad', $id)->first();
        if (!$actividad) {
            return response()->json(['message' => 'Actividad no encontrada'], 404);
        }

        $actividad->delete();

        return response()->json(['message' => 'Actividad eliminada']);
    }

    // ------------------ AREAS (Administrador) ------------------
    // Listar áreas
    public function areasIndex(Request $request)
    {
        $areas = Area::all();
        return response()->json($areas);
    }

    // Crear área
    public function areasStore(Request $request)
    {
        $data = $request->validate([
            'area' => 'required|string|max:255',
        ]);

        $area = Area::create($data);

        return response()->json($area, 201);
    }

    // Actualizar área
    public function areasUpdate(Request $request, $id)
    {
        $area = Area::where('id_area', $id)->first();
        if (!$area) {
            return response()->json(['message' => 'Área no encontrada'], 404);
        }

        $data = $request->validate([
            'area' => 'sometimes|required|string|max:255',
        ]);

        $area->update($data);

        return response()->json($area);
    }

    // Eliminar área
    public function areasDestroy($id)
    {
        $area = Area::where('id_area', $id)->first();
        if (!$area) {
            return response()->json(['message' => 'Área no encontrada'], 404);
        }

        $area->delete();

        return response()->json(['message' => 'Área eliminada']);
    }

    // ------------------ ESPECIALISTAS (Administrador) ------------------
    // Listar especialistas
    public function especialistasIndex(Request $request)
    {
        $especialistas = Especialista::all();
        return response()->json($especialistas);
    }

    // Crear especialista
    public function especialistasStore(Request $request)
    {
        $data = $request->validate([
            'id_usuario' => 'required|integer|exists:usuario,id_usuario', // <--- usuario
            'especialidad' => 'required|string|max:255',
        ]);

        $especialista = Especialista::create($data);

        return response()->json($especialista, 201);
    }

    // Actualizar especialista
    public function especialistasUpdate(Request $request, $id)
    {
        $especialista = Especialista::where('id_especialista', $id)->first();
        if (!$especialista) {
            return response()->json(['message' => 'Especialista no encontrado'], 404);
        }

        $data = $request->validate([
            'id_usuario' => 'sometimes|required|integer|exists:usuario,id_usuario', // <--- usuario
            'especialidad' => 'sometimes|required|string|max:255',
        ]);

        $especialista->update($data);

        return response()->json($especialista);
    }

    // Eliminar especialista
    public function especialistasDestroy($id)
    {
        $especialista = Especialista::where('id_especialista', $id)->first();
        if (!$especialista) {
            return response()->json(['message' => 'Especialista no encontrado'], 404);
        }

        $especialista->delete();

        return response()->json(['message' => 'Especialista eliminado']);
    }

    // ------------------ PACIENTES (Administrador) ------------------
    // Listar pacientes
    public function pacientesIndex(Request $request)
    {
        $pacientes = Paciente::all();
        return response()->json($pacientes);
    }

    // Crear paciente
    public function pacientesStore(Request $request)
    {
        $data = $request->validate([
            'id_usuario' => 'required|integer|exists:usuario,id_usuario', // <--- usuario
            'fecha_nacimiento' => 'required|date',
            'sexo' => 'required|in:M,F',
        ]);

        $paciente = Paciente::create($data);

        return response()->json($paciente, 201);
    }

    // Actualizar paciente
    public function pacientesUpdate(Request $request, $id)
    {
        $paciente = Paciente::where('id_paciente', $id)->first();
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $data = $request->validate([
            'id_usuario' => 'sometimes|required|integer|exists:usuario,id_usuario', // <--- usuario
            'fecha_nacimiento' => 'sometimes|required|date',
            'sexo' => 'sometimes|required|in:M,F',
        ]);

        $paciente->update($data);

        return response()->json($paciente);
    }

    // Eliminar paciente
    public function pacientesDestroy($id)
    {
        $paciente = Paciente::where('id_paciente', $id)->first();
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $paciente->delete();

        return response()->json(['message' => 'Paciente eliminado']);
    }

    // ------------------ PREGUNTAS (Administrador) ------------------
    // Listar preguntas
    public function preguntasIndex(Request $request)
    {
        $preguntas = PreguntaAdi::all();
        return response()->json($preguntas);
    }

    // Crear pregunta
    public function preguntasStore(Request $request)
    {
        $data = $request->validate([
            'pregunta' => 'required|string|max:1000',
            'id_area' => 'required|integer|exists:area,id_area', // <--- area
        ]);

        $pregunta = PreguntaAdi::create($data);

        return response()->json($pregunta, 201);
    }

    // Actualizar pregunta
    public function preguntasUpdate(Request $request, $id)
    {
        $pregunta = PreguntaAdi::where('id_pregunta', $id)->first();
        if (!$pregunta) {
            return response()->json(['message' => 'Pregunta no encontrada'], 404);
        }

        $data = $request->validate([
            'pregunta' => 'sometimes|required|string|max:1000',
            'id_area' => 'sometimes|required|integer|exists:area,id_area', // <--- area
        ]);

        $pregunta->update($data);

        return response()->json($pregunta);
    }

    // Eliminar pregunta
    public function preguntasDestroy($id)
    {
        $pregunta = PreguntaAdi::where('id_pregunta', $id)->first();
        if (!$pregunta) {
            return response()->json(['message' => 'Pregunta no encontrada'], 404);
        }

        $pregunta->delete();

        return response()->json(['message' => 'Pregunta eliminada']);
    }

    // ------------------ TESTS ADIR (Administrador) ------------------
    // Listar tests
    public function testsAdirIndex(Request $request)
    {
        $tests = TestAdir::all();
        return response()->json($tests);
    }

    // Crear test (opcional, el front no lo usa pero lo dejo)
    public function testsAdirStore(Request $request)
    {
        $data = $request->validate([
            'id_paciente' => 'required|integer|exists:paciente,id_paciente',       // <--- paciente
            'id_especialista' => 'required|integer|exists:especialista,id_especialista', // <--- especialista
            'fecha' => 'required|date',
            'diagnostico' => 'nullable|string',
        ]);

        $test = TestAdir::create($data);

        return response()->json($test, 201);
    }

    // Actualizar test
    public function testsAdirUpdate(Request $request, $id)
    {
        $test = TestAdir::where('id_adir', $id)->first();
        if (!$test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        $data = $request->validate([
            'id_paciente' => 'sometimes|required|integer|exists:paciente,id_paciente',        // <--- paciente
            'id_especialista' => 'sometimes|required|integer|exists:especialista,id_especialista', // <--- especialista
            'fecha' => 'sometimes|required|date',
            'diagnostico' => 'sometimes|nullable|string',
        ]);

        $test->update($data);

        return response()->json($test);
    }

    // Eliminar test
    public function testsAdirDestroy($id)
    {
        $test = TestAdir::where('id_adir', $id)->first();
        if (!$test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        $test->delete();

        return response()->json(['message' => 'Test eliminado']);
    }

    // ------------------ TESTS ADOS (Administrador) ------------------
    // Listar tests ADOS-2
    public function testsAdosIndex(Request $request)
    {
        $tests = TestAdos::all();
        return response()->json($tests);
    }

    // Crear test ADOS-2 (opcional)
    public function testsAdosStore(Request $request)
    {
        $data = $request->validate([
            'id_paciente' => 'required|integer|exists:paciente,id_paciente',       // <--- paciente
            'fecha' => 'required|date',
            'modulo' => 'required|string|max:255',
            'id_especialista' => 'required|integer|exists:especialista,id_especialista', // <--- especialista
            'diagnostico' => 'nullable|string',
            'total_punto' => 'nullable|numeric',
        ]);

        $test = TestAdos::create($data);

        return response()->json($test, 201);
    }

    // Actualizar test ADOS-2
    public function testsAdosUpdate(Request $request, $id)
    {
        $test = TestAdos::where('id_ados', $id)->first();
        if (!$test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        $data = $request->validate([
            'id_paciente' => 'sometimes|required|integer|exists:paciente,id_paciente',        // <--- paciente
            'fecha' => 'sometimes|required|date',
            'modulo' => 'sometimes|required|string|max:255',
            'id_especialista' => 'sometimes|required|integer|exists:especialista,id_especialista', // <--- especialista
            'diagnostico' => 'sometimes|nullable|string',
            'total_punto' => 'sometimes|nullable|numeric',
        ]);

        $test->update($data);

        return response()->json($test);
    }

    // Eliminar test ADOS-2
    public function testsAdosDestroy($id)
    {
        $test = TestAdos::where('id_ados', $id)->first();
        if (!$test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        $test->delete();

        return response()->json(['message' => 'Test eliminado']);
    }

    // ------------------ RESPONSABLES LEGALES (Administrador) ------------------
    public function responsablesIndex(Request $request)
    {
        $id_paciente = $request->query('id_paciente');
        if ($id_paciente) {
            $rows = DB::select("SELECT * FROM responsable_legal WHERE id_paciente = ? ORDER BY id_responsable_legal", [$id_paciente]);
        } else {
            $rows = DB::select("SELECT * FROM responsable_legal ORDER BY id_responsable_legal DESC");
        }
        return response()->json($rows);
    }

    public function responsablesStore(Request $request)
    {
        $data = $request->validate([
            'id_paciente' => 'required|integer|exists:paciente,id_paciente',
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'num_identificacion' => 'required|string|max:50|unique:responsable_legal,num_identificacion',
            'parentesco' => 'required|string|max:50',
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:200',
            'correo' => 'nullable|email|max:50|unique:responsable_legal,correo',
        ]);

        $id = DB::table('responsable_legal')->insertGetId($data);

        return response()->json(['id_responsable_legal' => $id], 201);
    }

    public function responsablesUpdate(Request $request, $id)
    {
        $exists = DB::selectOne("SELECT id_responsable_legal FROM responsable_legal WHERE id_responsable_legal = ?", [$id]);
        if (!$exists)
            return response()->json(['message' => 'Responsable no encontrado'], 404);

        $data = $request->validate([
            'id_paciente' => 'sometimes|required|integer|exists:paciente,id_paciente',
            'nombre' => 'sometimes|required|string|max:50',
            'apellido' => 'sometimes|required|string|max:50',
            'num_identificacion' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('responsable_legal', 'num_identificacion')->ignore($id, 'id_responsable_legal')
            ],
            'parentesco' => 'sometimes|required|string|max:50',
            'telefono' => 'sometimes|nullable|string|max:30',
            'direccion' => 'sometimes|nullable|string|max:200',
            'correo' => [
                'sometimes',
                'nullable',
                'email',
                'max:50',
                Rule::unique('responsable_legal', 'correo')->ignore($id, 'id_responsable_legal')
            ],
        ]);

        DB::table('responsable_legal')->where('id_responsable_legal', $id)->update($data);

        return response()->json(['message' => 'Responsable actualizado']);
    }

    public function responsablesDestroy($id)
    {
        $deleted = DB::table('responsable_legal')->where('id_responsable_legal', $id)->delete();
        if (!$deleted)
            return response()->json(['message' => 'Responsable no encontrado'], 404);
        return response()->json(['message' => 'Responsable eliminado']);
    }

    // GET /api/admin/pacientes-lista  (Spring: getPacientesConNombres)
    public function pacientesLista()
    {
        try {
            $rows = DB::select("
                SELECT
                    p.id_paciente,
                    u.id_usuario,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    u.direccion,
                    u.estado,
                    p.fecha_nacimiento,
                    p.sexo
                FROM paciente p
                INNER JOIN usuario u ON p.id_usuario = u.id_usuario
                ORDER BY u.nombres, u.apellidos
            ");
            return response()->json($rows);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener pacientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/admin/pacientes/{id_paciente}/detalle (Spring: getPacienteDetalle)
    public function pacienteDetalle($id_paciente)
    {
        try {
            $pacRows = DB::select("
                SELECT
                    p.id_paciente,
                    p.fecha_nacimiento,
                    p.sexo,
                    u.id_usuario,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    u.direccion,
                    u.imagen,
                    u.estado
                FROM paciente p
                JOIN usuario u ON p.id_usuario = u.id_usuario
                WHERE p.id_paciente = ?
                LIMIT 1
            ", [$id_paciente]);

            if (empty($pacRows)) {
                return response()->json(['message' => 'Paciente no encontrado'], 404);
            }
            $row = $pacRows[0];

            $responsables = DB::select("
                SELECT
                    id_responsable_legal,
                    nombre,
                    apellido,
                    num_identificacion,
                    parentesco,
                    telefono,
                    direccion,
                    correo
                FROM responsable_legal
                WHERE id_paciente = ?
                ORDER BY id_responsable_legal
            ", [$id_paciente]);

            return response()->json([
                'paciente' => [
                    'id_paciente' => $row->id_paciente,
                    'fecha_nacimiento' => $row->fecha_nacimiento,
                    'sexo' => $row->sexo,
                ],
                'usuario' => [
                    'id_usuario' => $row->id_usuario,
                    'nombres' => $row->nombres,
                    'apellidos' => $row->apellidos,
                    'correo' => $row->correo,
                    'telefono' => $row->telefono,
                    'direccion' => $row->direccion,
                    'imagen' => $row->imagen,
                    'estado' => $row->estado,
                ],
                'responsables_legales' => $responsables ?? [],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
