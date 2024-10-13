<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Reserva",
 *     type="object",
 *     required={"espacio_id", "user_id", "fecha", "hora_inicio", "hora_fin"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="nombre", type="string", example="Fiesta de 15"),
 *     @OA\Property(property="espacio_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="user_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="fecha", type="string", format="date", example="2024-10-13"),
 *     @OA\Property(property="hora_inicio", type="string", format="time", example="14:00:00"),
 *     @OA\Property(property="hora_fin", type="string", format="time", example="18:00:00"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class Reserva extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'user_id',
        'espacio_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function espacio()
    {
        return $this->belongsTo(Espacio::class);
    }
}
