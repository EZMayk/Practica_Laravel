<?php

namespace Database\Factories;

use App\Models\DireccionEntrega;
use App\Models\MensajeEntrega;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MensajeEntrega>
 */
class MensajeEntregaFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (MensajeEntrega $mensaje): void {
            $contenido = $mensaje->mensaje;
            $contenido['datos']['direccion_id'] = $mensaje->direccion_entrega_id;
            $mensaje->update(['mensaje' => $contenido]);
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
            'tipo' => 'cobertura',
            'prioridad' => 'normal',
            'estado_solicitud' => 'cancelada',
            'destino' => null,
            'estado_procesamiento' => 'filtrado',
            'mensaje' => [
                'message_id' => $messageId,
                'correlation_id' => $correlationId,
                'tipo' => 'cobertura',
                'prioridad' => 'normal',
                'estado' => 'cancelada',
                'ocurrido_en' => now()->toIso8601String(),
                'datos' => ['direccion_id' => 0],
            ],
        ];
    }
}
