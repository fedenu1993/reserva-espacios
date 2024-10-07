<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase; // Esto asegura que la base de datos se restablezca para cada prueba

    public function testLoginSuccessful()
    {
        // Crea un usuario con contraseña encriptada
        $user = User::factory()->create([
            'password' => Hash::make('contraseña123'), // Asegúrate de que la contraseña sea la misma que en la prueba
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'contraseña123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']); // Verifica que se devuelva un token
    }

    public function testLoginFailedWithInvalidCredentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'usuario@example.com',
            'password' => 'contraseñaIncorrecta',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciales incorrectas']);
    }

    public function testLoginValidationFails()
    {
        $response = $this->postJson('/api/login', [
            'email' => '', // Email vacío
            'password' => '', // Contraseña vacía
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function testLogoutSuccessfully()
    {
        // Crea un usuario y loguealo
        $user = User::factory()->create([
            'password' => Hash::make('contraseña123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'contraseña123',
        ]);

        $token = $response->json('token');

        // Realiza la solicitud de logout
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sesión cerrada correctamente']);
    }

    public function testLogoutWithoutAuthorization()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
