<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\InvoicePdf;
use App\Support\ShopErrorLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OrderInvoiceController extends Controller
{
    public function forAdmin(Order $order): StreamedResponse|RedirectResponse
    {
        return $this->respond($order, 'admin.orders.invoice');
    }

    public function forCheckoutSuccess(Request $request, string $orderNumber): StreamedResponse|RedirectResponse
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $flashedId = session('completed_order_id');
        if ($flashedId !== null && (int) $flashedId !== (int) $order->id) {
            abort(404);
        }

        return $this->respond($order, 'checkout.invoice');
    }

    private function respond(Order $order, string $surface): StreamedResponse|RedirectResponse
    {
        try {
            return InvoicePdf::downloadResponse($order);
        } catch (Throwable $e) {
            ShopErrorLogger::report('invoice.pdf_failed', $e, [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'surface' => $surface,
            ]);
            report($e);

            return redirect()
                ->back(fallback: route('checkout.success', ['orderNumber' => $order->order_number]))
                ->with('invoice_error', 'Die Rechnung konnte gerade nicht erstellt werden. Bitte später erneut versuchen oder uns kontaktieren.');
        }
    }
}
