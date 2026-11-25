<?php

namespace App\Jobs;

use App\Models\MensajeEntrega;
use App\Services\ConsumidorMensajeEntrega as ServicioConsumidor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class ConsumirMensajeEntrega implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    /**
     * @param  array{message_id: string, correlation_id: string, tipo: string, prioridad: string, estado: string, ocurrido_en: string, datos: array{direccion_id: int}}  $contenido
     */
    public function __construct(public int $mensajeId, public array $contenido) {}

    abstract protected function destino(): string;

    public function handle(ServicioConsumidor $consumidor): void
    {
        $consumidor->procesar($this->mensajeId, $this->destino());
    }

    public function failed(?Throwable $exception): void
    {
        $mensaje = MensajeEntrega::find($this->mensajeId);

        if ($mensaje === null) {
            return;
        }

        $mensaje->update(['estado_procesamiento' => 'fallido', 'procesado_en' => now()]);
        Log::error('Consumidor de mensaje de entrega agotó reintentos', [
            'message_id' => $mensaje->message_id,
            'correlation_id' => $mensaje->correlation_id,
            'destino' => $this->destino(),
            'motivo' => $exception?->getMessage(),
        ]);
    }
}
