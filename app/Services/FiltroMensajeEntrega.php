<?php

namespace App\Services;

use App\Models\DireccionEntrega;

class FiltroMensajeEntrega
{
    /** @param array{estado: string} $contenido */
    public function acepta(array $contenido, DireccionEntrega $direccion): bool
    {
        return $contenido['estado'] === 'activa' && $direccion->activa;
    }
}
