<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class EspaciosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('espacios')->insert([
            [
                'nombre' => 'Sala de Conferencias',
                'descripcion' => 'Una sala espaciosa para conferencias grandes',
                'capacidad' => 100,
                'disponibilidad' => true,
                'imagen' => 'sala_conferencias.jpg',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Auditorio',
                'descripcion' => 'Auditorio equipado con tecnología de audio y video',
                'capacidad' => 300,
                'disponibilidad' => true,
                'imagen' => 'auditorio.jpg',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
