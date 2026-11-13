<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SafeHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppShellAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_manifest_is_valid_and_installable(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk();
        $this->assertStringContainsString('application/manifest+json', (string) $response->headers->get('Content-Type'));
        $response->assertJsonPath('display', 'standalone');
        $response->assertJsonPath('scope', '/');
        $this->assertNotEmpty($response->json('icons'));
    }

    public function test_admin_manifest_is_scoped_to_admin(): void
    {
        $this->get('/admin-manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('scope', '/admin')
            ->assertJsonPath('start_url', '/admin');
    }

    public function test_offline_page_renders_without_build_assets(): void
    {
        $this->get('/offline')
            ->assertOk()
            ->assertSee('Du bist gerade offline')
            ->assertDontSee('/build/', false);
    }

    public function test_layouts_include_app_shell(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('viewport-fit=cover', false)
            ->assertSee('rel="manifest"', false)
            ->assertSee('app-tabbar', false);
    }

    public function test_product_can_be_added_to_cart_from_grid_but_not_beyond_stock(): void
    {
        $product = \App\Models\Product::query()->create([
            'name' => 'Booster', 'slug' => 'booster-grid', 'price' => 5, 'stock' => 1, 'is_active' => true,
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Shop\ProductGrid::class)
            ->assertSee('In den Warenkorb')
            ->assertSee('Nur noch 1 da')
            ->call('addToCart', $product->id)
            ->assertDispatched('cartUpdated');

        $this->assertSame(1, app(\App\Services\CartService::class)->getTotalLineQuantity());

        // Zweites Exemplar gibt es nicht → Menge bleibt 1.
        $component->call('addToCart', $product->id);
        $this->assertSame(1, app(\App\Services\CartService::class)->getTotalLineQuantity());
    }

    public function test_mobile_more_menu_and_buy_bars_are_rendered(): void
    {
        $this->get('/shop')->assertOk()->assertSee('app-sheet', false)->assertSee('Kontakt &amp; Anfahrt', false);
        $this->get('/')->assertOk()->assertSee('Zum Shop');
    }

    public function test_checkout_pages_are_never_cached(): void
    {
        $cacheControl = (string) $this->get('/cart')->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_robots_hides_admin_and_checkout(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /checkout');
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
    }

    public function test_safe_html_strips_scripts_and_event_handlers(): void
    {
        $dirty = '<p class="lead" onclick="alert(1)">Hallo <a href="javascript:alert(1)">x</a></p><script>alert(1)</script><img src="/a.png" onerror="alert(1)">';

        $clean = SafeHtml::clean($dirty);

        $this->assertStringContainsString('Hallo', $clean);
        $this->assertStringContainsString('class="lead"', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
    }

    public function test_user_without_role_is_not_admin(): void
    {
        $user = new User(['name' => 'x', 'email' => 'x@example.com']);
        $user->role = null;

        $this->assertFalse($user->isAdmin());

        $user->role = 'admin';
        $this->assertTrue($user->isAdmin());
    }

    public function test_new_users_default_to_customer_role_in_database(): void
    {
        // Ohne explizite Rolle (z. B. künftige Registrierung) greift der DB-Default.
        $id = DB::table('users')->insertGetId([
            'name' => 'Neu',
            'email' => 'neu@example.com',
            'password' => bcrypt('irrelevant-123'),
        ]);

        $this->assertSame('customer', User::query()->findOrFail($id)->role);
    }

    public function test_two_factor_flag_is_read_from_config(): void
    {
        config(['filament.require_2fa' => true]);

        $this->assertTrue((bool) config('filament.require_2fa'));
        $this->assertStringNotContainsString(
            "env('FILAMENT_REQUIRE_2FA'",
            (string) file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php')),
        );
    }
}
