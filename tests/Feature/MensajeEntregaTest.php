<?php

namespace Tests\Feature;

use App\Models\DireccionEntrega;
use App\Models\MensajeEntrega;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MensajeEntregaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tres_tipos_se_enrutan_a_consumidores_distintos_y_guardan_resultados(): void
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

        $rutas = [
            'cobertura' => 'cobertura-entrega',
            'estimacion' => 'estimacion-entrega',
            'resumen' => 'resumen-entrega',
        ];
        $ids = [];

        foreach ($rutas as $tipo => $cola) {
            $respuesta = $this->postJson('/api/v1/clientes/mensajes-entrega', [
                'direccion_id' => $direccion->id,
                'tipo' => $tipo,
                'prioridad' => 'normal',
                'estado' => 'activa',
            ], ['X-Correlation-ID' => "ruta-{$tipo}-001"]);

            $respuesta->assertStatus(202)
                ->assertJsonPath('data.tipo', $tipo)
                ->assertJsonPath('data.destino', $cola)
                ->assertJsonPath('data.estado', 'pendiente')
                ->assertHeader('X-Correlation-ID', "ruta-{$tipo}-001");
            $ids[$tipo] = $respuesta->json('data.id');
            $this->assertSame(1, DB::table('jobs')->where('queue', $cola)->count());
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('jobs', 3);

        foreach ($rutas as $cola) {
            Artisan::call('queue:work', [
                'connection' => 'database',
                '--queue' => $cola,
                '--stop-when-empty' => true,
                '--tries' => 3,
            ]);
        }

        $this->assertDatabaseCount('jobs', 0);
        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/cobertura?')
            && $request->header('X-Correlation-ID') === ['ruta-cobertura-001']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/estimacion?')
            && $request->header('X-Correlation-ID') === ['ruta-estimacion-001']);

        $this->getJson('/api/v1/clientes/mensajes-entrega/'.$ids['cobertura'])
            ->assertOk()->assertJsonPath('data.estado', 'completado')
            ->assertJsonPath('data.resultado.cobertura.disponible', true)
            ->assertJsonMissingPath('data.resultado.estimacion');
        $this->getJson('/api/v1/clientes/mensajes-entrega/'.$ids['estimacion'])
            ->assertOk()->assertJsonPath('data.resultado.estimacion.tiempo_minutos', 30)
            ->assertJsonMissingPath('data.resultado.cobertura');
        $this->getJson('/api/v1/clientes/mensajes-entrega/'.$ids['resumen'])
            ->assertOk()->assertJsonPath('data.resultado.cobertura.disponible', true)
            ->assertJsonPath('data.resultado.estimacion.tiempo_minutos', 30);
    }

    public function test_tipo_desconocido_termina_en_no_enrutables_sin_llamar_servicios(): void
    {
        $cliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create(['user_id' => $cliente->id]);
        Sanctum::actingAs($cliente);
        Http::preventStrayRequests();

        $respuesta = $this->postJson('/api/v1/clientes/mensajes-entrega', [
            'direccion_id' => $direccion->id,
            'tipo' => 'inventario',
            'prioridad' => 'normal',
            'estado' => 'activa',
        ])->assertStatus(202)->assertJsonPath('data.destino', 'no-enrutables');

        $this->assertSame(1, DB::table('jobs')->where('queue', 'no-enrutables')->count());
        Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'no-enrutables',
            '--stop-when-empty' => true,
        ]);

        $this->getJson('/api/v1/clientes/mensajes-entrega/'.$respuesta->json('data.id'))
            ->assertOk()->assertJsonPath('data.estado', 'no_enrutable');
        $this->assertDatabaseCount('jobs', 0);
        Http::assertNothingSent();
    }

    public function test_mensaje_cancelado_se_filtra_sin_llegar_a_una_cola(): void
    {
        $cliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create(['user_id' => $cliente->id]);
        Sanctum::actingAs($cliente);
        Http::preventStrayRequests();

        $respuesta = $this->postJson('/api/v1/clientes/mensajes-entrega', [
            'direccion_id' => $direccion->id,
            'tipo' => 'cobertura',
            'prioridad' => 'normal',
            'estado' => 'cancelada',
        ])->assertOk()->assertJsonPath('data.estado', 'filtrado')
            ->assertJsonPath('data.destino', null);

        $this->assertDatabaseHas('mensaje_entregas', [
            'id' => $respuesta->json('data.id'),
            'estado_procesamiento' => 'filtrado',
        ]);
        $this->assertDatabaseCount('jobs', 0);
        Http::assertNothingSent();
    }

    public function test_direccion_inactiva_se_filtra_aunque_el_mensaje_diga_activa(): void
    {
        $cliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $cliente->id,
            'activa' => false,
        ]);
        Sanctum::actingAs($cliente);

        $this->postJson('/api/v1/clientes/mensajes-entrega', [
            'direccion_id' => $direccion->id,
            'tipo' => 'estimacion',
            'prioridad' => 'normal',
            'estado' => 'activa',
        ])->assertOk()->assertJsonPath('data.estado', 'filtrado');

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_mensaje_sin_campos_obligatorios_devuelve_422_sin_publicar(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/clientes/mensajes-entrega', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['direccion_id', 'tipo', 'prioridad', 'estado']);
        $this->assertDatabaseCount('mensaje_entregas', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_usuario_sin_token_o_sin_propiedad_no_puede_publicar(): void
    {
        $propietario = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create(['user_id' => $propietario->id]);
        $datos = [
            'direccion_id' => $direccion->id,
            'tipo' => 'cobertura',
            'prioridad' => 'normal',
            'estado' => 'activa',
        ];

        $this->postJson('/api/v1/clientes/mensajes-entrega', $datos)->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/clientes/mensajes-entrega', $datos)->assertForbidden();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_otro_cliente_no_puede_leer_resultado(): void
    {
        $propietario = User::factory()->create();
        $mensaje = MensajeEntrega::factory()->create(['user_id' => $propietario->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/clientes/mensajes-entrega/'.$mensaje->id)->assertForbidden();
    }
}
