<?php

namespace App\Services;

use App\Exceptions\ServicioEntregaNoDisponible;
use App\Http\Middleware\AsegurarCorrelationId;
use App\Models\DireccionEntrega;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CalculadorResumenEntrega
{
    /**
     * @return array{cobertura: array<string, mixed>, estimacion: array<string, mixed>|null}
     */
    public function calcular(DireccionEntrega $direccion, string $correlationId): array
    {
        $cabeceras = [AsegurarCorrelationId::CABECERA => $correlationId];
        $coordenadas = ['lat' => $direccion->lat, 'lng' => $direccion->lng];

        try {
            $servicio = 'cobertura';
            $cobertura = Http::connectTimeout(1)->timeout(3)->withHeaders($cabeceras)
                ->get(config('services.cobertura.url').'/cobertura', $coordenadas);
            $servicio = 'estimacion';
            $estimacion = Http::connectTimeout(1)->timeout(3)->withHeaders($cabeceras)
                ->get(config('services.estimacion.url').'/estimacion', $coordenadas);
        } catch (ConnectionException $exception) {
            throw new ServicioEntregaNoDisponible($servicio, 'Un servicio de entrega no respondió.', $exception);
        }

        if (! $cobertura->successful()
            || ! is_bool($cobertura->json('disponible'))) {
            throw new ServicioEntregaNoDisponible('cobertura', 'El servicio de cobertura respondió con error o datos inválidos.');
        }

        if (! $estimacion->successful()
            || (! is_int($estimacion->json('costo_estimado')) && ! is_float($estimacion->json('costo_estimado')))
            || ! is_int($estimacion->json('tiempo_minutos'))) {
            throw new ServicioEntregaNoDisponible('estimacion', 'El servicio de estimación respondió con error o datos inválidos.');
        }

        return [
            'cobertura' => $cobertura->json(),
            'estimacion' => $cobertura->json('disponible') ? $estimacion->json() : null,
        ];
    }
}
