<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EspecialistaController;
use App\Http\Controllers\AdosController;

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