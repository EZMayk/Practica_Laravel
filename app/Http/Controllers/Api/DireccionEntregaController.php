<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDireccionEntregaRequest;
use App\Http\Resources\DireccionEntregaResource;
use App\Models\DireccionEntrega;
use Illuminate\Http\JsonResponse;

class DireccionEntregaController extends Controller
{
    public function store(StoreDireccionEntregaRequest $request): JsonResponse
    {
        $direccion = DireccionEntrega::create($request->validated())->refresh();

        return (new DireccionEntregaResource($direccion))->response()->setStatusCode(201);
    }
}
