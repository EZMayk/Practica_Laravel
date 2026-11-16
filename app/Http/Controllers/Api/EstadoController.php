<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EstadoController extends Controller
{
    //
    public function index(): JsonResponse 
    {
    return response() -> json ([
        'success' => true,
        'message' => 'Api de prueba',
        'status' => 'abierto',
        'version' => '1.0.0',
    ], 200);

    }

}