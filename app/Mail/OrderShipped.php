<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->load(['items' => fn ($q) => $q->orderBy('id')]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Versandbestätigung '.$this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.shipped',
        );
    }

    public function attachments(): array
    {
        $order = $this->order;

        return [
            Attachment::fromData(
                fn () => Pdf::loadView('pdf.invoice', ['order' => $order])->output(),
                'rechnung-'.$order->order_number.'.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
