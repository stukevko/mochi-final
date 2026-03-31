<?php

namespace App\Mail;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminOrderNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->load(['items' => fn ($q) => $q->orderBy('id')]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neue Bestellung '.$this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.admin-notification',
            with: [
                'adminUrl' => OrderResource::getUrl('edit', ['record' => $this->order]),
            ],
        );
    }
}
