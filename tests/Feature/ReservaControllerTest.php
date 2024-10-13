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

    public function testStoreCreatesReservaSuccessfully()
    {
        // Crea un usuario y un espacio
        $user = User::factory()->create();
        $espacio = Espacio::factory()->create(['id' => 1]);

        // Asegúrate de que la fecha y hora sean futuras
        $futureDate = now()->addDays(1)->format('Y-m-d'); // Un día en el futuro
        $futureStartTime = now()->addHours(2)->format('H:i'); // Dos horas en el futuro
        $futureEndTime = now()->addHours(3)->format('H:i'); // Tres horas en el futuro

        $this->actingAs($user)
            ->postJson('/api/reservas', [
                'nombre' => 'nombre de reserva',
                'espacio_id' => $espacio->id,
                'fecha' => $futureDate,
                'hora_inicio' => $futureStartTime,
                'hora_fin' => $futureEndTime
            ])
            ->assertStatus(201)
            ->assertJson([
                'nombre' => 'nombre de reserva',
                'user_id' => $user->id,
                'espacio_id' => $espacio->id,
                'fecha' => $futureDate,
                'hora_inicio' => $futureStartTime,
                'hora_fin' => $futureEndTime,
            ]);

        $this->assertDatabaseHas('reservas', [
            'nombre' => 'nombre de reserva',
            'user_id' => $user->id,
            'espacio_id' => $espacio->id,
            'fecha' => $futureDate,
            'hora_inicio' => $futureStartTime,
            'hora_fin' => $futureEndTime,
        ]);
    }

    public function testStoreFailsWithoutNombre()
    {
        $user = User::factory()->create();
        $espacio = Espacio::factory()->create(['id' => 1]);

        $this->actingAs($user)
            ->postJson('/api/reservas', [
                "espacio_id" => $espacio->id,
                "fecha" => "2024-10-09",
                "hora_inicio" => "16:00",
                "hora_fin" => "17:00"
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function testStoreFailsWithNonExistentEspacio()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/reservas', [
                'nombre' => 'nombre de reserva',
                'espacio_id' => 999, // ID que no existe
                'fecha' => '2024-10-09',
                'hora_inicio' => '16:00',
                'hora_fin' => '17:00'
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['espacio_id']);
    }

    public function testStoreFailsWithPastStartTime()
    {
        $user = User::factory()->create();
        $espacio = Espacio::factory()->create(['id' => 1]);

        $this->actingAs($user)
            ->postJson('/api/reservas', [
                'nombre' => 'nombre de reserva',
                'espacio_id' => $espacio->id,
                'fecha' => '2024-10-09',
                'hora_inicio' => '16:00',
                'hora_fin' => '15:00' // Hora de fin antes de la de inicio
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hora_fin']);
    }

    public function testStoreFailsWithOverlappingReservation()
    {
        $user = User::factory()->create();
        $espacio = Espacio::factory()->create(['id' => 1]);

        // Crea una reserva existente que se superpone
        Reserva::factory()->create([
            'espacio_id' => $espacio->id,
            'fecha' => '2024-10-09',
            'hora_inicio' => '15:00',
            'hora_fin' => '17:00'
        ]);

        $this->actingAs($user)
            ->postJson('/api/reservas', [
                'nombre' => 'nombre de reserva',
                'espacio_id' => $espacio->id,
                'fecha' => '2024-10-09',
                'hora_inicio' => '16:00',
                'hora_fin' => '18:00' // Se superpone con la reserva existente
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hora_inicio']);
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
                'nombre' => 'nombre de reserva',
                "espacio_id" => $reserva->espacio_id,
                "fecha" => "2024-10-19",
                "hora_inicio" => "16:00",
                "hora_fin" => "17:00"
            ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $reserva->id,
                'nombre' => 'nombre de reserva',
                "espacio_id" => $reserva->espacio_id,
                "fecha" => "2024-10-19",
                "hora_inicio" => "16:00",
                "hora_fin" => "17:00"
            ]);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'nombre' => 'nombre de reserva',
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
