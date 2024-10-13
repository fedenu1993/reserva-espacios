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

        $horaDesde = $this->faker->time();
        // Generar una hora que sea después de $horaDesde
        $horaHasta = date('H:i', strtotime($horaDesde) + rand(3600, 7200)); // 1 a 2 horas después

        return [
            'nombre' => $this->faker->word(), // Nombre aleatorio
            'espacio_id' => Espacio::factory(), // Genera automáticamente un espacio ficticio (si tienes el modelo)
            'user_id' => User::factory(), // Genera automáticamente un usuario ficticio
            'fecha' => $this->faker->date(), // Genera una fecha aleatoria
            'hora_inicio' => $horaDesde, // Hora de inicio aleatoria
            'hora_fin' => $horaHasta, // Hora de fin aleatoria
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
