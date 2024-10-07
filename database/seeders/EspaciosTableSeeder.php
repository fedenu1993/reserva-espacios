<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;


class EspaciosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserta datos de ejemplo en la tabla de espacios
        DB::table('espacios')->insert([
            [
                'nombre' => 'Sala de Conferencias',
                'descripcion' => 'Una sala espaciosa para conferencias grandes',
                'capacidad' => 100,
                'disponibilidad' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Auditorio',
                'descripcion' => 'Auditorio equipado con tecnología de audio y video',
                'capacidad' => 300,
                'disponibilidad' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
