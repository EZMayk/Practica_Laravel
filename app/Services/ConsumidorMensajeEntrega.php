<?php

namespace App\Services;

use App\Exceptions\ServicioEntregaNoDisponible;
use App\Http\Middleware\AsegurarCorrelationId;
use App\Models\MensajeEntrega;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LogicException;

class ConsumidorMensajeEntrega
{
    public function __construct(private readonly CalculadorResumenEntrega $calculador) {}

    public function procesar(int $mensajeId, string $destino): void
    {
        $mensaje = MensajeEntrega::with('direccionEntrega')->findOrFail($mensajeId);

        if (in_array($mensaje->estado_procesamiento, ['completado', 'no_enrutable'], true)) {
            return;
        }

        if ($mensaje->destino !== $destino) {
            throw new LogicException('El consumidor no coincide con el destino registrado.');
        }

        $mensaje->update(['estado_procesamiento' => 'procesando']);
        Log::info('Mensaje de entrega recibido por consumidor', [
            'message_id' => $mensaje->message_id,
            'correlation_id' => $mensaje->correlation_id,
            'tipo' => $mensaje->tipo,
            'destino' => $destino,
        ]);

        if ($destino === 'no-enrutables') {
            $mensaje->update(['estado_procesamiento' => 'no_enrutable', 'procesado_en' => now()]);
            Log::warning('Mensaje de entrega enviado a revisión por tipo desconocido', [
                'message_id' => $mensaje->message_id,
                'correlation_id' => $mensaje->correlation_id,
                'tipo' => $mensaje->tipo,
                'destino' => $destino,
            ]);

            return;
        }

        $resultado = match ($destino) {
            'cobertura-entrega' => ['cobertura' => $this->consultarServicio($mensaje, 'cobertura')],
            'estimacion-entrega' => ['estimacion' => $this->consultarServicio($mensaje, 'estimacion')],
            'resumen-entrega' => $this->calculador->calcular($mensaje->direccionEntrega, $mensaje->correlation_id),
            default => throw new LogicException('Destino de entrega no configurado.'),
        };

        $mensaje->update([
            'estado_procesamiento' => 'completado',
            'resultado' => $resultado,
            'procesado_en' => now(),
        ]);

        Log::info('Mensaje de entrega completado por consumidor', [
            'message_id' => $mensaje->message_id,
            'correlation_id' => $mensaje->correlation_id,
            'tipo' => $mensaje->tipo,
            'destino' => $destino,
        ]);
    }

    /** @return array<string, mixed> */
    private function consultarServicio(MensajeEntrega $mensaje, string $servicio): array
    {
        try {
            $respuesta = Http::connectTimeout(1)->timeout(3)
                ->withHeaders([AsegurarCorrelationId::CABECERA => $mensaje->correlation_id])
                ->get(config("services.{$servicio}.url")."/{$servicio}", [
                    'lat' => $mensaje->direccionEntrega->lat,
                    'lng' => $mensaje->direccionEntrega->lng,
                ]);
        } catch (ConnectionException $exception) {
            throw new ServicioEntregaNoDisponible($servicio, 'El servicio de entrega no respondió.', $exception);
        }

        if (! $respuesta->successful()
            || ($servicio === 'cobertura' && ! is_bool($respuesta->json('disponible')))
            || ($servicio === 'estimacion' && ((! is_int($respuesta->json('costo_estimado')) && ! is_float($respuesta->json('costo_estimado')))
                || ! is_int($respuesta->json('tiempo_minutos'))))) {
            throw new ServicioEntregaNoDisponible($servicio, 'El servicio de entrega respondió con datos inválidos.');
        }

        return $respuesta->json();
    }
}
