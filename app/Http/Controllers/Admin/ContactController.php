<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactStatusRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = Contact::query()
            ->with('product:id,name,slug')
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(25);

        return ContactResource::collection($contacts);
    }

    public function show(Contact $contact)
    {
        return new ContactResource($contact->load('product:id,name,slug'));
    }

    public function update(UpdateContactStatusRequest $request, Contact $contact)
    {
        $contact->update($request->validated());

        return new ContactResource($contact->load('product:id,name,slug'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->noContent();
    }
}
