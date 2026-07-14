<?php

namespace App\Support;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class InvoicePdf
{
    /**
     * @throws Throwable
     */
    public static function output(Order $order): string
    {
        $order->loadMissing('items');

        return Pdf::loadView('pdf.invoice', ['order' => $order])->output();
    }

    public static function filename(Order $order): string
    {
        return 'rechnung-'.$order->order_number.'.pdf';
    }

    /**
     * @throws Throwable
     */
    public static function downloadResponse(Order $order): StreamedResponse
    {
        $filename = self::filename($order);

        return response()->streamDownload(
            function () use ($order): void {
                echo self::output($order);
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
