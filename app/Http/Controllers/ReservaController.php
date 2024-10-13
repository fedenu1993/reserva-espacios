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
     *     description="Este endpoint permite obtener las reservas del usuario autenticado. Opcionalmente, se pueden filtrar las reservas por `espacio_id`.",
     *     @OA\Parameter(
     *         name="espacio_id",
     *         in="query",
     *         required=false,
     *         description="ID del espacio para filtrar las reservas",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="array", 
     *             @OA\Items(ref="#/components/schemas/Reserva")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener reservas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al obtener reservas: {detalle_error}")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function index(Request $request)
    {
        try {
            // Obtener el ID del usuario autenticado
            $userId = Auth::id();

            // Obtener el espacio_id del request
            $espacioId = $request->input('espacio_id');

            // Si se proporciona espacio_id, obtener las reservas de ese espacio
            if ($espacioId) {
                $reservas = Reserva::where('espacio_id', $espacioId)->get();

                // Si no hay reservas para ese espacio, retornar un array vacío
                if ($reservas->isEmpty()) {
                    return response()->json([]);
                }

                return response()->json($reservas);
            }

            // Si no se proporciona espacio_id, obtener las reservas del usuario autenticado
            $reservas = Reserva::where('user_id', $userId)->with('espacio')->get();
            return response()->json($reservas);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener reservas: ' . $e->getMessage()], 500);
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
     *     description="Permite al usuario autenticado crear una reserva en un espacio disponible.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la nueva reserva",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="nombre", type="string", example="Evento Corporativo"),
     *             @OA\Property(property="espacio_id", type="integer", example=1),
     *             @OA\Property(property="fecha", type="string", format="date", example="2024-10-20"),
     *             @OA\Property(property="hora_inicio", type="string", format="time", example="14:00"),
     *             @OA\Property(property="hora_fin", type="string", format="time", example="18:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Reserva")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={"espacio_id": {"El espacio es obligatorio."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al crear la reserva",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al crear la reserva. {detalle_error}")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function store(Request $request)
    {
        try {
            // Verificar si el usuario está autenticado
            if (!Auth::check()) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            // Validar los datos de la solicitud
            $validatedData = $this->validateReserva($request);
            $validatedData['user_id'] = Auth::id(); // Asignar el ID del usuario autenticado

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
     *     description="Permite al usuario autenticado actualizar una reserva de su propiedad.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reserva a actualizar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos actualizados de la reserva",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="nombre", type="string", example="Cumpleaños"),
     *             @OA\Property(property="espacio_id", type="integer", example=2),
     *             @OA\Property(property="fecha", type="string", format="date", example="2024-10-20"),
     *             @OA\Property(property="hora_inicio", type="string", format="time", example="18:00"),
     *             @OA\Property(property="hora_fin", type="string", format="time", example="23:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva actualizada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Reserva")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={"nombre": {"El nombre es obligatorio."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al actualizar la reserva",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al actualizar la reserva. {detalle_error}")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function update(Request $request, Reserva $reserva)
    {
        try {
            // Verificar que la reserva pertenece al usuario autenticado
            if ($reserva->user_id !== Auth::id()) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            // Validar los datos recibidos
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
        // Validación de entrada
        $validatedData = $request->validate([
            'nombre' => 'required',
            'espacio_id' => 'required|exists:espacios,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        $now = now(); // Fecha y hora actual

        // Combinar fecha y hora de inicio
        $reservationStartTime = \Carbon\Carbon::parse($validatedData['fecha'] . ' ' . $validatedData['hora_inicio']);
        if ($reservationStartTime->isPast()) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'La hora de inicio debe ser en el futuro.',
            ]);
        }

        // Manejo especial para reservas de todo el día (00:00 a 23:59)
        $isFullDay = $validatedData['hora_inicio'] === '00:00' && $validatedData['hora_fin'] === '23:59';

        // Comprobación de superposición de reservas
        $overlappingReservations = Reserva::where('espacio_id', $validatedData['espacio_id'])
            ->where('fecha', $validatedData['fecha'])
            ->where(function ($query) use ($validatedData, $isFullDay) {
                if ($isFullDay) {
                    // Detecta conflictos en toda la fecha
                    $query->where('fecha', $validatedData['fecha']);
                } else {
                    // Verifica conflictos parciales por horas
                    $query->whereBetween('hora_inicio', [$validatedData['hora_inicio'], $validatedData['hora_fin']])
                        ->orWhereBetween('hora_fin', [$validatedData['hora_inicio'], $validatedData['hora_fin']]);
                }
            })
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId); // Excluir la reserva actual al editar
            })
            ->exists();

        // Si hay conflicto, lanzar excepción
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
