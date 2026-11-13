<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AddressSuggestController;
use App\Http\Controllers\Admin\EventCalendarRescheduleController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\OrderInvoiceController;
use App\Http\Controllers\PaymentCheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WebAppManifestController;
use App\Models\Setting;
use App\Models\SiteSetting;
use App\Support\ProductSeo;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/events/calendar', [EventController::class, 'calendar'])->name('events.calendar');
Route::get('/events/feed', [EventController::class, 'feed'])->name('events.feed');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');

Route::get('/journal', [PostController::class, 'index'])->name('posts.index');
Route::get('/journal/{post}', [PostController::class, 'show'])->name('posts.show');

Route::get('/seite/{page}', [PageController::class, 'show'])->name('pages.show');

// App-Shell (installierbar als PWA)
Route::get('/manifest.webmanifest', [WebAppManifestController::class, 'storefront'])->name('pwa.manifest');
Route::get('/admin-manifest.webmanifest', [WebAppManifestController::class, 'admin'])->name('pwa.manifest.admin');
Route::view('/offline', 'pages.offline')->name('offline');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    $body = "User-agent: *\n"
        ."Disallow: /admin\n"
        ."Disallow: /cart\n"
        ."Disallow: /checkout\n"
        ."Disallow: /livewire\n"
        ."Disallow: /api/\n"
        ."\nSitemap: ".url('/sitemap.xml')."\n";

    return response($body, 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('robots');

Route::view('/shop', 'pages.shop')->name('shop');

Route::get('/product/{slug}', function (string $slug) {
    $product = \App\Models\Product::query()
        ->where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    return view('pages.product', [
        'slug' => $slug,
        'seo' => ProductSeo::layoutData($product),
    ]);
})->name('product.show');

// Seiten mit Warenkorb-/Kundendaten niemals in Browser-/Proxy-Caches (auch nicht im Service Worker).
Route::middleware('cache.headers:no_store;private')->group(function (): void {
    Route::view('/cart', 'pages.cart')->name('cart');
    Route::view('/checkout', 'pages.checkout')->name('checkout');
    Route::get('/checkout/success/{orderNumber}', fn (string $orderNumber) => view('pages.checkout-success', ['orderNumber' => $orderNumber]))
        ->middleware(['signed', 'throttle:30,1'])
        ->name('checkout.success');
    Route::get('/checkout/success/{orderNumber}/invoice', [OrderInvoiceController::class, 'forCheckoutSuccess'])
        ->middleware(['signed', 'throttle:20,1'])
        ->name('checkout.invoice');
    Route::get('/checkout/return/{provider}/{order}', [PaymentCheckoutController::class, 'returnFromProvider'])
        ->middleware(['signed', 'throttle:30,1'])
        ->whereIn('provider', ['sumup'])
        ->name('payment.return');
});

// SumUp-Webhook: Inhalt wird nicht vertraut, Status wird bei SumUp nachgefragt (siehe Controller).
Route::post('/webhooks/payment/sumup', [PaymentWebhookController::class, 'sumup'])
    ->middleware('throttle:120,1')
    ->name('webhooks.payment.sumup');

Route::get('/ueber-uns', AboutController::class)->name('about');

Route::get('/service', function () {
    return view('pages.service', [
        'shopContactEmail' => Setting::get('shop_email', ''),
    ]);
})->name('service');

Route::get('/kontakt', function () {
    $site = SiteSetting::current();
    $visitUrl = trim((string) ($site->hero_visit_store_url ?? ''));
    $mapsExternalUrl = $visitUrl !== ''
        ? $visitUrl
        : 'https://www.google.com/maps/search/?api=1&query='.urlencode('Maximilianstraße 42, 67346 Speyer');

    return view('pages.contact', [
        'shopAddress' => trim((string) Setting::get('shop_address', '')),
        'shopEmail' => trim((string) Setting::get('shop_email', '')),
        'openingHours' => trim(config('mochicards.contact_opening_hours')),
        'mapsEmbedUrl' => config('mochicards.contact_maps_embed_url'),
        'mapsExternalUrl' => $mapsExternalUrl,
    ]);
})->name('contact');

Route::get('/api/address-suggest', AddressSuggestController::class)
    ->middleware('throttle:15,1')
    ->name('api.address-suggest');

Route::view('/impressum', 'pages.legal-impressum')->name('legal.impressum');
Route::view('/agb', 'pages.legal-agb')->name('legal.agb');
Route::view('/datenschutz', 'pages.legal-datenschutz')->name('legal.datenschutz');
Route::view('/widerruf', 'pages.legal-widerruf')->name('legal.widerruf');

Route::middleware(['auth', 'admin', 'cache.headers:no_store;private'])->group(function (): void {
    Route::get('admin/orders/{order}/invoice', [OrderInvoiceController::class, 'forAdmin'])
        ->name('admin.orders.invoice');

    Route::post('admin-calendar/events/{id}/reschedule', EventCalendarRescheduleController::class)
        ->name('admin.calendar.event-reschedule');
});
