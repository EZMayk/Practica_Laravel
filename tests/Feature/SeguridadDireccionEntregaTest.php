<?php

namespace Tests\Feature;

use App\Models\DireccionEntrega;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeguridadDireccionEntregaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_token_no_puede_registrar_una_direccion_y_recibe_401(): void
    {
        $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Casa',
            'direccion' => 'Calle de prueba',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('direccion_entregas', 0);
    }

    public function test_sin_token_no_puede_usar_checkout_y_recibe_401(): void
    {
        $this->postJson('/api/v1/clientes/direcciones/checkout', [])->assertUnauthorized();
    }

    public function test_catalogo_no_acepta_creaciones_anonimas(): void
    {
        $this->postJson('/api/productos', [])->assertUnauthorized();
        $this->postJson('/api/categorias', [])->assertUnauthorized();
    }

    public function test_sin_token_no_puede_consultar_un_resumen_y_recibe_401(): void
    {
        $direccion = DireccionEntrega::factory()->create();
        Http::preventStrayRequests();

        $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")
            ->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_usuario_autenticado_registra_su_direccion_sin_poder_elegir_propietario(): void
    {
        $cliente = User::factory()->create();
        $otroCliente = User::factory()->create();
        Sanctum::actingAs($cliente);

        $respuesta = $this->postJson('/api/v1/clientes/direcciones', [
            'etiqueta' => 'Casa',
            'direccion' => 'Calle de prueba',
            'lat' => -0.9580285,
            'lng' => -80.6384797,
            'user_id' => $otroCliente->id,
        ]);

        $respuesta->assertCreated()->assertJsonPath('data.etiqueta', 'Casa');
        $this->assertDatabaseHas('direccion_entregas', [
            'id' => $respuesta->json('data.id'),
            'user_id' => $cliente->id,
        ]);
        $respuesta->assertJsonMissingPath('data.user_id');
    }

    public function test_otro_usuario_autenticado_no_puede_consultar_la_direccion_y_recibe_403(): void
    {
        $propietario = User::factory()->create();
        $otroCliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $propietario->id,
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);
        Sanctum::actingAs($otroCliente);
        Http::preventStrayRequests();

        $respuesta = $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega");

        $respuesta->assertForbidden()->assertJsonMissingPath('data.direccion');
        Http::assertNothingSent();
    }

    public function test_direccion_anterior_sin_propietario_no_se_expone_a_otro_usuario(): void
    {
        $direccion = DireccionEntrega::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        Http::preventStrayRequests();

        $this->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega")
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_token_bearer_del_propietario_permite_consultar_el_resumen(): void
    {
        $cliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create([
            'user_id' => $cliente->id,
            'lat' => -0.9580285,
            'lng' => -80.6384797,
        ]);
        $token = $cliente->createToken('practica-individual', ['*'], now()->addHour())->plainTextToken;
        Http::preventStrayRequests();
        Http::fake([
            '*/cobertura*' => Http::response(['disponible' => true]),
            '*/estimacion*' => Http::response(['costo_estimado' => 2.5, 'tiempo_minutos' => 30]),
        ]);

        $respuesta = $this->withToken($token)
            ->getJson("/api/v1/clientes/direcciones/{$direccion->id}/resumen-entrega");

        $respuesta->assertOk()
            ->assertJsonPath('data.direccion.id', $direccion->id)
            ->assertJsonPath('data.cobertura.disponible', true);
        Http::assertSentCount(2);
    }

    public function test_politica_solo_permite_consultar_la_direccion_al_propietario(): void
    {
        $propietario = User::factory()->create();
        $otroCliente = User::factory()->create();
        $direccion = DireccionEntrega::factory()->create(['user_id' => $propietario->id]);

        $this->assertTrue(Gate::forUser($propietario)->allows('view', $direccion));
        $this->assertFalse(Gate::forUser($otroCliente)->allows('view', $direccion));
    }
}
