<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Throwable;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ], 200);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $product = Product::create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $product,
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el producto',
            ], 500);
        }
    }

    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product,
        ], 200);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $product->update($request->validated());

            return response()->json([
                'success' => true,
                'data' => $product->fresh(),
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el producto',
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            // soft delete via is_active, no se borra el registro físicamente
            $product->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Producto desactivado',
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el producto',
            ], 500);
        }
    }
}
