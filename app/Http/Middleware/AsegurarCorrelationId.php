<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AsegurarCorrelationId
{
    public const CABECERA = 'X-Correlation-ID';

    /**
     * Propaga el X-Correlation-ID recibido o genera uno nuevo, lo agrega al
     * contexto de logs y lo devuelve en la cabecera de la respuesta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header(self::CABECERA);

        if (! is_string($correlationId) || ! preg_match('/^[A-Za-z0-9._-]{1,64}$/', $correlationId)) {
            $correlationId = (string) Str::uuid();
        }

        $request->attributes->set('correlation_id', $correlationId);
        Log::withContext(['correlation_id' => $correlationId]);

        $response = $next($request);
        $response->headers->set(self::CABECERA, $correlationId);

        return $response;
    }
}
