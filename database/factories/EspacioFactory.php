<?php

namespace Database\Factories;

use App\Models\Espacio;
use Illuminate\Database\Eloquent\Factories\Factory;

class EspacioFactory extends Factory
{
    protected $model = Espacio::class;

    public function definition()
    {
        // Generar un nombre de imagen aleatorio
        $imagenNombre = time() . '_' . $this->faker->word() . '.jpg'; // Formato de imagen .jpg

        return [
            'nombre' => $this->faker->word(), // Nombre aleatorio
            'descripcion' => $this->faker->sentence(10), // Descripción aleatoria
            'capacidad' => $this->faker->numberBetween(1, 100), // Capacidad entre 1 y 100
            'imagen' => $imagenNombre, // Genera un nombre de imagen de prueba
            'created_at' => now(), // Fecha y hora actuales
            'updated_at' => now(), // Fecha y hora actuales
        ];
    }
}
