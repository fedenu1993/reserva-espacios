<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Crea users
        \App\Models\User::factory(4)->create();
        // user admin
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);
        // user user
        \App\Models\User::factory()->create([
            'name' => 'Normal User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user'
        ]);

        // Llamar seeder personalizado
        $this->call(EspaciosTableSeeder::class);
        $this->call(ReservaSeeder::class);
    }
}
