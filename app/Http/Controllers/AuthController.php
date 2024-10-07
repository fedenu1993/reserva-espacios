<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Iniciar sesión de usuario",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="usuario@example.com"),
     *             @OA\Property(property="password", type="string", example="contraseña123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inicio de sesión exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="token_generado_aqui")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales incorrectas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Credenciales incorrectas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al iniciar sesión",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al iniciar sesión.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            // Validamos las credenciales recibidas en el request
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Intentamos autenticar al usuario
            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Credenciales incorrectas'], 401);
            }

            // Obtenemos el usuario autenticado
            $user = Auth::user();

            // Creamos un token con Sanctum
            $token = $user->createToken('API Token')->plainTextToken;

            // Retornamos el token
            return response()->json(['token' => $token]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al iniciar sesión.'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Cerrar sesión de usuario",
     *     @OA\Response(
     *         response=200,
     *         description="Cierre de sesión exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Sesión cerrada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al cerrar sesión",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al cerrar sesión.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            // Revocar todos los tokens del usuario
            $user = $request->user();

            // Verificar si el usuario está autenticado
            if (!$user) {
                return response()->json(['message' => 'No autorizado'], 401);
            }

            $user->tokens()->delete();

            return response()->json(['message' => 'Sesión cerrada correctamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cerrar sesión.'], 500);
        }
    }
}
