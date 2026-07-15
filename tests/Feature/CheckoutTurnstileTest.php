<?php

namespace Tests\Feature;

use App\Livewire\Shop\CheckoutPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTurnstileTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_shows_turnstile_when_site_key_is_configured(): void
    {
        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret',
        ]);

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('data-consent-turnstile', false)
            ->assertSee('cf-turnstile', false)
            ->assertSee('Sicherheitsprüfung', false);
    }

    public function test_checkout_hides_turnstile_without_site_key(): void
    {
        config([
            'services.turnstile.site_key' => null,
            'services.turnstile.secret' => null,
        ]);

        $this->get('/checkout')
            ->assertOk()
            ->assertDontSee('cf-turnstile', false);
    }

    public function test_place_order_requires_turnstile_in_production_when_secret_configured(): void
    {
        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret',
        ]);

        $this->app->detectEnvironment(fn () => 'production');

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
}
