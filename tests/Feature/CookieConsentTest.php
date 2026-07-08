<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_includes_cookie_banner_without_optional_third_party_assets(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="mochi-cookie-banner"', false);
        $response->assertSee('Nur notwendige', false);
        $response->assertSee('id="mochi-consent-config"', false);
        $response->assertDontSee('href="https://fonts.bunny.net', false);
        $response->assertDontSee('href="https://fonts.googleapis.com', false);
        $response->assertDontSee('fonts.bunny.net/css', false);
    }

    public function test_contact_page_does_not_embed_google_maps_before_consent(): void
    {
        $response = $this->get('/kontakt');

        $response->assertOk();
        $response->assertSee('data-consent-map-root', false);
        $response->assertSee('data-consent-load-maps', false);
        $response->assertDontSee('src="https://maps.google.com', false);
    }

    public function test_admin_area_has_no_cookie_banner(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertDontSee('id="mochi-cookie-banner"', false);
    }
}
