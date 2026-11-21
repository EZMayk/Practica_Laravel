<?php

namespace Database\Factories;

use App\Models\DireccionEntrega;
use App\Models\SolicitudResumenEntrega;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SolicitudResumenEntrega>
 */
class SolicitudResumenEntregaFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (SolicitudResumenEntrega $solicitud): void {
            $mensaje = $solicitud->mensaje;
            $mensaje['payload']['direccion_id'] = $solicitud->direccion_entrega_id;
            $solicitud->update(['mensaje' => $mensaje]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $messageId = (string) Str::uuid();
        $correlationId = (string) Str::uuid();

        return [
            'message_id' => $messageId,
            'correlation_id' => $correlationId,
            'user_id' => User::factory(),
            'direccion_entrega_id' => fn (array $attributes): int => DireccionEntrega::factory()->create([
                'user_id' => $attributes['user_id'],
                'lat' => -0.9580285,
                'lng' => -80.6384797,
            ])->id,
            'estado' => 'pendiente',
            'mensaje' => [
                'message_id' => $messageId,
                'correlation_id' => $correlationId,
                'tipo' => 'resumen_entrega_solicitado',
                'ocurrido_en' => now()->toIso8601String(),
                'payload' => ['direccion_id' => 0],
            ],
        ];
    }
}
