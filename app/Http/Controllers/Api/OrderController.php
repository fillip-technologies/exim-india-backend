<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function store(StoreOrderRequest $request)
    {
        $this->orders->store($request->orderData(), $request->ip());

        return response()->json(['message' => 'Thank you! Your order inquiry has been received.'], 201);
    }
}
