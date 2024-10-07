<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Espacio;

class EspacioController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/espacios",
     *     tags={"Espacios"},
     *     summary="Obtener todos los espacios",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de espacios",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Espacio"))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener los espacios",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Error al obtener los espacios."))
     *     )
     * )
     */
    public function index()
    {
        try {
            return Espacio::all();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los espacios.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/espacios",
     *     tags={"Espacios"},
     *     summary="Crear un nuevo espacio",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "descripcion", "capacidad"},
     *             @OA\Property(property="nombre", type="string", example="Sala de Conferencias"),
     *             @OA\Property(property="descripcion", type="string", example="Una sala amplia para conferencias."),
     *             @OA\Property(property="capacidad", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Espacio creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Espacio")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al crear el espacio",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Error al crear el espacio."))
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required',
                'descripcion' => 'required',
                'capacidad' => 'required|integer',
            ]);

            $espacio = Espacio::create($validated);
            return response()->json($espacio, 201);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al crear el espacio.',
                    'error' => $e->getMessage(), // Muestra más detalles del error
                ],
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/espacios/{id}",
     *     tags={"Espacios"},
     *     summary="Obtener un espacio por ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del espacio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del espacio",
     *         @OA\JsonContent(ref="#/components/schemas/Espacio")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Espacio no encontrado",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Espacio no encontrado."))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener el espacio",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Error al obtener el espacio."))
     *     )
     * )
     */
    public function show(Espacio $espacio)
    {
        try {
            return $espacio;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener el espacio.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/espacios/{id}",
     *     tags={"Espacios"},
     *     summary="Actualizar un espacio por ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del espacio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string", example="Sala de Reuniones"),
     *             @OA\Property(property="descripcion", type="string", example="Sala equipada para reuniones."),
     *             @OA\Property(property="capacidad", type="integer", example=30)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Espacio actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Espacio")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Espacio no encontrado",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Espacio no encontrado."))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al actualizar el espacio",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Error al actualizar el espacio."))
     *     )
     * )
     */
    public function update(Request $request, Espacio $espacio)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'sometimes|required',
                'descripcion' => 'sometimes|required',
                'capacidad' => 'sometimes|required|integer',
            ]);

            $espacio->update($validated);
            return response()->json($espacio, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el espacio.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/espacios/{id}",
     *     tags={"Espacios"},
     *     summary="Eliminar un espacio por ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del espacio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Espacio eliminado correctamente",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Espacio eliminado correctamente."))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Espacio no encontrado",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Espacio no encontrado."))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al eliminar el espacio",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Error al eliminar el espacio."))
     *     )
     * )
     */
    public function destroy(Espacio $espacio)
    {
        try {
            $espacio->delete();
            return response()->json(['message' => 'Espacio eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el espacio.' . $e->getMessage()], 500);
        }
    }
}
