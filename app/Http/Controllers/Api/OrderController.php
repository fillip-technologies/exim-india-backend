<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Contact;
use App\Services\ContactService;

class OrderController extends Controller
{
    public function __construct(private ContactService $contacts) {}

    public function store(StoreOrderRequest $request)
    {
        $this->contacts->store(Contact::TYPE_ORDER, $request->orderData(), $request->ip());

        return response()->json(['message' => 'Thank you! Your order inquiry has been received.'], 201);
    }
}
