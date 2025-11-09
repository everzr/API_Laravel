<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EspecialistaController;
use App\Http\Controllers\AdosController;
use App\Http\Controllers\AdirController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\UserController;

Route::prefix('admin')->group(function () {
    // Rutas para usuarios (sin token)
    Route::get('usuarios', [AdminController::class, 'usuariosIndex']);
    Route::post('usuarios', [AdminController::class, 'usuariosStore']);
    Route::put('usuarios/{id}', [AdminController::class, 'usuariosUpdate']);
    Route::delete('usuarios/{id}', [AdminController::class, 'usuariosDestroy']);

    // Rutas para actividades
    Route::get('actividades', [AdminController::class, 'actividadesIndex']);
    Route::post('actividades', [AdminController::class, 'actividadesStore']);
    Route::put('actividades/{id}', [AdminController::class, 'actividadesUpdate']);
    Route::delete('actividades/{id}', [AdminController::class, 'actividadesDestroy']);

    // Rutas para áreas (sin token)
    Route::get('areas', [AdminController::class, 'areasIndex']);
    Route::post('areas', [AdminController::class, 'areasStore']);
    Route::put('areas/{id}', [AdminController::class, 'areasUpdate']);
    Route::delete('areas/{id}', [AdminController::class, 'areasDestroy']);

    // Rutas para especialistas (sin token)
    Route::get('especialistas', [AdminController::class, 'especialistasIndex']);
    Route::post('especialistas', [AdminController::class, 'especialistasStore']);
    Route::put('especialistas/{id}', [AdminController::class, 'especialistasUpdate']);
    Route::delete('especialistas/{id}', [AdminController::class, 'especialistasDestroy']);

    // Rutas para pacientes (sin token)
    Route::get('pacientes', [AdminController::class, 'pacientesIndex']);
    Route::post('pacientes', [AdminController::class, 'pacientesStore']);
    Route::put('pacientes/{id}', [AdminController::class, 'pacientesUpdate']);
    Route::delete('pacientes/{id}', [AdminController::class, 'pacientesDestroy']);

    // Rutas para preguntas (sin token)
    Route::get('preguntas', [AdminController::class, 'preguntasIndex']);
    Route::post('preguntas', [AdminController::class, 'preguntasStore']);
    Route::put('preguntas/{id}', [AdminController::class, 'preguntasUpdate']);
    Route::delete('preguntas/{id}', [AdminController::class, 'preguntasDestroy']);

    // Rutas para tests adir (sin token)
    Route::get('tests-adir', [AdminController::class, 'testsAdirIndex']);
    Route::post('tests-adir', [AdminController::class, 'testsAdirStore']);
    Route::put('tests-adir/{id}', [AdminController::class, 'testsAdirUpdate']);
    Route::delete('tests-adir/{id}', [AdminController::class, 'testsAdirDestroy']);

    // Rutas para tests ados (sin token)
    Route::get('tests-ados', [AdminController::class, 'testsAdosIndex']);
    Route::post('tests-ados', [AdminController::class, 'testsAdosStore']);
    Route::put('tests-ados/{id}', [AdminController::class, 'testsAdosUpdate']);
    Route::delete('tests-ados/{id}', [AdminController::class, 'testsAdosDestroy']);
});


/*
  Rutas del módulo especialista / ADOS.
  NOTA: las URIs deben coincidir exactamente con las que usa el front.
  Estas rutas no aplican middleware de autenticación (el front puede enviar token pero no es obligatorio).
*/

Route::get('especialistas/buscar-espe/{id_usuario}', [EspecialistaController::class, 'buscarEspe']); //sirve

// Nuevas rutas de especialista (sin token)
Route::post('especialistas/aceptar-consentimiento', [EspecialistaController::class, 'aceptarConsentimiento']);
Route::get('especialistas/reportes/pacientes-con-tests', [EspecialistaController::class, 'pacientesConTests']);

// Rutas para el módulo ADOS (precisa exactamente estas URIs usadas por el front)
Route::get('ados/actividades/{modulo}', [AdosController::class, 'actividadesPorModulo']); //Sirve
Route::get('ados/paciente/{id_paciente}', [AdosController::class, 'paciente']);
Route::get('ados/actividades-realizadas/{id_ados}', [AdosController::class, 'actividadesRealizadas']);
Route::post('ados/crear', [AdosController::class, 'crearTest']);
Route::put('ados/pausar/{id_ados}', [AdosController::class, 'pausarTest']);
Route::post('ados/actividad-realizada', [AdosController::class, 'guardarActividadRealizada']);
Route::get('ados/codificacion/{id}', [AdosController::class, 'codificacion']);
Route::get('ados/puntuaciones-codificacion/{id}', [AdosController::class, 'puntuacionesCodificacion']);
Route::post('ados/responder-codificacion', [AdosController::class, 'responderCodificacion']);
Route::get('ados/algoritmo/{id}', [AdosController::class, 'algoritmo']);

// Rutas ADIR (sin token)
Route::prefix('adir')->group(function () {
    Route::get('listar/{id_paciente}', [AdirController::class, 'listarTestsPorPaciente']);
    Route::get('resumen/{id_adir}', [AdirController::class, 'obtenerResumenEvaluacion']);
    Route::put('diagnostico/{id_adir}', [AdirController::class, 'guardarDiagnostico']);
    Route::get('resumen-ultimo/{id_paciente}', [AdirController::class, 'resumenUltimoTestPorPaciente']);
    Route::get('listar-con-diagnostico/{id_paciente}', [AdirController::class, 'listarTestsConDiagnosticoPorPaciente']);
    Route::get('pdf/{id_adir}', [AdirController::class, 'generarPdfAdir']); // requiere dompdf para funcionar
    Route::get('preguntas', [AdirController::class, 'obtenerPreguntasAdir']);
    Route::post('crear-test', [AdirController::class, 'crearTestAdir']);
    Route::get('preguntas-con-respuestas/{id_adir}', [AdirController::class, 'obtenerPreguntasConRespuestas']);
    Route::get('codigos-por-pregunta', [AdirController::class, 'obtenerCodigosPorPregunta']);
    Route::post('guardar-respuesta', [AdirController::class, 'guardarRespuestaAdir']);
    Route::get('id-paciente/{id_adir}', [AdirController::class, 'obtenerIdPacientePorAdir']);
    Route::put('determinar-tipo-sujeto/{id_adir}', [AdirController::class, 'determinarYActualizarTipoSujeto']);
    Route::get('fecha-entrevista/{id_adir}', [AdirController::class, 'obtenerFechaEntrevistaPorAdir']);
    Route::put('guardar-diagnostico-final/{id_adir}', [AdirController::class, 'guardarDiagnosticoFinal']);
    Route::get('resumen-paciente/{id_adir}', [AdirController::class, 'obtenerResumenPacienteAdir']);
});

// Rutas ADOS (añadidas todas las del controlador Node, sin token)
Route::prefix('ados')->group(function () {
    Route::get('pacientes', [AdosController::class, 'listarPacientesConAdos']);
    Route::get('tests/{id_paciente}', [AdosController::class, 'listarTestsAdosPorPaciente']);
    Route::get('actividades/{modulo}', [AdosController::class, 'actividadesPorModulo']);
    Route::post('crear', [AdosController::class, 'crearTest']);
    Route::post('actividad-realizada', [AdosController::class, 'guardarActividadRealizada']);
    Route::put('pausar/{id_ados}', [AdosController::class, 'pausarTest']);
    Route::get('test-pausado', [AdosController::class, 'buscarTestPausado']);
    Route::get('actividades-realizadas/{id_ados}', [AdosController::class, 'actividadesRealizadas']);
    Route::post('responder-item', [AdosController::class, 'responderItem']);
    Route::get('codificaciones-algoritmo/{id_algoritmo}', [AdosController::class, 'codificacionesPorAlgoritmo']);
    Route::get('puntuaciones-codificacion/{id_codificacion}', [AdosController::class, 'puntuacionesPorCodificacion']);
    Route::post('responder-codificacion', [AdosController::class, 'responderCodificacion']);
    Route::get('paciente/{id_paciente}', [AdosController::class, 'obtenerPacientePorId']);
    Route::get('codificacion/{id_codificacion}', [AdosController::class, 'codificacionPorId']);
    Route::get('algoritmo/{id_algoritmo}', [AdosController::class, 'obtenerAlgoritmoPorId']);
    Route::get('respuestas-algoritmo/{id_ados}', [AdosController::class, 'respuestasAlgoritmo']);
    Route::get('test/{id_ados}', [AdosController::class, 'obtenerTestPorId']);
    Route::get('algoritmo-por-test/{id_ados}', [AdosController::class, 'obtenerAlgoritmoPorTest']);
    Route::get('puntuaciones-aplicadas/{id_ados}', [AdosController::class, 'puntuacionesAplicadasPorTest']);
    Route::put('clasificacion/{id_ados}', [AdosController::class, 'actualizarClasificacion']);
    Route::put('puntuacion-comparativa/{id_ados}', [AdosController::class, 'actualizarPuntuacionComparativa']);
    Route::put('diagnostico/{id_ados}', [AdosController::class, 'actualizarDiagnostico']);
    Route::get('actividades-por-test/{id_ados}', [AdosController::class, 'obtenerActividadesPorTest']);
    Route::get('grupo-codificacion/{id_codificacion}', [AdosController::class, 'obtenerGrupoPorCodificacion']);
    Route::get('reporte-modulo-t/{id_ados}', [AdosController::class, 'obtenerDatosReporteModuloT']);
    Route::get('reporte-modulo-1/{id_ados}', [AdosController::class, 'obtenerDatosReporteModulo1']);
    Route::get('reporte-modulo-2/{id_ados}', [AdosController::class, 'obtenerDatosReporteModulo2']);
    Route::get('reporte-modulo-3/{id_ados}', [AdosController::class, 'obtenerDatosReporteModulo3']);
    Route::get('reporte-modulo-4/{id_ados}', [AdosController::class, 'obtenerDatosReporteModulo4']);
    Route::get('validar-filtros/{id_paciente}', [AdosController::class, 'validarFiltrosPaciente']);
});

// Rutas Paciente (sin token)
Route::prefix('paciente')->group(function () {
    Route::get('buscar-paciente/{id_usuario}', [PacienteController::class, 'buscarPacientePorUsuario']);
    Route::post('aceptar-consentimiento', [PacienteController::class, 'aceptarConsentimiento']);
    Route::post('guardar-dsm5', [PacienteController::class, 'guardarDsm5']);
    Route::get('validar-terminos/{id_usuario}', [PacienteController::class, 'validarTerminos']);
    Route::put('desactivar/{id_usuario}', [PacienteController::class, 'desactivarCuenta']);
    Route::get('resultados/{id_paciente}', [PacienteController::class, 'listarResultadosPaciente']);
});

// Rutas User (paridad con userRoutes.js)
Route::prefix('users')->group(function () {
    Route::post('login', [UserController::class, 'login']);
    Route::post('registrar', [UserController::class, 'registrar']);
    Route::post('cambiar-contrasena', [UserController::class, 'cambiarContrasena']);
    Route::get('pacientes', [UserController::class, 'listarPacientes']);
    Route::put('cambiar-password', [UserController::class, 'cambiarPasswordConActual']);
    Route::post('recuperar-contrasena', [UserController::class, 'recuperarContrasena']);
});
