<?php

namespace App\Support;

use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmed;
use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Throwable;

final class ShopViewIntegrity
{
    /**
     * Blade-Views, deren Fehlen zu 500-Fehlern in Produktion führt.
     *
     * @return list<string>
     */
    public static function criticalBladeViews(): array
    {
        return [
            'home',
            'layouts.app',
            'components.layouts.app',
            'sitemap',
            'pages.shop',
            'pages.cart',
            'pages.checkout',
            'pages.checkout-success',
            'pages.product',
            'pages.contact',
            'pages.about',
            'pages.service',
            'pages.storefront-maintenance',
            'pages.legal-impressum',
            'pages.legal-agb',
            'pages.legal-datenschutz',
            'pages.legal-widerruf',
            'posts.index',
            'posts.show',
            'events.index',
            'events.show',
            'events.calendar',
            'livewire.shop.checkout-page',
            'livewire.shop.product-grid',
            'livewire.shop.product-detail',
            'livewire.shop.cart-page',
            'livewire.shop.cart-drawer',
            'livewire.shop.cart-icon',
            'livewire.shop.order-success',
            'livewire.contact-form',
            'emails.contact.notify',
            'emails.orders.confirmed',
            'emails.orders.shipped',
            'emails.orders.admin-notification',
            'pdf.invoice',
            'pdf.merchant-handbook',
            'pdf.client-manual',
            'errors.404',
            'errors.500',
            'errors.503',
            'filament.site-settings.hero-renner-preview',
            'filament.pages.payment-setup-env-status',
            'filament.components.payment-setup-intro',
        ];
    }

    /**
     * @return list<array{view: string, ok: bool, detail: string}>
     */
    public static function checkBladeViews(): array
    {
        $rows = [];

        foreach (self::criticalBladeViews() as $view) {
            $exists = View::exists($view);
            $rows[] = [
                'view' => $view,
                'ok' => $exists,
                'detail' => $exists ? 'vorhanden' : 'FEHLT',
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{surface: string, ok: bool, detail: string}>
     */
    public static function checkRenderableSurfaces(): array
    {
        $rows = [];
        $order = self::sampleOrder();

        foreach ([
            'pdf.invoice' => ['order' => $order],
            'pdf.merchant-handbook' => [
                'generatedAt' => now()->format('d.m.Y H:i'),
                'appName' => (string) config('app.name', 'Shop'),
            ],
            'pdf.client-manual' => [
                'generatedAt' => now()->format('d.m.Y H:i'),
            ],
        ] as $view => $data) {
            $rows[] = self::trySurface("PDF: {$view}", function () use ($view, $data): void {
                $output = Pdf::loadView($view, $data)->output();
                if (! is_string($output) || strlen($output) < 500) {
                    throw new \RuntimeException('PDF-Ausgabe zu klein oder leer');
                }
            });
        }

        foreach ([
            OrderConfirmed::class => fn () => (new OrderConfirmed($order))->render(),
            OrderShipped::class => fn () => (new OrderShipped($order))->render(),
            AdminOrderNotification::class => fn () => (new AdminOrderNotification($order))->render(),
        ] as $label => $callback) {
            $short = class_basename($label);
            $rows[] = self::trySurface("Mail: {$short}", $callback);
        }

        $rows[] = self::trySurface('Sitemap XML', function () use ($order): void {
            $html = view('sitemap', [
                'entries' => [
                    ['loc' => url('/'), 'lastmod' => now()],
                ],
            ])->render();

            if (! Str::contains($html, '<urlset')) {
                throw new \RuntimeException('Sitemap enthält kein urlset');
            }
        });

        return $rows;
    }

    public static function allPass(): bool
    {
        foreach (self::checkBladeViews() as $row) {
            if (! $row['ok']) {
                return false;
            }
        }

        foreach (self::checkRenderableSurfaces() as $row) {
            if (! $row['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{surface: string, ok: bool, detail: string}
     */
    private static function trySurface(string $surface, callable $callback): array
    {
        try {
            $callback();

            return ['surface' => $surface, 'ok' => true, 'detail' => 'OK'];
        } catch (Throwable $e) {
            return ['surface' => $surface, 'ok' => false, 'detail' => Str::limit($e->getMessage(), 120)];
        }
    }

    public static function sampleOrder(): Order
    {
        $order = new Order;
        $order->forceFill([
            'order_number' => 'ORD-TEST-0001',
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'sumup',
            'subtotal' => 10.08,
            'tax' => 1.92,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 12.00,
            'currency' => 'EUR',
            'shipping_carrier' => 'dhl',
            'tracking_number' => '1234567890',
            'billing_address' => [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max@example.test',
                'street' => 'Musterstraße 1',
                'zip' => '67346',
                'city' => 'Speyer',
                'country' => 'DE',
            ],
            'shipping_address' => [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max@example.test',
                'street' => 'Musterstraße 1',
                'zip' => '67346',
                'city' => 'Speyer',
                'country' => 'DE',
            ],
            'notes' => null,
        ]);
        $order->id = 1;
        $order->created_at = now();
        $order->setRelation('items', collect([
            new OrderItem([
                'product_name' => 'Testprodukt',
                'variant_name' => 'Standard',
                'sku' => 'TEST-001',
                'quantity' => 1,
                'unit_price' => 12.00,
                'total_price' => 12.00,
            ]),
        ]));

        return $order;
    }
}
