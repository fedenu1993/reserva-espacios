<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Modelo de usuario",
 *     @OA\Property(property="id", type="integer", example=1, description="ID del usuario"),
 *     @OA\Property(property="name", type="string", example="Juan Pérez", description="Nombre del usuario"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com", description="Correo electrónico del usuario"),
 *     @OA\Property(property="password", type="string", example="contraseña123", description="Contraseña del usuario (oculta en respuestas)"),
 *     @OA\Property(property="role", type="string", enum={"user", "admin"}, example="user", description="Rol del usuario"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-01T00:00:00Z", description="Fecha de verificación del correo"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z", description="Fecha de creación del usuario"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z", description="Fecha de última actualización del usuario"),
 *     @OA\Property(property="reservas", type="array", @OA\Items(ref="#/components/schemas/Reserva"), description="Reservas asociadas al usuario")
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }
}
