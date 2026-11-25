<?php

namespace App\Jobs;

class RevisarMensajeNoEnrutable extends ConsumirMensajeEntrega
{
    protected function destino(): string
    {
        return 'no-enrutables';
    }
}
