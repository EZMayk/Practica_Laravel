<?php

namespace Tests\Feature;

use App\Models\DireccionEntrega;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrarDireccionEntregaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_json_valido_registra_direccion_y_devuelve_contrato_201(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.etiqueta', 'Casa de prueba')
            ->assertJsonPath('data.direccion', 'Calle Ficticia 123')
            ->assertJsonPath('data.telefono', null)
            ->assertJsonPath('data.referencia', null)
            ->assertJsonPath('data.activa', true)
            ->assertJsonStructure(['data' => ['id', 'etiqueta', 'direccion', 'telefono', 'observaciones', 'referencia', 'lat', 'lng', 'activa', 'creadoAt']]);

        $this->assertIsInt($response->json('data.id'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $response->json('data.creadoAt'));

        $this->assertDatabaseHas('direccion_entregas', [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
        ]);
        $this->assertSame(1, DireccionEntrega::count());
    }

    public function test_sin_etiqueta_devuelve_422_y_no_registra(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'direccion' => 'Calle Ficticia 123',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['etiqueta']);
        $this->assertSame(0, DireccionEntrega::count());
    }

    public function test_latitud_con_tipo_incorrecto_devuelve_422_y_no_registra(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
            'lat' => 'norte',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['lat']);
        $this->assertSame(0, DireccionEntrega::count());
    }

    public function test_latitud_como_cadena_numerica_devuelve_422_y_no_registra(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
            'lat' => '1.5',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['lat']);
        $this->assertSame(0, DireccionEntrega::count());
    }

    public function test_coordenadas_fuera_de_rango_devuelven_422_y_no_registran(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
            'lat' => 91,
            'lng' => -181,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['lat', 'lng']);
        $this->assertSame(0, DireccionEntrega::count());
    }

    public function test_textos_obligatorios_vacios_devuelven_422_y_no_registran(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => '',
            'direccion' => '',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['etiqueta', 'direccion']);
        $this->assertSame(0, DireccionEntrega::count());
    }

    public function test_campos_opcionales_y_coordenadas_numericas_aparecen_en_la_respuesta(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Oficina de prueba',
            'direccion' => 'Avenida Imaginaria 45',
            'telefono' => '0990000000',
            'observaciones' => 'Portería ficticia',
            'referencia' => 'Edificio de prueba',
            'lat' => -0.2,
            'lng' => -78.5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.telefono', '0990000000')
            ->assertJsonPath('data.observaciones', 'Portería ficticia')
            ->assertJsonPath('data.referencia', 'Edificio de prueba')
            ->assertJsonPath('data.lat', -0.2)
            ->assertJsonPath('data.lng', -78.5);

        $this->assertSame(1, DireccionEntrega::count());
    }

    public function test_textos_que_superan_limites_devuelven_422_y_no_registran(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => str_repeat('a', 41),
            'direccion' => str_repeat('b', 201),
            'telefono' => str_repeat('1', 21),
            'observaciones' => str_repeat('c', 201),
            'referencia' => str_repeat('d', 201),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'etiqueta', 'direccion', 'telefono', 'observaciones', 'referencia',
        ]);
        $this->assertSame(0, DireccionEntrega::count());
    }
}
