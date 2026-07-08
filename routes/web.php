<?php

use App\Http\Controllers\AddressSuggestController;
use App\Http\Controllers\Admin\EventCalendarRescheduleController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentCheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
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

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    $body = "User-agent: *\nDisallow:\n\nSitemap: ".url('/sitemap.xml')."\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
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

Route::view('/cart', 'pages.cart')->name('cart');
Route::view('/checkout', 'pages.checkout')->name('checkout');
Route::get('/checkout/success/{orderNumber}', fn (string $orderNumber) => view('pages.checkout-success', ['orderNumber' => $orderNumber]))
    ->middleware('signed')
    ->name('checkout.success');
Route::get('/checkout/return/{provider}/{order}', [PaymentCheckoutController::class, 'returnFromProvider'])->name('payment.return');
Route::get('/checkout/cancel/{provider}/{order}', [PaymentCheckoutController::class, 'cancelFromProvider'])->name('payment.cancel');
Route::post('/webhooks/payment/stripe', [PaymentWebhookController::class, 'stripe'])->name('webhooks.payment.stripe');
Route::post('/webhooks/payment/paypal', [PaymentWebhookController::class, 'paypal'])->name('webhooks.payment.paypal');

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

Route::middleware(['web', 'auth', 'admin'])->group(function (): void {
    Route::post('admin-calendar/events/{id}/reschedule', EventCalendarRescheduleController::class)
        ->name('admin.calendar.event-reschedule');
});
