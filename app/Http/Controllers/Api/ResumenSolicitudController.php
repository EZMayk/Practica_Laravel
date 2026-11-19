<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AsegurarCorrelationId;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResumenSolicitudController extends Controller
{
    /**
     * ID de usuario asociado a las solicitudes de la práctica (dato de prueba).
     */
    private const USUARIO_ID_PRUEBA = 7;

    /**
     * Orquesta la consulta a los servicios de usuarios y de estados y
     * devuelve una respuesta unificada.
     */
    public function show(Request $request, int $solicitudId): JsonResponse
    {
        $correlationId = $request->attributes->get('correlation_id');
        $cabeceras = [AsegurarCorrelationId::CABECERA => $correlationId];

        try {
            $respuestaUsuario = Http::timeout(3)
                ->withHeaders($cabeceras)
                ->get(config('services.usuarios.url').'/usuarios/'.self::USUARIO_ID_PRUEBA);

            $respuestaEstado = Http::timeout(3)
                ->withHeaders($cabeceras)
                ->get(config('services.estados.url').'/estados/'.$solicitudId);
        } catch (ConnectionException $e) {
            Log::error('Servicio no disponible o sin respuesta en el tiempo límite', [
                'solicitud_id' => $solicitudId,
                'motivo' => $e->getMessage(),
            ]);

            return $this->errorControlado('usuarios o estados', $correlationId);
        }

        foreach (['usuarios' => $respuestaUsuario, 'estados' => $respuestaEstado] as $servicio => $respuesta) {
            if ($this->fallo($respuesta)) {
                Log::error('Servicio respondió con error', [
                    'servicio' => $servicio,
                    'solicitud_id' => $solicitudId,
                    'status' => $respuesta->status(),
                ]);

                return $this->errorControlado($servicio, $correlationId);
            }
        }

        return response()->json([
            'solicitud_id' => $solicitudId,
            'usuario' => $respuestaUsuario->json(),
            'estado' => $respuestaEstado->json('estado'),
            'correlation_id' => $correlationId,
        ]);
    }

    private function fallo(Response $respuesta): bool
    {
        return ! $respuesta->successful() || ! is_array($respuesta->json());
    }

    private function errorControlado(string $servicio, string $correlationId): JsonResponse
    {
        return response()->json([
            'mensaje' => "No fue posible obtener la información del servicio de {$servicio}. Intente más tarde.",
            'correlation_id' => $correlationId,
        ], 502);
    }
}
