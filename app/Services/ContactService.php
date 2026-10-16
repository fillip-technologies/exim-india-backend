<?php

namespace App\Services;

use App\Mail\ContactReceived;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function store(array $data, ?string $ip = null): Contact
    {
        $contact = Contact::create([
            ...$data,
            'status' => 'new',
            'ip_address' => $ip,
        ]);

        $to = config('mail.contact_notify_to');
        if ($to) {
            Mail::to($to)->queue(new ContactReceived($contact));
        }

        return $contact;
    }
}
