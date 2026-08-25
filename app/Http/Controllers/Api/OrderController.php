<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('client:id,name')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ], 200);
    }

    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();

        try {
            $order = DB::transaction(function () use ($data) {
                $order = Order::create([
                    'client_id' => $data['client_id'],
                    'status' => $data['status'] ?? 'pending',
                    'total' => 0,
                ]);

                $total = 0;
                foreach ($data['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $unitPrice = $product->price;
                    $subtotal = round($unitPrice * $item['quantity'], 2);
                    $total += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);
                }

                $order->update(['total' => $total]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $order->load(['client:id,name', 'items.product:id,sku,name']),
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la orden',
            ], 500);
        }
    }

    public function show(Order $order)
    {
        return response()->json([
            'success' => true,
            'data' => $order->load(['client:id,name,email,phone', 'items.product:id,sku,name']),
        ], 200);
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        try {
            $order->update($request->validated());

            return response()->json([
                'success' => true,
                'data' => $order->fresh()->load(['client:id,name', 'items.product:id,sku,name']),
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar la orden',
            ], 500);
        }
    }

    public function destroy(Order $order)
    {
        try {
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Orden eliminada',
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar la orden',
            ], 500);
        }
    }
}
