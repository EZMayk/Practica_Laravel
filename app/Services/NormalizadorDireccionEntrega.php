<?php

namespace App\Services;

class NormalizadorDireccionEntrega
{
    /**
     * Recibe datos previamente validados por StoreDireccionEntregaRequest.
     *
     * @param  array{etiqueta: string, direccion: string, telefono?: ?string, observaciones?: ?string, referencia?: ?string, lat?: int|float|null, lng?: int|float|null}  $entrada
     * @return array{etiqueta: string, direccion: string, telefono: ?string, observaciones: ?string, referencia: ?string, lat: int|float|null, lng: int|float|null}
     */
    public function desdeDirecciones(array $entrada): array
    {
        return [
            'etiqueta' => $entrada['etiqueta'],
            'direccion' => $entrada['direccion'],
            'telefono' => $entrada['telefono'] ?? null,
            'observaciones' => $entrada['observaciones'] ?? null,
            'referencia' => $entrada['referencia'] ?? null,
            'lat' => $entrada['lat'] ?? null,
            'lng' => $entrada['lng'] ?? null,
        ];
    }

    /**
     * Recibe datos previamente validados por StoreDireccionCheckoutRequest.
     *
     * @param  array{direccion: array{etiqueta: string, direccion: string, telefono?: ?string, referencia?: ?string}, ubicacion: array{latitude: int|float, longitude: int|float}}  $entrada
     * @return array{etiqueta: string, direccion: string, telefono: ?string, observaciones: null, referencia: ?string, lat: int|float, lng: int|float}
     */
    public function desdeCheckout(array $entrada): array
    {
        $direccion = $entrada['direccion'];
        $ubicacion = $entrada['ubicacion'];

        return [
            'etiqueta' => $direccion['etiqueta'],
            'direccion' => $direccion['direccion'],
            'telefono' => $direccion['telefono'] ?? null,
            'observaciones' => null,
            'referencia' => $direccion['referencia'] ?? null,
            'lat' => $ubicacion['latitude'],
            'lng' => $ubicacion['longitude'],
        ];
    }
}
