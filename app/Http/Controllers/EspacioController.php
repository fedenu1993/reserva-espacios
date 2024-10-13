<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Espacio;
use Illuminate\Support\Facades\Storage;

class EspacioController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/espacios",
     *     tags={"Espacios"},
     *     summary="Obtener todos los espacios",
     *     description="Este endpoint permite obtener una lista paginada de todos los espacios disponibles. Se pueden aplicar filtros opcionales por nombre, capacidad y fecha.",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Número de elementos por página (default: 10)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Número de página actual (default: 1)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="nombre",
     *         in="query",
     *         required=false,
     *         description="Filtrar espacios por nombre (opcional)",
     *         @OA\Schema(type="string", example="Sala de Conferencias")
     *     ),
     *     @OA\Parameter(
     *         name="capacidad",
     *         in="query",
     *         required=false,
     *         description="Filtrar espacios por capacidad mínima (opcional)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="fecha",
     *         in="query",
     *         required=false,
     *         description="Filtrar espacios que no estén reservados en la fecha especificada (opcional)",
     *         @OA\Schema(type="string", format="date", example="2024-10-13")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de espacios",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Espacio")),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="last_page", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener los espacios",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al obtener los espacios.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            // Número de elementos por página y página actual
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Filtros opcionales
            $nombre = $request->input('nombre');
            $capacidad = $request->input('capacidad');
            $fecha = $request->input('fecha');

            // Consulta base
            $query = Espacio::query();

            // Aplicar filtro por nombre (si se proporciona)
            if ($nombre) {
                $query->where('nombre', 'LIKE', '%' . $nombre . '%');
            }

            // Aplicar filtro por capacidad (si se proporciona)
            if ($capacidad) {
                $query->where('capacidad', '>=', $capacidad);
            }

            // Aplicar filtro por fecha (si se proporciona)
            if ($fecha) {
                $query->whereDoesntHave('reservas', function ($query) use ($fecha) {
                    $query->whereDate('fecha', $fecha)
                        ->whereRaw('hora_inicio <= "00:00" AND hora_fin >= "23:59"');
                });
            }

            // Paginación de resultados
            $espacios = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($espacios); // Devolver los espacios paginados
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los espacios. ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/espacios",
     *     tags={"Espacios"},
     *     summary="Crear un nuevo espacio",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"nombre", "descripcion", "capacidad"},
     *                 @OA\Property(property="nombre", type="string", example="Sala de Conferencias"),
     *                 @OA\Property(property="descripcion", type="string", example="Una sala amplia para conferencias."),
     *                 @OA\Property(property="capacidad", type="integer", example=50),
     *                 @OA\Property(property="imagen", type="string", format="binary", description="Imagen del espacio en formato jpg, png o jpeg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Espacio creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Espacio")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="El campo nombre es obligatorio.")
     *         )
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
                'imagen' => 'required|string', // Aceptar cadena Base64
            ]);

            // Guardar la nueva imagen y obtener su nombre
            $imagenNombre = $this->saveBase64Image($validated['imagen']);
            $validated['imagen'] = $imagenNombre; // Guardamos solo el nombre del archivo

            $espacio = Espacio::create($validated);

            return response()->json($espacio, 201);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al crear el espacio.',
                    'error' => $e->getMessage(),
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
     *     description="Devuelve los detalles de un espacio. La propiedad `imagen` contiene la imagen en formato base64.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del espacio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del espacio con la imagen en base64",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nombre", type="string", example="Sala de Conferencias"),
     *             @OA\Property(property="descripcion", type="string", example="Una sala amplia para conferencias."),
     *             @OA\Property(property="capacidad", type="integer", example=50),
     *             @OA\Property(property="imagen", type="string", example="data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA..."),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
     *         )
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
    public function show($id)
    {
        $espacio = Espacio::find($id);

        if (!$espacio) {
            return response()->json(['message' => 'Espacio no encontrado.'], 404);
        }

        // Obtener la imagen en base64
        $imagenPath = 'imagenes/' . $espacio->imagen; // Ajusta el path según tu estructura
        $base64 = null;

        if (Storage::disk('public')->exists($imagenPath)) { // Verifica si la imagen existe usando Storage
            $base64 = 'data:image/jpeg;base64,' . base64_encode(Storage::disk('public')->get($imagenPath)); // Cambia el tipo MIME si es necesario
        }

        return response()->json([
            'id' => $espacio->id,
            'nombre' => $espacio->nombre,
            'descripcion' => $espacio->descripcion,
            'capacidad' => $espacio->capacidad,
            'imagen' => $base64, // Aquí enviamos el base64 de la imagen
            'created_at' => $espacio->created_at,
            'updated_at' => $espacio->updated_at,
        ]);
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="nombre", type="string", example="Sala de Reuniones"),
     *                 @OA\Property(property="descripcion", type="string", example="Sala equipada para reuniones."),
     *                 @OA\Property(property="capacidad", type="integer", example=30),
     *                 @OA\Property(property="imagen", type="string", format="binary", description="Imagen actualizada del espacio en formato jpg, png o jpeg")
     *             )
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
                'imagen' => 'nullable|string', // Aceptar cadena Base64
            ]);


            // Guardar la imagen si se proporciona
            if (isset($validated['imagen']) && !empty($validated['imagen'])) {
                // Eliminar la imagen antigua si existe
                $this->deleteImage($espacio->imagen);

                // Guardar la nueva imagen y obtener su nombre
                $imagenNombre = $this->saveBase64Image($validated['imagen']);
                $validated['imagen'] = $imagenNombre; // Guardamos solo el nombre del archivo
            }

            $espacio->update($validated);
            return response()->json($espacio, 200);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al actualizar el espacio.',
                    'error' => $e->getMessage(),
                ],
                500
            );
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
     *         response=204,
     *         description="Espacio eliminado exitosamente"
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
            // Verifica si existe la imagen antes de intentar eliminarla
            if ($espacio->imagen && Storage::disk('public')->exists('imagenes/' . $espacio->imagen)) {
                // Eliminar la imagen del servidor
                $this->deleteImage($espacio->imagen);
            }

            // Eliminar el registro del espacio
            $espacio->delete();

            return response()->json(['message' => 'Espacio eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => 'Error al eliminar el espacio.',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    private function saveImage($image)
    {
        // Guardar la imagen en el disco 'public'
        $imagenNombre = time() . '_' . $image->getClientOriginalName();
        Storage::disk('public')->putFileAs('imagenes', $image, $imagenNombre);
        return $imagenNombre;
    }

    // Método para guardar la imagen Base64
    private function saveBase64Image($base64String)
    {
        // Validar si el string tiene el prefijo 'data:image/png;base64,'
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
            // Eliminar el prefijo y decodificar la imagen
            $data = substr($base64String, strpos($base64String, ',') + 1);
            $data = base64_decode($data);

            // Verificar si la decodificación fue exitosa
            if ($data === false) {
                throw new \Exception('Base64 decode error');
            }

            // Generar un nombre único para la imagen
            $imagenNombre = time() . '_' . uniqid() . '.png'; // Cambia la extensión según el tipo

            // Guardar la imagen en el disco 'public'
            Storage::disk('public')->put('imagenes/' . $imagenNombre, $data);

            return $imagenNombre;
        }

        throw new \Exception('Invalid base64 image format');
    }


    private function deleteImage($imagenNombre)
    {
        // Eliminar la imagen usando el sistema de archivos de Laravel
        if (Storage::disk('public')->exists('imagenes/' . $imagenNombre)) {
            Storage::disk('public')->delete('imagenes/' . $imagenNombre);
        }
    }
}
