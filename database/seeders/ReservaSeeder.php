<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reserva;
use App\Models\Espacio;
use App\Models\User;
use Carbon\Carbon;

class ReservaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Obtener todos los espacios y usuarios existentes
        $espacios = Espacio::all();
        $usuarios = User::all();

        // Comprobar que haya espacios y usuarios disponibles
        if ($espacios->isEmpty() || $usuarios->isEmpty()) {
            $this->command->info('No hay espacios o usuarios disponibles para crear reservas.');
            return;
        }

        // Crear algunas reservas
        foreach ($espacios as $espacio) {
            foreach ($usuarios as $usuario) {
                $nombre = 'Reserva por ' . $usuario->name . ' en ' . $espacio->nombre;
                Reserva::create([
                    'nombre' => $nombre,
                    'espacio_id' => $espacio->id,
                    'user_id' => $usuario->id,
                    'fecha' => Carbon::now()->addDays(rand(1, 10)), // Reserva para los próximos 10 días
                    'hora_inicio' => '09:00',
                    'hora_fin' => '11:00',
                ]);
            }
        }
    }
}
