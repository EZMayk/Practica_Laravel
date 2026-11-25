<?php

namespace App\Jobs;

class ProcesarEstimacionEnrutada extends ConsumirMensajeEntrega
{
    protected function destino(): string
    {
        return 'estimacion-entrega';
    }
}
