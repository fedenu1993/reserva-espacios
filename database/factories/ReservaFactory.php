<?php

namespace Database\Factories;

use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Espacio; // Asumiendo que tienes un modelo 'Espacio'

class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition()
    {
        return [
            'espacio_id' => Espacio::factory(), // Genera automáticamente un espacio ficticio (si tienes el modelo)
            'user_id' => User::factory(), // Genera automáticamente un usuario ficticio
            'fecha' => $this->faker->date(), // Genera una fecha aleatoria
            'hora_inicio' => $this->faker->time('H:i'), // Hora de inicio aleatoria
            'hora_fin' => $this->faker->time('H:i'), // Hora de fin aleatoria
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
