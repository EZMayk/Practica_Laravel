<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoriasController extends Controller{
    private array $categorias = [
        [
            'id' => 1,
            'nombre' => 'Refrescos',
            'descripcion' => 'Refrescos de todo tipo',
            'estado' => true,
        ],
        [
            'id' => 2,
            'nombre' => 'Hamburguesas',
            'descripcion' => 'Hamburguesas de todo tipo',
            'estado' => true,
        ],
        [
            'id' => 3,
            'nombre' => 'Batidos',
            'descripcion' => 'Batidos de todo tipo',
            'estado' => true,
        ],
    ];

    public function listarCategorias(): JsonResponse{
        return response()->json($this->categorias, 200);
    }



     public function obtenerCategoria(int $id): JsonResponse
    {
        foreach ($this->categorias as $categoria) {
            if ($categoria['id'] === $id) {
                return response()->json($categoria, 200);
            }
        }

        return response()->json([
            'mensaje' => 'Categoría no encontrada',
        ], 404);
    } 


    
    public function crearCategoria(Request $request): JsonResponse{
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:255'],
        ]);

        $categoria = [
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'estado' => true,
        ];

        return response()->json([
            'mensaje' => 'Datos válidos. La categoría todavía no se ha guardado.',
            'categoria' => $categoria,
        ], 200);
    }
}
   