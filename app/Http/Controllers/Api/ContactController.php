<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Services\ContactService;

class ContactController extends Controller
{
    public function __construct(private ContactService $contacts) {}

    public function store(StoreContactRequest $request)
    {
        $this->contacts->store($request->contactData(), $request->ip());

        return response()->json(['message' => 'Thank you! Your message has been received.'], 201);
    }
}
