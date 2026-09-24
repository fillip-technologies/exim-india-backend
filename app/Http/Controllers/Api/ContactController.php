<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use App\Services\ContactService;

class ContactController extends Controller
{
    public function __construct(private ContactService $contacts) {}

    public function store(StoreContactRequest $request)
    {
        $this->contacts->store(Contact::TYPE_CONTACT, $request->contactData(), $request->ip());

        return response()->json(['message' => 'Thank you! Your message has been received.'], 201);
    }
}
