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
        return new Envelope(
            subject: "New contact enquiry from {$this->contact->name}",
            replyTo: [$this->contact->email],
        );
    }

    public function content(): Content
    {
        $c = $this->contact;

        return new Content(htmlString: self::rows([
            'Name' => $c->name,
            'Email' => $c->email,
            'Phone' => $c->phone,
            'Company' => $c->company,
            'Product interest' => $c->product_interest,
            'Message' => $c->message,
        ]));
    }

    public static function rows(array $rows): string
    {
        $html = '';
        foreach (array_filter($rows) as $label => $value) {
            $html .= '<p><strong>'.e($label).':</strong> '.nl2br(e($value)).'</p>';
        }

        return $html;
    }
}
