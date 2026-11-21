<?php

namespace Tests\Feature;

use App\Models\DireccionEntrega;
use App\Models\SolicitudResumenEntrega;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SolicitudResumenEntregaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_token_no_puede_publicar_ni_consultar_solicitudes(): void
    {
        $this->postJson('/api/v1/clientes/resumenes-entrega', ['direccion_id' => 1])
            ->assertUnauthorized();
        $this->getJson('/api/v1/clientes/resumenes-entrega/1')->assertUnauthorized();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_rechaza_mensaje_sin_direccion_y_no_publica_trabajo(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/clientes/resumenes-entrega', [])
            ->assertUnprocessable()->assertJsonValidationErrors('direccion_id');
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_otro_cliente_no_puede_publicar_una_direccion_ajena(): void
    {
        $propietario = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $propietario->id,
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/clientes/resumenes-entrega', ['direccion_id' => $direccion->id])
            ->assertForbidden();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_publica_tres_mensajes_y_los_procesa_al_reanudar_el_consumidor(): void
    {
        $cliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $cliente->id,
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);
        Sanctum::actingAs($cliente);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => true]),
            '*/estimacion*' => Http::response(['costo_estimado' => 2.5, 'tiempo_minutos' => 30]),
        ]);

        $ids = [];
        foreach (range(1, 3) as $numero) {
            $correlationId = "async-entrega-{$numero}";
            $respuesta = $this->postJson('/api/v1/clientes/resumenes-entrega', [
                'direccion_id' => $direccion->id,
            ], ['X-Correlation-ID' => $correlationId]);

            $respuesta->assertStatus(202)
                ->assertJsonPath('data.estado', 'pendiente')
                ->assertJsonPath('data.correlation_id', $correlationId)
                ->assertHeader('X-Correlation-ID', $correlationId);
            $ids[] = $respuesta->json('data.solicitud_id');
        }

        $this->assertDatabaseCount('jobs', 3);
        $this->assertDatabaseCount('solicitud_resumen_entregas', 3);
        Http::assertNothingSent();

        foreach ($ids as $id) {
            $this->getJson("/api/v1/clientes/resumenes-entrega/{$id}")
                ->assertOk()->assertJsonPath('data.estado', 'pendiente');
        }

        Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'resumenes-entrega',
            '--stop-when-empty' => true,
            '--tries' => 3,
        ]);

        $this->assertDatabaseCount('jobs', 0);
        Http::assertSentCount(6);
        Http::assertSent(fn (Request $request): bool => $request->header('X-Correlation-ID') === ['async-entrega-2']);

        foreach ($ids as $numero => $id) {
            $correlationId = 'async-entrega-'.($numero + 1);
            $this->getJson("/api/v1/clientes/resumenes-entrega/{$id}")
                ->assertOk()
                ->assertJsonPath('data.estado', 'completada')
                ->assertJsonPath('data.correlation_id', $correlationId)
                ->assertJsonPath('data.resultado.cobertura.disponible', true)
                ->assertJsonPath('data.resultado.estimacion.tiempo_minutos', 30);
            $this->assertSame($correlationId, SolicitudResumenEntrega::findOrFail($id)->mensaje['correlation_id']);
        }
    }

    public function test_cliente_ajeno_no_puede_consultar_solicitud(): void
    {
        $propietario = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $propietario->id,
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);
        Sanctum::actingAs($propietario);
        $respuesta = $this->postJson('/api/v1/clientes/resumenes-entrega', ['direccion_id' => $direccion->id])
            ->assertStatus(202);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/clientes/resumenes-entrega/'.$respuesta->json('data.solicitud_id'))
            ->assertForbidden();
    }

    public function test_fallo_del_servicio_deja_la_solicitud_como_fallida_tras_reintentos(): void
    {
        $cliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $cliente->id,
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);
        Sanctum::actingAs($cliente);
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => true]),
            '*/estimacion*' => Http::response('error', 500),
        ]);

        $respuesta = $this->postJson('/api/v1/clientes/resumenes-entrega', [
            'direccion_id' => $direccion->id,
        ], ['X-Correlation-ID' => 'async-error-001'])->assertStatus(202);

        Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'resumenes-entrega',
            '--stop-when-empty' => true,
            '--tries' => 3,
            '--backoff' => 0,
        ]);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);
        $this->getJson('/api/v1/clientes/resumenes-entrega/'.$respuesta->json('data.solicitud_id'))
            ->assertOk()
            ->assertJsonPath('data.estado', 'fallida')
            ->assertJsonPath('data.correlation_id', 'async-error-001')
            ->assertJsonPath('data.resultado', null);
    }
}
