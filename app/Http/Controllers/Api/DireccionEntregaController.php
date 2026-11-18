<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDireccionCheckoutRequest;
use App\Http\Requests\StoreDireccionEntregaRequest;
use App\Http\Resources\DireccionEntregaResource;
use App\Models\DireccionEntrega;
use App\Services\NormalizadorDireccionEntrega;
use Illuminate\Http\JsonResponse;

class DireccionEntregaController extends Controller
{
    public function __construct(private readonly NormalizadorDireccionEntrega $normalizador) {}

    public function store(StoreDireccionEntregaRequest $request): JsonResponse
    {
        return $this->guardar($this->normalizador->desdeDirecciones($request->validated()));
    }

    public function storeDesdeCheckout(StoreDireccionCheckoutRequest $request): JsonResponse
    {
        return $this->guardar($this->normalizador->desdeCheckout($request->validated()));
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardar(array $datos): JsonResponse
    {
        $direccion = DireccionEntrega::create($datos)->refresh();

        return (new DireccionEntregaResource($direccion))->response()->setStatusCode(201);
    }
}
