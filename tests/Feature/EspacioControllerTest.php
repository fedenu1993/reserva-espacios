<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Espacio;
use App\Models\Reserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EspacioControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Configura el sistema de archivos para usar el disco temporal
        Storage::fake('public');
    }

    public function testIndexFiltersByNombre()
    {

        // Simula la autenticación del usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        Espacio::factory()->create(['nombre' => 'Espacio A']);
        Espacio::factory()->create(['nombre' => 'Espacio B']);
        Espacio::factory()->create(['nombre' => 'Espacio C']);

        $response = $this->getJson('/api/espacios?nombre=Espacio A');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Paginado
            ->assertJsonFragment(['nombre' => 'Espacio A']);
    }

    public function testIndexFiltersByCapacidad()
    {

        // Simula la autenticación del usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        Espacio::factory()->create(['capacidad' => 10]);
        Espacio::factory()->create(['capacidad' => 20]);
        Espacio::factory()->create(['capacidad' => 30]);

        $response = $this->getJson('/api/espacios?capacidad=15');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Debe devolver espacios con capacidad >= 15
            ->assertJsonFragment(['capacidad' => 20])
            ->assertJsonFragment(['capacidad' => 30]);
    }

    public function testIndexFiltersByFecha()
    {

        // Simula la autenticación del usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crea espacios y reservas
        $espacio = Espacio::factory()->create();
        $reservaOcupada = Reserva::factory()->create([
            'espacio_id' => $espacio->id,
            'fecha' => '2024-10-14',
            'hora_inicio' => '00:00',
            'hora_fin' => '23:59',
        ]);

        Espacio::factory()->create(['nombre' => 'Espacio Libre']);

        $response = $this->getJson('/api/espacios?fecha=2024-10-14');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Debe devolver solo el espacio libre
            ->assertJsonFragment(['nombre' => 'Espacio Libre']);
    }

    public function testIndexReturnsPaginatedResults()
    {
        // Simula la autenticación del usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        Espacio::factory()->count(25)->create();

        $response = $this->getJson('/api/espacios?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data') // Asegúrate de que devuelva 10 elementos
            ->assertSee(['current_page' => 1])
            ->assertSee(['last_page' => 3]); // Total de 25 elementos / 10 por página
    }

    /**
     * Test para crear un nuevo espacio.
     */
    // Este test verifica que puedes crear un nuevo espacio. Se asegura de que la respuesta tenga el contenido correcto y que el espacio se guarde en la base de datos.
    public function testStoreCreatesEspacioSuccessfully()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Generar una imagen de prueba en memoria
        $image = imagecreate(100, 100); // Crea una imagen de 100x100 píxeles
        $backgroundColor = imagecolorallocate($image, 255, 255, 255); // Color de fondo blanco
        $textColor = imagecolorallocate($image, 0, 0, 0); // Color de texto negro
        imagestring($image, 5, 10, 40, 'Dummy', $textColor); // Escribe "Dummy" en la imagen

        // Guardar la imagen en un buffer
        ob_start();
        imagepng($image); // Genera la imagen en formato PNG
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image); // Libera la memoria de la imagen

        // Convertir a Base64
        $base64Image = 'data:image/png;base64,' . base64_encode($imageData);

        $response = $this->postJson('/api/espacios', [
            'nombre' => 'Sala de Reuniones',
            'descripcion' => 'Una sala grande para reuniones.',
            'capacidad' => 10,
            'imagen' => $base64Image, // Enviar como Base64
        ]);

        // Verifica si el archivo se creó correctamente en el disco fake
        Storage::disk('public')->assertExists('imagenes/' . $response->json('imagen'));
    }

    public function testShowReturnsEspacioSuccessfullyWithImage()
    {
        // Crear un usuario y loguearlo
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Crear un espacio con una imagen ficticia
        $espacio = Espacio::factory()->create(['imagen' => 'ejemplo.jpg']);

        // Simular la existencia de la imagen en el disco
        $imagenPath = 'imagenes/' . $espacio->imagen;
        Storage::disk('public')->put($imagenPath, 'contenido de imagen');

        // Ejecutar la request para obtener el espacio
        $response = $this->getJson('/api/espacios/' . $espacio->id);

        // Verificar que la respuesta sea exitosa y contenga la imagen en base64
        $base64 = 'data:image/jpeg;base64,' . base64_encode('contenido de imagen');
        $response->assertStatus(200)
            ->assertJson([
                'id' => $espacio->id,
                'nombre' => $espacio->nombre,
                'descripcion' => $espacio->descripcion,
                'capacidad' => $espacio->capacidad,
                'imagen' => $base64,
            ]);

        // Verificar que la imagen exista en el disco
        Storage::disk('public')->assertExists($imagenPath);
    }

    public function testShowReturns404IfEspacioNotFound()
    {
        // Crear un usuario y loguearlo
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Ejecutar la request con un ID que no existe
        $response = $this->getJson('/api/espacios/999');

        // Verificar que la respuesta devuelva 404 y el mensaje de error
        $response->assertStatus(404)
            ->assertJson(['message' => 'Espacio no encontrado.']);
    }

    public function testShowReturnsEspacioSuccessfullyWithoutImage()
    {
        // Crear un usuario y loguearlo
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Crear un espacio sin imagen
        $espacio = Espacio::factory()->create(['imagen' => null]);

        // Ejecutar la request para obtener el espacio
        $response = $this->getJson('/api/espacios/' . $espacio->id);

        // Verificar que la respuesta sea exitosa y no tenga imagen (o sea null)
        $response->assertStatus(200)
            ->assertJson([
                'id' => $espacio->id,
                'nombre' => $espacio->nombre,
                'descripcion' => $espacio->descripcion,
                'capacidad' => $espacio->capacidad,
                'imagen' => null, // Verificar que la imagen sea null
            ]);
    }

    /**
     * Test para actualizar un espacio.
     */
    public function testUpdateUpdatesEspacioSuccessfully()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $espacio = Espacio::factory()->create();

        // Generar una imagen de prueba en memoria para la actualización
        $image = imagecreate(100, 100); // Crea una imagen de 100x100 píxeles
        $backgroundColor = imagecolorallocate($image, 255, 255, 255); // Color de fondo blanco
        $textColor = imagecolorallocate($image, 0, 0, 0); // Color de texto negro
        imagestring($image, 5, 10, 40, 'Updated', $textColor); // Escribe "Updated" en la imagen

        // Guardar la imagen en un buffer
        ob_start();
        imagepng($image); // Genera la imagen en formato PNG
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image); // Libera la memoria de la imagen

        // Convertir a Base64
        $base64Image = 'data:image/png;base64,' . base64_encode($imageData);

        $response = $this->putJson('/api/espacios/' . $espacio->id, [
            'nombre' => 'Sala de Conferencias',
            'descripcion' => 'Una sala grande para conferencias.',
            'capacidad' => 20,
            'imagen' => $base64Image, // Enviar como Base64
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

        // Verifica que la nueva imagen se haya guardado en el disco
        Storage::disk('public')->assertExists('imagenes/' . $response->json('imagen'));
    }

    /**
     * Test para eliminar un espacio.
     */
    public function testDestroyDeletesEspacioSuccessfully()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $espacio = Espacio::factory()->create();

        // Simula el almacenamiento de la imagen
        Storage::disk('public')->put('imagenes/' . $espacio->imagen, ''); // Asegúrate de que la imagen existe

        $response = $this->deleteJson('/api/espacios/' . $espacio->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Espacio eliminado correctamente.']);

        $this->assertDatabaseMissing('espacios', [
            'id' => $espacio->id,
        ]);

        // Verifica que la imagen se haya eliminado del disco
        Storage::disk('public')->assertMissing('imagenes/' . $espacio->imagen);
    }
}
