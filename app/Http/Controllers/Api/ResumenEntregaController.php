<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AsegurarCorrelationId;
use App\Http\Resources\DireccionEntregaResource;
use App\Models\DireccionEntrega;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResumenEntregaController extends Controller
{
    public function show(Request $request, DireccionEntrega $direccion): JsonResponse
    {
        $correlationId = $request->attributes->get('correlation_id');

        if ($direccion->lat === null || $direccion->lng === null) {
            return response()->json([
                'mensaje' => 'La dirección necesita coordenadas para consultar la entrega.',
                'correlation_id' => $correlationId,
            ], 422);
        }

        $cabeceras = [AsegurarCorrelationId::CABECERA => $correlationId];
        $coordenadas = ['lat' => $direccion->lat, 'lng' => $direccion->lng];

        try {
            $servicio = 'cobertura';
            $respuestaCobertura = Http::connectTimeout(1)->timeout(3)
                ->withHeaders($cabeceras)
                ->get(config('services.cobertura.url').'/cobertura', $coordenadas);

            $servicio = 'estimacion';
            $respuestaEstimacion = Http::connectTimeout(1)->timeout(3)
                ->withHeaders($cabeceras)
                ->get(config('services.estimacion.url').'/estimacion', $coordenadas);
        } catch (ConnectionException $e) {
            Log::error('Servicio de entrega no disponible o sin respuesta', [
                'servicio' => $servicio,
                'direccion_id' => $direccion->id,
                'motivo' => $e->getMessage(),
            ]);

            return $this->errorControlado($correlationId);
        }

        if (! $respuestaCobertura->successful()
            || ! is_bool($respuestaCobertura->json('disponible'))
            || ! $respuestaEstimacion->successful()
            || (! is_int($respuestaEstimacion->json('costo_estimado')) && ! is_float($respuestaEstimacion->json('costo_estimado')))
            || ! is_int($respuestaEstimacion->json('tiempo_minutos'))) {
            Log::error('Servicio de entrega respondió con error o datos inválidos', [
                'direccion_id' => $direccion->id,
                'cobertura_status' => $respuestaCobertura->status(),
                'estimacion_status' => $respuestaEstimacion->status(),
            ]);

            return $this->errorControlado($correlationId);
        }

        return response()->json([
            'data' => [
                'direccion' => (new DireccionEntregaResource($direccion))->toArray($request),
                'cobertura' => $respuestaCobertura->json(),
                'estimacion' => $respuestaCobertura->json('disponible') ? $respuestaEstimacion->json() : null,
                'correlation_id' => $correlationId,
            ],
        ]);
    }

    private function errorControlado(string $correlationId): JsonResponse
    {
        return response()->json([
            'mensaje' => 'No fue posible consultar los servicios de entrega. Intente más tarde.',
            'correlation_id' => $correlationId,
        ], 502);
    }
}
