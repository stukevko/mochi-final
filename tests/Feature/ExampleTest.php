<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_events_calendar_and_feed_are_reachable(): void
    {
        $this->get('/events/calendar')->assertOk();

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
