<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ServicioEntregaNoDisponible;
use App\Http\Controllers\Controller;
use App\Http\Resources\DireccionEntregaResource;
use App\Models\DireccionEntrega;
use App\Services\CalculadorResumenEntrega;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ResumenEntregaController extends Controller
{
    public function show(Request $request, DireccionEntrega $direccion, CalculadorResumenEntrega $calculador): JsonResponse
    {
        Gate::authorize('view', $direccion);

        $correlationId = $request->attributes->get('correlation_id');

        if ($direccion->lat === null || $direccion->lng === null) {
            return response()->json([
                'mensaje' => 'La dirección necesita coordenadas para consultar la entrega.',
                'correlation_id' => $correlationId,
            ], 422);
        }

        try {
            $resultado = $calculador->calcular($direccion, $correlationId);
        } catch (ServicioEntregaNoDisponible $exception) {
            Log::error('Servicio de entrega no disponible o sin respuesta', [
                'servicio' => $exception->servicio,
                'direccion_id' => $direccion->id,
                'motivo' => $exception->getMessage(),
            ]);

            return $this->errorControlado($correlationId);
        }

        return response()->json([
            'data' => [
                'direccion' => (new DireccionEntregaResource($direccion))->toArray($request),
                'cobertura' => $resultado['cobertura'],
                'estimacion' => $resultado['estimacion'],
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
