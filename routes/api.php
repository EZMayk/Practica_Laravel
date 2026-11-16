<?php

use App\Http\Controllers\Api\DireccionEntregaController;
use App\Http\Controllers\Api\EstadoController;
use App\Http\Controllers\Menu\CategoriasController;
use App\Http\Controllers\Menu\ProductosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/clientes/direcciones', [DireccionEntregaController::class, 'store']);

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
);

Route::get('/categorias', [CategoriasController::class, 'listarCategorias']
);

Route::get('/categorias/{id}', [CategoriasController::class, 'obtenerCategoria']
)->whereNumber('id');

Route::post('/categorias', [CategoriasController::class, 'crearCategoria']
);
