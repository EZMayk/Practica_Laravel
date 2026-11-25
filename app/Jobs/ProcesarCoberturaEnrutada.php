<?php

namespace App\Jobs;

class ProcesarCoberturaEnrutada extends ConsumirMensajeEntrega
{
    protected function destino(): string
    {
        return 'cobertura-entrega';
    }
}
