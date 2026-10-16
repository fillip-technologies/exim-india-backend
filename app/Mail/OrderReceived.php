<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New order inquiry from {$this->order->name}",
            replyTo: [$this->order->email],
        );
    }

    public function content(): Content
    {
        $o = $this->order;
        $items = $o->items->map(fn ($i) => trim($i->label_snapshot.' × '.$i->quantity.($i->note ? " ({$i->note})" : '')))->implode("\n");

        return new Content(htmlString: ContactReceived::rows([
            'Name' => $o->name,
            'Email' => $o->email,
            'Phone' => $o->phone,
            'Product' => $o->product?->name,
            'Quantity' => $o->quantity,
            'Items' => $items,
            'Address' => $o->address,
            'Message' => $o->message,
        ]));
    }
}
