<?php

namespace App\Jobs;

class ProcesarResumenEnrutado extends ConsumirMensajeEntrega
{
    protected function destino(): string
    {
        return 'resumen-entrega';
    }
}
