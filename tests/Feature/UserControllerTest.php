<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    // Asegura que un usuario sin rol de administrador no pueda obtener la lista.
    public function testIndexAsAdminReturnsAllUsers()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }

    /** @test */
    // Asegura que un usuario sin rol de administrador no pueda obtener la lista.
    public function testIndexAsNonAdminReturnsForbidden()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    /** @test */
    // Comprueba que un administrador pueda crear usuarios.
    public function testStoreAsAdminCreatesUser()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    /** @test */
    // Verifica que no se pueda crear un usuario si el correo electrónico ya está en uso.
    public function testStoreReturnsConflictIfEmailExists()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'existing@example.com']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $response->assertStatus(409);
    }

    /** @test */
    // Asegura que la función show devuelva un usuario.
    public function testShowReturnsUser()
    {

        $user = User::factory()->create();

        $this->actingAs($user); // Simular un usuario autenticado

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertJson($user->toArray());
    }

    /** @test */
    // Verifica que se devuelva un error 404 si el usuario no existe.
    public function testShowReturnsNotFoundIfUserDoesNotExist()
    {

        $this->withoutMiddleware(); // Omitir middleware

        $response = $this->getJson('/api/users/999');

        $response->assertStatus(404);
    }

    /** @test */
    // Prueba la actualización de un usuario, incluyendo el cambio de contraseña.
    public function testUpdateUpdatesUser()
    {

        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
        $this->actingAs($user); // Simular un usuario autenticado

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => 'newpassword123'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['name' => 'Updated Name']);
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    /** @test */
    // Comprueba la eliminación de un usuario.
    public function testDestroyDeletesUser()
    {

        $user = User::factory()->create();
        $this->actingAs($user); // Simular un usuario autenticado

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
