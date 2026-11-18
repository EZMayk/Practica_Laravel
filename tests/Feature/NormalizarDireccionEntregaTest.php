<?php

namespace Tests\Feature;

use App\Models\DireccionEntrega;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizarDireccionEntregaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_p1_y_p2_normalizan_dos_formatos_equivalentes_al_mismo_contrato(): void
    {
        $origenPlano = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
            'telefono' => '0990000000',
            'referencia' => 'Frente al parque',
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);
        $origenCheckout = $this->postJson('/api/v1/clientes/direcciones/checkout', [
            'direccion' => [
                'etiqueta' => 'Casa de prueba',
                'direccion' => 'Calle Ficticia 123',
                'telefono' => '0990000000',
                'referencia' => 'Frente al parque',
            ],
            'ubicacion' => [
                'latitude' => -0.9580285,
                'longitude' => -80.6384797,
            ],
        ]);

        $origenPlano->assertCreated();
        $origenCheckout->assertCreated();
        $this->assertSame(
            collect($origenPlano->json('data'))->except(['id', 'creadoAt'])->all(),
            collect($origenCheckout->json('data'))->except(['id', 'creadoAt'])->all(),
        );
        $this->assertDatabaseCount('direccion_entregas', 2);
        $this->assertDatabaseHas('direccion_entregas', [
            'etiqueta' => 'Casa de prueba',
            'direccion' => 'Calle Ficticia 123',
            'referencia' => 'Frente al parque',
        ]);
    }

    public function test_checkout_conserva_referencia_sin_inventar_observaciones(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones/checkout', [
            'direccion' => [
                'etiqueta' => 'Oficina de prueba',
                'direccion' => 'Avenida Imaginaria 45',
                'referencia' => 'Edificio de prueba',
            ],
            'ubicacion' => ['latitude' => -0.2, 'longitude' => -78.5],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.referencia', 'Edificio de prueba')
            ->assertJsonPath('data.observaciones', null)
            ->assertJsonPath('data.lat', -0.2)
            ->assertJsonPath('data.lng', -78.5);
        $this->assertSame(1, DireccionEntrega::count());
    }

    public function test_p3_checkout_sin_campo_requerido_devuelve_422_y_no_guarda(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones/checkout', [
            'direccion' => ['direccion' => 'Calle Ficticia 123'],
            'ubicacion' => ['latitude' => -0.2],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['direccion.etiqueta', 'ubicacion.longitude']);
        $this->assertSame(0, DireccionEntrega::count());
    }

    public function test_checkout_rechaza_coordenadas_de_texto_y_campos_ambiguos(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones/checkout', [
            'direccion' => [
                'etiqueta' => 'Casa de prueba',
                'direccion' => 'Calle Ficticia 123',
                'observaciones' => 'No corresponde a este origen',
            ],
            'ubicacion' => ['latitude' => '-0.2', 'longitude' => -78.5],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['direccion', 'ubicacion.latitude']);
        $this->assertSame(0, DireccionEntrega::count());
    }

    public function test_checkout_rechaza_coordenadas_fuera_de_rango(): void
    {
        $response = $this->postJson('/api/v1/clientes/direcciones/checkout', [
            'direccion' => [
                'etiqueta' => 'Casa de prueba',
                'direccion' => 'Calle Ficticia 123',
            ],
            'ubicacion' => ['latitude' => 91, 'longitude' => -181],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ubicacion.latitude', 'ubicacion.longitude']);
        $this->assertSame(0, DireccionEntrega::count());
    }
}
