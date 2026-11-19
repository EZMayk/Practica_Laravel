<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResumenSolicitudTest extends TestCase
{
    private function simularServiciosSanos(): void
    {
        Http::fake([
            '*/usuarios/7' => Http::response(['id' => 7, 'nombre' => 'Ana']),
            '*/estados/101' => Http::response(['solicitud_id' => 101, 'estado' => 'pendiente']),
        ]);
    }

    public function test_flujo_normal_devuelve_resumen_unificado(): void
    {
        $this->simularServiciosSanos();

        $this->getJson('/api/resumen-solicitud/101')
            ->assertOk()
            ->assertJsonPath('solicitud_id', 101)
            ->assertJsonPath('usuario.nombre', 'Ana')
            ->assertJsonPath('estado', 'pendiente')
            ->assertJsonStructure(['solicitud_id', 'usuario', 'estado', 'correlation_id']);
    }

    public function test_propaga_el_correlation_id_recibido_a_los_servicios(): void
    {
        $this->simularServiciosSanos();

        $this->getJson('/api/resumen-solicitud/101', ['X-Correlation-ID' => 'abc-123'])
            ->assertOk()
            ->assertJsonPath('correlation_id', 'abc-123')
            ->assertHeader('X-Correlation-ID', 'abc-123');

        Http::assertSent(fn (Request $request) => $request->header('X-Correlation-ID') === ['abc-123']);
    }

    public function test_genera_correlation_id_si_no_se_recibe(): void
    {
        $this->simularServiciosSanos();

        $respuesta = $this->getJson('/api/resumen-solicitud/101')->assertOk();

        $this->assertNotEmpty($respuesta->json('correlation_id'));
        $respuesta->assertHeader('X-Correlation-ID', $respuesta->json('correlation_id'));
    }

    public function test_servicio_con_error_http_devuelve_502_sin_trazas(): void
    {
        Http::fake([
            '*/usuarios/7' => Http::response(['id' => 7, 'nombre' => 'Ana']),
            '*/estados/101' => Http::response('error', 500),
        ]);

        $this->getJson('/api/resumen-solicitud/101')
            ->assertStatus(502)
            ->assertJsonStructure(['mensaje', 'correlation_id'])
            ->assertJsonMissingPath('trace')
            ->assertJsonMissingPath('exception');
    }

    public function test_servicio_sin_respuesta_devuelve_502_controlado(): void
    {
        Http::fake([
            '*/usuarios/7' => fn () => throw new ConnectionException('timeout'),
            '*/estados/101' => Http::response(['solicitud_id' => 101, 'estado' => 'pendiente']),
        ]);

        $this->getJson('/api/resumen-solicitud/101')
            ->assertStatus(502)
            ->assertJsonStructure(['mensaje', 'correlation_id'])
            ->assertJsonMissingPath('trace');
    }
}
