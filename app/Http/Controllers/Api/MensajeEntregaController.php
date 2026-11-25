<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DireccionEntrega;
use App\Models\MensajeEntrega;
use App\Services\EnrutadorMensajeEntrega;
use App\Services\FiltroMensajeEntrega;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MensajeEntregaController extends Controller
{
    public function store(Request $request, FiltroMensajeEntrega $filtro, EnrutadorMensajeEntrega $enrutador): JsonResponse
    {
        $datos = $request->validate([
            'direccion_id' => ['required', 'integer', 'exists:direccion_entregas,id'],
            'tipo' => ['required', 'string', 'max:40', 'regex:/^[a-z_]+$/'],
            'prioridad' => ['required', 'in:alta,normal'],
            'estado' => ['required', 'in:activa,cancelada'],
        ]);

        $direccion = DireccionEntrega::findOrFail($datos['direccion_id']);
        Gate::authorize('view', $direccion);

        $aceptado = $filtro->acepta($datos, $direccion);
        $ruta = $aceptado ? $enrutador->resolver($datos) : null;

        if ($aceptado && $ruta['cola'] !== 'no-enrutables' && ($direccion->lat === null || $direccion->lng === null)) {
            return response()->json(['mensaje' => 'La dirección necesita coordenadas para procesar la entrega.'], 422);
        }

        $messageId = (string) Str::uuid();
        $correlationId = $request->attributes->get('correlation_id');
        $contenido = [
            'message_id' => $messageId,
            'correlation_id' => $correlationId,
            'tipo' => $datos['tipo'],
            'prioridad' => $datos['prioridad'],
            'estado' => $datos['estado'],
            'ocurrido_en' => now()->toIso8601String(),
            'datos' => ['direccion_id' => $direccion->id],
        ];

        $mensaje = DB::transaction(function () use ($request, $direccion, $datos, $ruta, $messageId, $correlationId, $contenido): MensajeEntrega {
            $mensaje = MensajeEntrega::create([
                'message_id' => $messageId,
                'correlation_id' => $correlationId,
                'user_id' => $request->user()->id,
                'direccion_entrega_id' => $direccion->id,
                'tipo' => $datos['tipo'],
                'prioridad' => $datos['prioridad'],
                'estado_solicitud' => $datos['estado'],
                'destino' => $ruta['cola'] ?? null,
                'estado_procesamiento' => $ruta === null ? 'filtrado' : 'pendiente',
                'mensaje' => $contenido,
            ]);

            if ($ruta !== null) {
                $consumidor = $ruta['consumidor'];
                $consumidor::dispatch($mensaje->id, $contenido)->onConnection('database')->onQueue($ruta['cola']);
            }

            return $mensaje;
        });

        Log::info($ruta === null ? 'Mensaje de entrega filtrado' : 'Mensaje de entrega enrutado', [
            'message_id' => $messageId,
            'correlation_id' => $correlationId,
            'tipo' => $datos['tipo'],
            'destino' => $ruta['cola'] ?? null,
            'estado' => $mensaje->estado_procesamiento,
        ]);

        return response()->json(['data' => $this->respuesta($mensaje)], $ruta === null ? 200 : 202);
    }

    public function show(Request $request, MensajeEntrega $mensaje): JsonResponse
    {
        if ($mensaje->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['data' => $this->respuesta($mensaje)]);
    }

    /** @return array<string, mixed> */
    private function respuesta(MensajeEntrega $mensaje): array
    {
        return [
            'id' => $mensaje->id,
            'message_id' => $mensaje->message_id,
            'correlation_id' => $mensaje->correlation_id,
            'tipo' => $mensaje->tipo,
            'prioridad' => $mensaje->prioridad,
            'destino' => $mensaje->destino,
            'estado' => $mensaje->estado_procesamiento,
            'resultado' => $mensaje->resultado,
            'procesado_en' => $mensaje->procesado_en?->toIso8601String(),
        ];
    }
}
