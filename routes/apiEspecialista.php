<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EspecialistaController;
use App\Http\Controllers\AdosController;

/*
  Rutas del módulo especialista / ADOS.
  NOTA: las URIs deben coincidir exactamente con las que usa el front.
  Estas rutas no aplican middleware de autenticación (el front puede enviar token pero no es obligatorio).
*/

Route::get('especialistas/buscar-espe/{id_usuario}', [EspecialistaController::class, 'buscarEspe']);

// Rutas para el módulo ADOS (precisa exactamente estas URIs usadas por el front)
Route::get('ados/actividades/{modulo}', [AdosController::class, 'actividadesPorModulo']);
Route::get('ados/paciente/{id_paciente}', [AdosController::class, 'paciente']);
Route::get('ados/actividades-realizadas/{id_ados}', [AdosController::class, 'actividadesRealizadas']);
Route::post('ados/crear', [AdosController::class, 'crearTest']);
Route::put('ados/pausar/{id_ados}', [AdosController::class, 'pausarTest']);
Route::post('ados/actividad-realizada', [AdosController::class, 'guardarActividadRealizada']);
Route::get('ados/codificacion/{id}', [AdosController::class, 'codificacion']);
Route::get('ados/puntuaciones-codificacion/{id}', [AdosController::class, 'puntuacionesCodificacion']);
Route::post('ados/responder-codificacion', [AdosController::class, 'responderCodificacion']);
Route::get('ados/algoritmo/{id}', [AdosController::class, 'algoritmo']);

