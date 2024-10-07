<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Reserva;
use App\Models\Espacio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test para listar las reservas del usuario autenticado.
     */
    // Se verifica que el usuario autenticado pueda obtener sus reservas, comprobando la cantidad y el contenido del JSON de respuesta.
    public function testIndexReturnsUserReservations()
    {
        $user = User::factory()->create();

        $reservas = Reserva::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/reservas')
            ->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Test para mostrar una reserva por ID.
     */
    // Se prueba que una reserva específica se pueda obtener correctamente por su ID.
    public function testShowReturnsReservaById()
    {
        $user = User::factory()->create();
        $reserva = Reserva::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/reservas/{$reserva->id}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $reserva->id,
                'user_id' => $user->id,
            ]);
    }

    /**
     * Test para crear una nueva reserva.
     */
    // Verifica que se pueda crear una reserva con éxito, asegurándose de que se almacenen los datos correctos en la base de datos.
    public function testStoreCreatesReservaSuccessfully()
    {
        $user = User::factory()->create();
        // Crea un espacio primero para asegurarte de que existe
        $espacio = Espacio::factory()->create(['id' => 1]); // Asegúrate de que el ID del espacio sea 1

        $this->actingAs($user)
            ->postJson('/api/reservas', [
                "espacio_id" => $espacio->id, // Usar el espacio creado
                "fecha" => "2024-10-09",
                "hora_inicio" => "16:00",
                "hora_fin" => "17:00"
            ])
            ->assertStatus(201)
            ->assertJson([
                'user_id' => $user->id,
                "espacio_id" => $espacio->id,
                "fecha" => "2024-10-09",
                "hora_inicio" => "16:00",
                "hora_fin" => "17:00"
            ]);

        $this->assertDatabaseHas('reservas', [
            'user_id' => $user->id,
            'espacio_id' => $espacio->id,
            'fecha' => '2024-10-09',
            'hora_inicio' => '16:00',
            'hora_fin' => '17:00',
        ]);
    }

    /**
     * Test para actualizar una reserva existente.
     */
    // Se asegura de que una reserva pueda actualizarse y que los cambios se reflejen tanto en la respuesta como en la base de datos.
    public function testUpdateModifiesReservaSuccessfully()
    {
        $user = User::factory()->create();
        $reserva = Reserva::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson("/api/reservas/{$reserva->id}", [
                "espacio_id" => $reserva->espacio_id,
                "fecha" => "2024-10-19",
                "hora_inicio" => "16:00",
                "hora_fin" => "17:00"
            ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $reserva->id,
                "espacio_id" => $reserva->espacio_id,
                "fecha" => "2024-10-19",
                "hora_inicio" => "16:00",
                "hora_fin" => "17:00"
            ]);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            "espacio_id" => $reserva->espacio_id,
            "fecha" => "2024-10-19",
            "hora_inicio" => "16:00",
            "hora_fin" => "17:00"
        ]);
    }

    /**
     * Test para eliminar una reserva.
     */
    // Prueba que una reserva pueda eliminarse con éxito y que se elimine de la base de datos.
    public function testDestroyDeletesReservaSuccessfully()
    {
        $user = User::factory()->create();
        $reserva = Reserva::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/reservas/{$reserva->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Reserva eliminada correctamente.']);

        $this->assertDatabaseMissing('reservas', [
            'id' => $reserva->id,
        ]);
    }

    /**
     * Test para evitar que un usuario no autorizado pueda eliminar una reserva de otro usuario.
     */
    // Verifica que un usuario no pueda eliminar reservas que no le pertenezcan, devolviendo un error 403 de no autorizado.
    public function testDestroyReturns403IfUnauthorized()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reserva = Reserva::factory()->create(['user_id' => $user1->id]);

        $this->actingAs($user2)
            ->deleteJson("/api/reservas/{$reserva->id}")
            ->assertStatus(403)
            ->assertJson(['message' => 'No autorizado']);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
        ]);
    }
}
