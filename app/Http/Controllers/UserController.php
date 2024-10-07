<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;


class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Obtener todos los usuarios",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuarios",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener usuarios",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al obtener los usuarios.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        // Verificar si el usuario autenticado es un administrador
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            return User::all();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los usuarios.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Crear un nuevo usuario",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", example="contraseña123"),
     *             @OA\Property(property="role", type="string", enum={"user", "admin"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para crear el usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autorizado para crear usuarios con rol admin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto: correo ya en uso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="El correo ya está en uso.")
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
     *         description="Error al crear el usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al crear el usuario.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Verificar si el usuario está autenticado
            if (Auth::check()) {
                // Si está autenticado, solo los administradores pueden crear usuarios con rol admin
                if (Auth::user()->role !== 'admin' && $request->role === 'admin') {
                    return response()->json(['message' => 'No autorizado para crear usuarios con rol admin.'], 403);
                }
            } else {
                // Si no está autenticado, solo puede crear usuarios con rol user
                if ($request->role !== 'user') {
                    return response()->json(['message' => 'No autorizado para crear usuarios con rol admin.'], 403);
                }
            }

            // Verificar si el email ya está en uso
            if (User::where('email', $request->email)->exists()) {
                return response()->json(['message' => 'El correo ya está en uso.'], 409); // Código de conflicto (409)
            }

            // Validación de los datos
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:user,admin', // Validar rol
            ]);

            // Crear el usuario
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'], // Asignar rol
            ]);

            return response()->json($user, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el usuario.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Obtener un usuario específico",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario no encontrado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener el usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al obtener el usuario.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return response()->json($user);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener el usuario.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Actualizar un usuario existente",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", example="contraseña123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario actualizado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario no encontrado.")
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
     *         description="Error al actualizar el usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al actualizar el usuario.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, User $user)
    {
        try {
            // Validación de los datos
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'email' => [
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id), // Excluir el usuario actual
                ],
                'password' => 'nullable|string|min:8',  // La contraseña es opcional y debe tener al menos 8 caracteres
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Actualizar los campos si están presentes en la solicitud
            $user->name = $request->name ?? $user->name;
            $user->email = $request->email ?? $user->email;

            // Si se proporciona una nueva contraseña, encriptarla
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            // Guardar los cambios
            $user->save();

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el usuario.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Eliminar un usuario existente",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Usuario eliminado correctamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario no encontrado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al eliminar el usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al eliminar el usuario.")
     *         )
     *     )
     * )
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el usuario.' . $e->getMessage()], 500);
        }
    }
}
