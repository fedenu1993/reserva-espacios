<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EspacioController;
use App\Http\Controllers\ReservaController;
use Illuminate\Support\Facades\Auth;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas para sessiones
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/login/validate-token', function (Request $request) {
  // El usuario ya está autenticado gracias al middleware
  $user = $request->user();

  return response()->json(['valid' => true, 'user' => $user]);
});
Route::middleware('auth:sanctum')->get('/login/user', function (Request $request) {
  // El usuario ya está autenticado gracias al middleware
  $user = $request->user();
  return response()->json(['user' => $user]);
});

// Users
Route::post('users', [UserController::class, 'store']); // Crear (sin autenticación)
Route::middleware('auth:sanctum')->group(function () {
  Route::get('users/{user}', [UserController::class, 'show']); // Ver
  Route::get('users/', [UserController::class, 'index']); // Ver todos
  Route::put('users/{user}', [UserController::class, 'update']); // Editar
  Route::delete('users/{user}', [UserController::class, 'destroy']); // Eliminar
});

// Rutas para ver espacios  
Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('espacios', [EspacioController::class, 'index']); // Listar todos los espacios
  Route::get('espacios/{espacio}', [EspacioController::class, 'show']); // Ver un espacio específico
});
// Rutas para agregar, editar y eliminar espacios (solo admin)
Route::middleware(['auth:sanctum', 'checkRole:admin'])->group(function () {
  Route::post('espacios', [EspacioController::class, 'store']); // Crear un nuevo espacio
  Route::put('espacios/{espacio}', [EspacioController::class, 'update']); // Actualizar un espacio
  Route::delete('espacios/{espacio}', [EspacioController::class, 'destroy']); // Eliminar un espacio
});

// Rutas para reservas
Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('reservas', [ReservaController::class, 'index']); // Listar todos los reservas
  Route::get('reservas/{reserva}', [ReservaController::class, 'show']); // Ver un reserva específico
  Route::post('reservas', [ReservaController::class, 'store']); // Crear un nuevo reserva
  Route::put('reservas/{reserva}', [ReservaController::class, 'update']); // Actualizar un reserva
  Route::delete('reservas/{reserva}', [ReservaController::class, 'destroy']); // Eliminar un reserva
});
