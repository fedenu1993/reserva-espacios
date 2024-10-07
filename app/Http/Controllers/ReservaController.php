<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reservas",
     *     tags={"Reservas"},
     *     summary="Obtener todas las reservas del usuario autenticado",
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *            type="array", @OA\Items(ref="#/components/schemas/Reserva")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener reservas"
     *     )
     * )
     */
    public function index()
    {
        try {
            // Obtener el ID del usuario autenticado
            $userId = Auth::id();

            // Obtener todas las reservas del usuario autenticado
            $reservas = Reserva::where('user_id', $userId)->get();

            return response()->json($reservas);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener reservas.' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Obtener una reserva por ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reserva",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/Reserva")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $reserva = Reserva::findOrFail($id);
            return response()->json($reserva);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Reserva no encontrada.'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/reservas",
     *     tags={"Reservas"},
     *     summary="Crear una nueva reserva",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="Request")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Reserva")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al crear la reserva"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Asegúrate de que el usuario esté autenticado
            if (!Auth::check()) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            $validatedData = $this->validateReserva($request);
            $validatedData['user_id'] = Auth::id(); // Asigna el ID del usuario autenticado

            // Crear la reserva
            $reserva = Reserva::create($validatedData);

            return response()->json($reserva, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear la reserva. ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Actualizar una reserva existente",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reserva",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="Request")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva actualizada",
     *         @OA\JsonContent(ref="#/components/schemas/Reserva")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al actualizar la reserva"
     *     )
     * )
     */
    public function update(Request $request, Reserva $reserva)
    {
        try {
            // Asegúrate de que el usuario solo pueda modificar sus propias reservas
            if ($reserva->user_id !== Auth::id()) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            $validatedData = $this->validateReserva($request, $reserva->id);

            // Actualizar la reserva
            $reserva->update($validatedData);

            return response()->json($reserva);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la reserva. ' . $e->getMessage()], 500);
        }
    }

    private function validateReserva(Request $request, $excludeId = null)
    {
        // Validar los datos de entrada
        $validatedData = $request->validate([
            'espacio_id' => 'required|exists:espacios,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        // Comprobar si hay reservas que se superpongan
        $overlappingReservations = Reserva::where('espacio_id', $validatedData['espacio_id'])
            ->where('fecha', $validatedData['fecha'])
            ->where(function ($query) use ($validatedData) {
                $query->whereBetween('hora_inicio', [$validatedData['hora_inicio'], $validatedData['hora_fin']])
                    ->orWhereBetween('hora_fin', [$validatedData['hora_inicio'], $validatedData['hora_fin']]);
            })
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId); // Excluir la reserva que se está actualizando
            })
            ->exists();

        // Si hay superposición, lanzar una excepción
        if ($overlappingReservations) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El espacio ya está reservado en este horario.',
            ]);
        }

        return $validatedData;
    }



    /**
     * @OA\Delete(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Eliminar una reserva",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reserva",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva eliminada correctamente"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al eliminar la reserva"
     *     )
     * )
     */
    public function destroy(Reserva $reserva)
    {
        try {
            // Asegúrate de que el usuario solo pueda eliminar sus propias reservas
            if ($reserva->user_id !== Auth::id()) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            $reserva->delete();

            return response()->json(['message' => 'Reserva eliminada correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la reserva.' . $e->getMessage()], 500);
        }
    }
}
