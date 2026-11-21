<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcesarResumenEntrega;
use App\Models\DireccionEntrega;
use App\Models\SolicitudResumenEntrega;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SolicitudResumenEntregaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'direccion_id' => ['required', 'integer', 'exists:direccion_entregas,id'],
        ]);

        $direccion = DireccionEntrega::findOrFail($datos['direccion_id']);
        Gate::authorize('view', $direccion);

        if ($direccion->lat === null || $direccion->lng === null) {
            return response()->json(['mensaje' => 'La dirección necesita coordenadas para consultar la entrega.'], 422);
        }

        $correlationId = $request->attributes->get('correlation_id');
        $messageId = (string) Str::uuid();
        $mensaje = [
            'message_id' => $messageId,
            'correlation_id' => $correlationId,
            'tipo' => 'resumen_entrega_solicitado',
            'ocurrido_en' => now()->toIso8601String(),
            'payload' => ['direccion_id' => $direccion->id],
        ];

        $solicitud = DB::transaction(function () use ($request, $direccion, $messageId, $correlationId, $mensaje): SolicitudResumenEntrega {
            $solicitud = SolicitudResumenEntrega::create([
                'message_id' => $messageId,
                'correlation_id' => $correlationId,
                'user_id' => $request->user()->id,
                'direccion_entrega_id' => $direccion->id,
                'estado' => 'pendiente',
                'mensaje' => $mensaje,
            ]);

            ProcesarResumenEntrega::dispatch($solicitud->id, $mensaje)
                ->onConnection('database')->onQueue('resumenes-entrega');

            return $solicitud;
        });

        Log::info('Resumen de entrega publicado en cola', [
            'message_id' => $messageId,
            'correlation_id' => $correlationId,
            'solicitud_id' => $solicitud->id,
            'estado' => 'pendiente',
        ]);

        return response()->json(['data' => [
            'solicitud_id' => $solicitud->id,
            'message_id' => $messageId,
            'correlation_id' => $correlationId,
            'estado' => 'pendiente',
        ]], 202);
    }

    public function show(Request $request, SolicitudResumenEntrega $solicitud): JsonResponse
    {
        if ($solicitud->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['data' => [
            'solicitud_id' => $solicitud->id,
            'message_id' => $solicitud->message_id,
            'correlation_id' => $solicitud->correlation_id,
            'estado' => $solicitud->estado,
            'resultado' => $solicitud->resultado,
            'procesado_en' => $solicitud->procesado_en?->toIso8601String(),
        ]]);
    }
}
