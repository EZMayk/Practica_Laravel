<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ServicioEntregaNoDisponible extends RuntimeException
{
    public function __construct(public string $servicio, string $mensaje, ?Throwable $previous = null)
    {
        parent::__construct($mensaje, previous: $previous);
    }
}
