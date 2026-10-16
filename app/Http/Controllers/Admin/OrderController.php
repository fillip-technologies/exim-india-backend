<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with('product:id,name,slug', 'items')
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(max(1, min(100, (int) $request->query('per_page', 25))));

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load('product:id,name,slug', 'items'));
    }

    public function update(UpdateOrderStatusRequest $request, Order $order)
    {
        $order->update($request->validated());

        return new OrderResource($order->load('product:id,name,slug', 'items'));
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->noContent();
    }
}
