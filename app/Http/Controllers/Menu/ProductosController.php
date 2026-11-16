<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductosController extends Controller
{
    private array $productos = [
        [
            'id' => 1,
            'categoria_id' => 1,
            'nombre' => 'Coca-Cola',
            'descripcion' => 'Refresco de cola',
            'precio' => '1.50',
            'estado' => true,
            'disponible' => true,
        ],
        [
            'id' => 2,
            'categoria_id' => 2,
            'nombre' => 'Hamburguesa con queso',
            'descripcion' => 'Hamburguesa con queso y lechuga',
            'precio' => '5.00',
            'estado' => true,
            'disponible' => true,
        ],
        [
            'id' => 3,
            'categoria_id' => 3,
            'nombre' => 'Batido de fresa',
            'descripcion' => 'Batido de fresa con leche',
            'precio' => '3.00',
            'estado' => true,
            'disponible' => true,
        ],
    ];

    public function listarProductos(): JsonResponse
    {
        return response()->json($this->productos, 200);
    }

    public function obtenerProducto(int $id): JsonResponse
    {
        foreach ($this->productos as $producto) {
            if ($producto['id'] === $id) {
                return response()->json($producto, 200);
            }
        }

        return response()->json([
            'mensaje' => 'Producto no encontrado',
        ], 404);
    }

    public function crearProducto(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'integer', 'in:1,2,3'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'precio' => ['required', 'numeric', 'min:0.01'],
        ]);

        $producto = [
            'categoria_id' => (int) $datos['categoria_id'],
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'precio' => number_format((float) $datos['precio'], 2, '.', ''),
            'estado' => true,
            'disponible' => true,
        ];

        return response()->json([
            'mensaje' => 'Datos válidos. El producto todavía no se ha guardado.',
            'producto' => $producto,
        ], 200);
    }
}