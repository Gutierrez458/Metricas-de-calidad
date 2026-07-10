<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    public function index()
    {
    $products = Product::with('image')->get();
    return response()->json($products, 200);
    }

    public function store(StoreProductRequest $request)
    {
    DB::beginTransaction();
    try {
        // Crear el producto
        $product = Product::create($request->only([
            'name',
            'description',
            'price',
            'stock'
        ]));

        // Guardar la imagen
        $path = $request->file('image')->store('products', 'public');

        // Crear relación polimórfica
        $product->image()->create([
            'path' => $path
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'data' => $product->load('image')
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        if (isset($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json([
            'message' => 'Ocurrió un error.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function show(string $id)
    {
        $product = Product::with('image')->findOrFail($id);
        return response()->json($product);
    }

public function update(UpdateProductRequest $request, string $id)
{
    DB::beginTransaction();
    try {
        $product = Product::with('image')->findOrFail($id);

        // Actualizar datos del producto
        $product->update($request->only([
            'name',
            'description',
            'price',
            'stock'
        ]));

        // ¿Se envió una imagen nueva?
        if ($request->hasFile('image')) {

            // Eliminar la imagen anterior del disco
            if ($product->image) {
                Storage::disk('public')->delete($product->image->path);
            }

            // Guardar la nueva imagen
            $path = $request->file('image')->store('products', 'public');

            // Si ya existía un registro de imagen, actualizarlo
            if ($product->image) {
                $product->image->update([
                    'path' => $path
                ]);
            } else {
                // Si no existía, crearlo
                $product->image()->create([
                    'path' => $path
                ]);
            }
        } DB::commit();

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data' => $product->fresh()->load('image')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Ocurrió un error al actualizar.',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function destroy(string $id)
{
    DB::beginTransaction();
    try {
        $product = Product::with('image')->findOrFail($id);

        // Si tiene imagen
        if ($product->image) {

            // Eliminar archivo físico
            Storage::disk('public')->delete($product->image->path);

            // Eliminar registro de la imagen
            $product->image->delete();
        }

        // Eliminar producto
        $product->delete();
        DB::commit();

        return response()->json([
            'message' => 'Producto eliminado correctamente.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Ocurrió un error al eliminar.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
