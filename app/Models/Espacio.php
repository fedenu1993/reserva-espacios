<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Espacio",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Sala de Conferencias"),
 *     @OA\Property(property="descripcion", type="string", example="Una sala amplia para conferencias."),
 *     @OA\Property(property="capacidad", type="integer", example=50),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */
class Espacio extends Model
{
    use HasFactory;
    protected $fillable = ['nombre', 'descripcion', 'capacidad'];

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }
}
