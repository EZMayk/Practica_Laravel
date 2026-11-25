<?php

namespace App\Services;

use App\Jobs\ProcesarCoberturaEnrutada;
use App\Jobs\ProcesarEstimacionEnrutada;
use App\Jobs\ProcesarResumenEnrutado;
use App\Jobs\RevisarMensajeNoEnrutable;

class EnrutadorMensajeEntrega
{
    /**
     * @param  array{tipo: string}  $contenido
     * @return array{cola: string, consumidor: class-string}
     */
    public function resolver(array $contenido): array
    {
        return match ($contenido['tipo']) {
            'cobertura' => ['cola' => 'cobertura-entrega', 'consumidor' => ProcesarCoberturaEnrutada::class],
            'estimacion' => ['cola' => 'estimacion-entrega', 'consumidor' => ProcesarEstimacionEnrutada::class],
            'resumen' => ['cola' => 'resumen-entrega', 'consumidor' => ProcesarResumenEnrutado::class],
            default => ['cola' => 'no-enrutables', 'consumidor' => RevisarMensajeNoEnrutable::class],
        };
    }
}
