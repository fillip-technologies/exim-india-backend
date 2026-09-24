<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Contact $contact) {}

    public function envelope(): Envelope
    {
        $label = $this->contact->type === Contact::TYPE_ORDER ? 'Order inquiry' : 'Contact enquiry';

        return new Envelope(
            subject: "New {$label} from {$this->contact->name}",
            replyTo: [$this->contact->email],
        );
    }

    public function content(): Content
    {
        $c = $this->contact;
        $rows = [
            'Type' => $c->type,
            'Name' => $c->name,
            'Email' => $c->email,
            'Phone' => $c->phone,
            'Company' => $c->company,
            'Product interest' => $c->product_interest ?: $c->product?->name,
            'Quantity' => $c->quantity,
            'Address' => $c->address,
            'Message' => $c->message,
        ];

        $html = '';
        foreach (array_filter($rows) as $label => $value) {
            $html .= '<p><strong>'.e($label).':</strong> '.nl2br(e($value)).'</p>';
        }

        return new Content(htmlString: $html);
    }
}
