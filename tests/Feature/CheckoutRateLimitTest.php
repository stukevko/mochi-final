<?php

namespace Tests\Feature;

use App\Livewire\Shop\CheckoutPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_order_rate_limits_even_when_turnstile_fails(): void
    {
        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret',
        ]);

        $this->app->detectEnvironment(fn () => 'production');

        $rateKey = 'checkout-place-order:'.sha1('127.0.0.1');
        RateLimiter::clear($rateKey);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(CheckoutPage::class)
                ->set('first_name', 'Max')
                ->set('last_name', 'Mustermann')
                ->set('email', 'max@example.test')
                ->set('street', 'Musterstraße 1')
                ->set('zip', '67346')
                ->set('city', 'Speyer')
                ->set('country', 'DE')
                ->set('accepted_legal', true)
                ->set('turnstileToken', '')
                ->call('placeOrder')
                ->assertHasErrors(['turnstileToken']);
        }

        Livewire::test(CheckoutPage::class)
            ->set('first_name', 'Max')
            ->set('last_name', 'Mustermann')
            ->set('email', 'max@example.test')
            ->set('street', 'Musterstraße 1')
            ->set('zip', '67346')
            ->set('city', 'Speyer')
            ->set('country', 'DE')
            ->set('accepted_legal', true)
            ->set('turnstileToken', '')
            ->call('placeOrder')
            ->assertHasErrors(['rate_limit']);
    }
}
