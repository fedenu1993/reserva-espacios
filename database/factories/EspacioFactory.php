<?php

namespace Database\Factories;

use App\Models\Espacio;
use Illuminate\Database\Eloquent\Factories\Factory;

class EspacioFactory extends Factory
{
    protected $model = Espacio::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->word(), // Nombre aleatorio
            'descripcion' => $this->faker->sentence(10), // Descripción aleatoria
            'capacidad' => $this->faker->numberBetween(1, 100), // Capacidad entre 1 y 100
            'disponibilidad' => $this->faker->boolean(), // Disponibilidad aleatoria (true o false)
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
