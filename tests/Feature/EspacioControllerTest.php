<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Espacio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EspacioControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test para obtener todos los espacios.
     */
    // Este test crea 3 espacios y verifica que el endpoint /api/espacios devuelve una respuesta 200 y contiene exactamente 3 elementos.
    public function testIndexReturnsAllEspacios()
    {
        // Crea algunos espacios para la prueba
        Espacio::factory()->count(3)->create();

        // Llama al endpoint y verifica el estado
        $response = $this->getJson('/api/espacios');

        $response->assertStatus(200)
            ->assertJsonCount(3); // Verifica que se devuelvan 3 espacios
    }

    /**
     * Test para crear un nuevo espacio.
     */
    // Este test verifica que puedes crear un nuevo espacio. Se asegura de que la respuesta tenga el contenido correcto y que el espacio se guarde en la base de datos.
    public function testStoreCreatesEspacioSuccessfully()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $response = $this->postJson('/api/espacios', [
            'nombre' => 'Sala de Reuniones',
            'descripcion' => 'Una sala grande para reuniones.',
            'capacidad' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'nombre' => 'Sala de Reuniones',
                'descripcion' => 'Una sala grande para reuniones.',
                'capacidad' => 10,
            ]);

        $this->assertDatabaseHas('espacios', [
            'nombre' => 'Sala de Reuniones',
            'descripcion' => 'Una sala grande para reuniones.',
            'capacidad' => 10,
        ]);
    }

    /**
     * Test para obtener un espacio por ID.
     */
    // Este test verifica que puedes obtener un espacio específico por su ID y que la respuesta contiene los datos correctos.
    public function testShowReturnsEspacio()
    {
        $espacio = Espacio::factory()->create();

        $response = $this->getJson('/api/espacios/' . $espacio->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $espacio->id,
                'nombre' => $espacio->nombre,
                'descripcion' => $espacio->descripcion,
                'capacidad' => $espacio->capacidad,
            ]);
    }

    /**
     * Test para actualizar un espacio.
     */
    // Este test actualiza un espacio existente y comprueba que la respuesta contenga los nuevos datos y que estos se hayan guardado correctamente en la base de datos.
    public function testUpdateUpdatesEspacioSuccessfully()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $espacio = Espacio::factory()->create();

        $response = $this->putJson('/api/espacios/' . $espacio->id, [
            'nombre' => 'Sala de Conferencias',
            'descripcion' => 'Una sala grande para conferencias.',
            'capacidad' => 20,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $espacio->id,
                'nombre' => 'Sala de Conferencias',
                'descripcion' => 'Una sala grande para conferencias.',
                'capacidad' => 20,
            ]);

        $this->assertDatabaseHas('espacios', [
            'id' => $espacio->id,
            'nombre' => 'Sala de Conferencias',
            'descripcion' => 'Una sala grande para conferencias.',
            'capacidad' => 20,
        ]);
    }

    /**
     * Test para eliminar un espacio.
     */
    // Este test elimina un espacio y verifica que la respuesta indique que se eliminó correctamente y que ya no existe en la base de datos.
    public function testDestroyDeletesEspacioSuccessfully()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $espacio = Espacio::factory()->create();

        $response = $this->deleteJson('/api/espacios/' . $espacio->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Espacio eliminado correctamente.']);

        $this->assertDatabaseMissing('espacios', [
            'id' => $espacio->id,
        ]);
    }
}
