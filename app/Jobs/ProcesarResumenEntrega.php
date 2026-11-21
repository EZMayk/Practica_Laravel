<?php

namespace App\Jobs;

use App\Models\SolicitudResumenEntrega;
use App\Services\CalculadorResumenEntrega;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcesarResumenEntrega implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    /** @var array{message_id: string, correlation_id: string, tipo: string, ocurrido_en: string, payload: array{direccion_id: int}} */
    public array $mensaje;

    /**
     * @param  array{message_id: string, correlation_id: string, tipo: string, ocurrido_en: string, payload: array{direccion_id: int}}  $mensaje
     */
    public function __construct(public int $solicitudId, array $mensaje)
    {
        $this->mensaje = $mensaje;
    }

    public function handle(CalculadorResumenEntrega $calculador): void
    {
        $solicitud = SolicitudResumenEntrega::with('direccionEntrega')->findOrFail($this->solicitudId);

        if ($solicitud->estado === 'completada') {
            return;
        }

        $solicitud->update(['estado' => 'procesando']);
        Log::info('Resumen de entrega consumido', [
            'message_id' => $this->mensaje['message_id'],
            'correlation_id' => $this->mensaje['correlation_id'],
            'solicitud_id' => $this->solicitudId,
            'estado' => 'procesando',
        ]);

        $resultado = $calculador->calcular($solicitud->direccionEntrega, $this->mensaje['correlation_id']);
        $solicitud->update([
            'estado' => 'completada',
            'resultado' => $resultado,
            'procesado_en' => now(),
        ]);

        Log::info('Resumen de entrega completado', [
            'message_id' => $this->mensaje['message_id'],
            'correlation_id' => $this->mensaje['correlation_id'],
            'solicitud_id' => $this->solicitudId,
            'estado' => 'completada',
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        SolicitudResumenEntrega::whereKey($this->solicitudId)->update([
            'estado' => 'fallida',
            'procesado_en' => now(),
        ]);

        Log::error('Resumen de entrega falló tras agotar reintentos', [
            'message_id' => $this->mensaje['message_id'],
            'correlation_id' => $this->mensaje['correlation_id'],
            'solicitud_id' => $this->solicitudId,
            'estado' => 'fallida',
            'motivo' => $exception?->getMessage(),
        ]);
    }
}
