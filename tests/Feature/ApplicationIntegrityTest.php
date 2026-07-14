<?php

namespace Tests\Feature;

use App\Support\ShopViewIntegrity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_views_exist_and_render(): void
    {
        $this->assertTrue(
            ShopViewIntegrity::allPass(),
            'Kritische Views/PDFs/Mails fehlen oder sind nicht renderbar. Ausführen: php artisan shop:check-views'
        );
    }

    public function test_public_storefront_routes_are_reachable(): void
    {
        $routes = [
            '/',
            '/shop',
            '/cart',
            '/checkout',
            '/events',
            '/events/calendar',
            '/journal',
            '/kontakt',
            '/ueber-uns',
            '/service',
            '/impressum',
            '/agb',
            '/datenschutz',
            '/widerruf',
            '/sitemap.xml',
            '/robots.txt',
        ];

        foreach ($routes as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_events_feed_returns_json(): void
    {
        $feed = $this->getJson('/events/feed');
        $feed->assertOk();
        $this->assertIsArray($feed->json());
    }

    public function test_custom_404_page_is_shown(): void
    {
        $response = $this->get('/dies-gibt-es-nicht-'.uniqid());

        $response->assertNotFound();
        $response->assertSee('Diese Karte ist nicht im Deck', false);
    }
}
