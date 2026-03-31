<?php

namespace App\Mail;

use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class OrderConfirmed extends Mailable implements ShouldQueue
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
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order])->output();

        return [
            Attachment::fromData(fn () => $pdf, 'rechnung-'.$order->order_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
