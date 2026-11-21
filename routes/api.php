<?php

use App\Http\Controllers\Api\DireccionEntregaController;
use App\Http\Controllers\Api\EstadoController;
use App\Http\Controllers\Api\ResumenEntregaController;
use App\Http\Controllers\Menu\CategoriasController;
use App\Http\Controllers\Menu\ProductosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/v1/clientes/direcciones', [DireccionEntregaController::class, 'store']);
    Route::post('/v1/clientes/direcciones/checkout', [DireccionEntregaController::class, 'storeDesdeCheckout']);
    Route::get('/v1/clientes/direcciones/{direccion}/resumen-entrega', [ResumenEntregaController::class, 'show'])
        ->whereNumber('direccion');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/estado', [EstadoController::class, 'index']
);

Route::get('/productos', [ProductosController::class, 'listarProductos']
);

Route::get('/productos/{id}', [ProductosController::class, 'obtenerProducto']
)->whereNumber('id');

Route::post('/productos', [ProductosController::class, 'crearProducto']
)->middleware('auth:sanctum');

Route::get('/categorias', [CategoriasController::class, 'listarCategorias']
);

Route::get('/categorias/{id}', [CategoriasController::class, 'obtenerCategoria']
)->whereNumber('id');

Route::post('/categorias', [CategoriasController::class, 'crearCategoria']
)->middleware('auth:sanctum');
