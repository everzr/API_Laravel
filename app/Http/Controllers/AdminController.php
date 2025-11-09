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
            'nombres'    => 'required|string|max:255',
            'apellidos'  => 'nullable|string|max:255',
            'direccion'  => 'nullable|string|max:500',
            'telefono'   => 'nullable|string|max:50',
            'correo'     => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|string|min:6',
            'privilegio' => 'nullable|integer',
            'imagen'     => 'nullable|string',
            'estado'     => 'nullable|integer',
        ]);

        $data['contrasena'] = Hash::make($data['contrasena']);

        $usuario = Usuario::create($data);

        return response()->json($usuario, 201);
    }

    // Actualizar usuario
    public function usuariosUpdate(Request $request, $id)
    {
        $usuario = Usuario::where('id_usuario', $id)->first();
        if (! $usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $data = $request->validate([
            'nombres'    => 'sometimes|required|string|max:255',
            'apellidos'  => 'sometimes|nullable|string|max:255',
            'direccion'  => 'sometimes|nullable|string|max:500',
            'telefono'   => 'sometimes|nullable|string|max:50',
            'correo'     => ['sometimes','required','email', Rule::unique('usuarios','correo')->ignore($usuario->id_usuario ?? $usuario->id, 'id_usuario')],
            'contrasena' => 'sometimes|nullable|string|min:6',
            'privilegio' => 'sometimes|nullable|integer',
            'imagen'     => 'sometimes|nullable|string',
            'estado'     => 'sometimes|nullable|integer',
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
        if (! $usuario) {
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
            'id_ados'            => 'nullable|integer',
            'nombre_actividad'   => 'required|string|max:255',
            'observacion'        => 'nullable|string',
            'puntuacion'         => 'nullable|numeric',
        ]);

        $actividad = Actividad::create($data);

        return response()->json($actividad, 201);
    }

    // Actualizar actividad
    public function actividadesUpdate(Request $request, $id)
    {
        $actividad = Actividad::where('id_actividad', $id)->first();
        if (! $actividad) {
            return response()->json(['message' => 'Actividad no encontrada'], 404);
        }

        $data = $request->validate([
            'id_ados'            => 'sometimes|nullable|integer',
            'nombre_actividad'   => 'sometimes|required|string|max:255',
            'observacion'        => 'sometimes|nullable|string',
            'puntuacion'         => 'sometimes|nullable|numeric',
        ]);

        $actividad->update($data);

        return response()->json($actividad);
    }

    // Eliminar actividad
    public function actividadesDestroy($id)
    {
        $actividad = Actividad::where('id_actividad', $id)->first();
        if (! $actividad) {
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
        if (! $area) {
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
        if (! $area) {
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
            'id_usuario'  => 'required|integer|exists:usuarios,id_usuario',
            'especialidad'=> 'required|string|max:255',
        ]);

        $especialista = Especialista::create($data);

        return response()->json($especialista, 201);
    }

    // Actualizar especialista
    public function especialistasUpdate(Request $request, $id)
    {
        $especialista = Especialista::where('id_especialista', $id)->first();
        if (! $especialista) {
            return response()->json(['message' => 'Especialista no encontrado'], 404);
        }

        $data = $request->validate([
            'id_usuario'  => 'sometimes|required|integer|exists:usuarios,id_usuario',
            'especialidad'=> 'sometimes|required|string|max:255',
        ]);

        $especialista->update($data);

        return response()->json($especialista);
    }

    // Eliminar especialista
    public function especialistasDestroy($id)
    {
        $especialista = Especialista::where('id_especialista', $id)->first();
        if (! $especialista) {
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
            'id_usuario'       => 'required|integer|exists:usuarios,id_usuario',
            'fecha_nacimiento' => 'required|date',
            'sexo'             => 'required|in:M,F',
        ]);

        $paciente = Paciente::create($data);

        return response()->json($paciente, 201);
    }

    // Actualizar paciente
    public function pacientesUpdate(Request $request, $id)
    {
        $paciente = Paciente::where('id_paciente', $id)->first();
        if (! $paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $data = $request->validate([
            'id_usuario'       => 'sometimes|required|integer|exists:usuarios,id_usuario',
            'fecha_nacimiento' => 'sometimes|required|date',
            'sexo'             => 'sometimes|required|in:M,F',
        ]);

        $paciente->update($data);

        return response()->json($paciente);
    }

    // Eliminar paciente
    public function pacientesDestroy($id)
    {
        $paciente = Paciente::where('id_paciente', $id)->first();
        if (! $paciente) {
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
            'id_area'  => 'required|integer|exists:areas,id_area',
        ]);

        $pregunta = PreguntaAdi::create($data);

        return response()->json($pregunta, 201);
    }

    // Actualizar pregunta
    public function preguntasUpdate(Request $request, $id)
    {
        $pregunta = PreguntaAdi::where('id_pregunta', $id)->first();
        if (! $pregunta) {
            return response()->json(['message' => 'Pregunta no encontrada'], 404);
        }

        $data = $request->validate([
            'pregunta' => 'sometimes|required|string|max:1000',
            'id_area'  => 'sometimes|required|integer|exists:areas,id_area',
        ]);

        $pregunta->update($data);

        return response()->json($pregunta);
    }

    // Eliminar pregunta
    public function preguntasDestroy($id)
    {
        $pregunta = PreguntaAdi::where('id_pregunta', $id)->first();
        if (! $pregunta) {
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
            'id_paciente'    => 'required|integer|exists:pacientes,id_paciente',
            'id_especialista'=> 'required|integer|exists:especialistas,id_especialista',
            'fecha'          => 'required|date',
            'diagnostico'    => 'nullable|string',
        ]);

        $test = TestAdir::create($data);

        return response()->json($test, 201);
    }

    // Actualizar test
    public function testsAdirUpdate(Request $request, $id)
    {
        $test = TestAdir::where('id_adir', $id)->first();
        if (! $test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        $data = $request->validate([
            'id_paciente'    => 'sometimes|required|integer|exists:pacientes,id_paciente',
            'id_especialista'=> 'sometimes|required|integer|exists:especialistas,id_especialista',
            'fecha'          => 'sometimes|required|date',
            'diagnostico'    => 'sometimes|nullable|string',
        ]);

        $test->update($data);

        return response()->json($test);
    }

    // Eliminar test
    public function testsAdirDestroy($id)
    {
        $test = TestAdir::where('id_adir', $id)->first();
        if (! $test) {
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
            'id_paciente'    => 'required|integer|exists:pacientes,id_paciente',
            'fecha'          => 'required|date',
            'modulo'         => 'required|string|max:255',
            'id_especialista'=> 'required|integer|exists:especialistas,id_especialista',
            'diagnostico'    => 'nullable|string',
            'total_punto'    => 'nullable|numeric',
        ]);

        $test = TestAdos::create($data);

        return response()->json($test, 201);
    }

    // Actualizar test ADOS-2
    public function testsAdosUpdate(Request $request, $id)
    {
        $test = TestAdos::where('id_ados', $id)->first();
        if (! $test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        $data = $request->validate([
            'id_paciente'    => 'sometimes|required|integer|exists:pacientes,id_paciente',
            'fecha'          => 'sometimes|required|date',
            'modulo'         => 'sometimes|required|string|max:255',
            'id_especialista'=> 'sometimes|required|integer|exists:especialistas,id_especialista',
            'diagnostico'    => 'sometimes|nullable|string',
            'total_punto'    => 'sometimes|nullable|numeric',
        ]);

        $test->update($data);

        return response()->json($test);
    }

    // Eliminar test ADOS-2
    public function testsAdosDestroy($id)
    {
        $test = TestAdos::where('id_ados', $id)->first();
        if (! $test) {
            return response()->json(['message' => 'Test no encontrado'], 404);
        }

        $test->delete();

        return response()->json(['message' => 'Test eliminado']);
    }
}