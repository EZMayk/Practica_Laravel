<?php

namespace Tests\Feature;

use App\Models\DireccionEntrega;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResumenEntregaTest extends TestCase
{
    use RefreshDatabase;

    public function test_compone_un_resumen_para_una_direccion_guardada(): void
    {
        $direccion = DireccionEntrega::factory()->create(['lat' => -0.9580285, 'lng' => -80.6384797]);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => true]),
            '*/estimacion*' => Http::response(['costo_estimado' => 2.5, 'tiempo_minutos' => 30]),
        ]);

        $respuesta = $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega", [
            'X-Correlation-ID' => 'entrega-001',
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('data.direccion.id', $direccion->id)
            ->assertJsonPath('data.direccion.direccion', $direccion->direccion)
            ->assertJsonPath('data.cobertura.disponible', true)
            ->assertJsonPath('data.estimacion.costo_estimado', 2.5)
            ->assertJsonPath('data.estimacion.tiempo_minutos', 30)
            ->assertJsonPath('data.correlation_id', 'entrega-001')
            ->assertHeader('X-Correlation-ID', 'entrega-001');

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/cobertura?')
            && str_contains($request->url(), 'lat=-0.9580285')
            && $request->header('X-Correlation-ID') === ['entrega-001']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/estimacion?')
            && str_contains($request->url(), 'lng=-80.6384797')
            && $request->header('X-Correlation-ID') === ['entrega-001']);
    }

    public function test_genera_correlation_id_cuando_no_se_envia(): void
    {
        $direccion = DireccionEntrega::factory()->create(['lat' => -0.9580285, 'lng' => -80.6384797]);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => true]),
            '*/estimacion*' => Http::response(['costo_estimado' => 2.5, 'tiempo_minutos' => 30]),
        ]);

        $respuesta = $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")->assertOk();

        $this->assertNotEmpty($respuesta->json('data.correlation_id'));
        $respuesta->assertHeader('X-Correlation-ID', $respuesta->json('data.correlation_id'));
        Http::assertSent(fn (Request $request) => $request->header('X-Correlation-ID') === [$respuesta->json('data.correlation_id')]);
    }

    public function test_direccion_inexistente_devuelve_404_sin_consultar_servicios(): void
    {
        Http::preventStrayRequests();

        $this->getJson('/api/v1/clientes/direcciones/999/resumen-entrega')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_direccion_sin_coordenadas_devuelve_422_sin_consultar_servicios(): void
    {
        $direccion = DireccionEntrega::factory()->create();
        Http::preventStrayRequests();

        $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")
            ->assertUnprocessable()
            ->assertJsonPath('mensaje', 'La dirección necesita coordenadas para consultar la entrega.');

        Http::assertNothingSent();
    }

    public function test_sin_cobertura_no_presenta_una_estimacion_de_entrega(): void
    {
        $direccion = DireccionEntrega::factory()->create(['lat' => -2.0, 'lng' => -79.0]);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => false]),
            '*/estimacion*' => Http::response(['costo_estimado' => 4.0, 'tiempo_minutos' => 45]),
        ]);

        $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")
            ->assertOk()
            ->assertJsonPath('data.cobertura.disponible', false)
            ->assertJsonPath('data.estimacion', null);

        Http::assertSentCount(2);
    }

    public function test_error_http_de_un_servicio_devuelve_502_sin_trazas(): void
    {
        $direccion = DireccionEntrega::factory()->create(['lat' => -0.9580285, 'lng' => -80.6384797]);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => true]),
            '*/estimacion*' => Http::response('error', 500),
        ]);

        $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")
            ->assertStatus(502)
            ->assertJsonStructure(['mensaje', 'correlation_id'])
            ->assertJsonMissingPath('trace')
            ->assertJsonMissingPath('exception');
    }

    public function test_servicio_sin_respuesta_devuelve_502_controlado(): void
    {
        $direccion = DireccionEntrega::factory()->create(['lat' => -0.9580285, 'lng' => -80.6384797]);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => fn () => throw new ConnectionException('timeout'),
            '*/estimacion*' => Http::response(['costo_estimado' => 2.5, 'tiempo_minutos' => 30]),
        ]);

        $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")
            ->assertStatus(502)
            ->assertJsonStructure(['mensaje', 'correlation_id'])
            ->assertJsonMissingPath('trace');
    }

    public function test_respuesta_incompleta_de_un_servicio_devuelve_502(): void
    {
        $direccion = DireccionEntrega::factory()->create(['lat' => -0.9580285, 'lng' => -80.6384797]);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => true]),
            '*/estimacion*' => Http::response(['costo_estimado' => 2.5]),
        ]);

        $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")
            ->assertStatus(502)
            ->assertJsonStructure(['mensaje', 'correlation_id']);
    }
}
