<?php

namespace App\Services;

use App\Mail\ContactReceived;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function store(string $type, array $data, ?string $ip = null): Contact
    {
        $contact = Contact::create([
            ...$data,
            'type' => $type,
            'status' => 'new',
            'ip_address' => $ip,
        ]);

        $this->notify($contact);

        return $contact;
    }

    private function notify(Contact $contact): void
    {
        $to = config('mail.contact_notify_to');

        if (! $to) {
            return;
        }

        Mail::to($to)->queue(new ContactReceived($contact->load('product')));
    }
}
