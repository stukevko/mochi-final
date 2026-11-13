<?php

namespace App\Mail;

use App\Models\Setting;
use App\Support\InvoicePdf;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->load(['items' => fn ($q) => $q->orderBy('id')]);
    }

    public function envelope(): Envelope
    {
        $shopName = (string) Setting::get('shop_name', config('app.name', 'Shop'));

        return new Envelope(
            subject: $shopName.' - Bestellbestätigung '.$this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.confirmed',
        );
    }

    public function attachments(): array
    {
        $order = $this->order->loadMissing('items');

        // Rechnung nur für bezahlte Bestellungen anhängen (eine Rechnung braucht eine vergebene Nummer).
        if (! $order->isPaid()) {
            return [];
        }

        $pdf = InvoicePdf::output($order);

        return [
            Attachment::fromData(fn () => $pdf, 'rechnung-'.$order->order_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
