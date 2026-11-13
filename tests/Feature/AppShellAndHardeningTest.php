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

    public function test_technical_settings_from_admin_override_env_and_keep_secrets_encrypted(): void
    {
        config(['services.sumup.token' => null, 'mail.default' => 'log']);

        \App\Support\RuntimeConfig::put('sumup_token', 'sup_sk_geheim');
        \App\Support\RuntimeConfig::put('sumup_merchant_code', 'MC999');
        \App\Support\RuntimeConfig::put('mail_host', 'smtp.example.test');
        \App\Support\RuntimeConfig::put('mail_port', '465');
        \App\Support\RuntimeConfig::put('mail_password', 'postfach-geheim');
        \App\Support\RuntimeConfig::put('require_2fa', '1');
        \App\Support\RuntimeConfig::apply();

        $this->assertSame('sup_sk_geheim', config('services.sumup.token'));
        $this->assertSame('MC999', config('services.sumup.merchant_code'));
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('postfach-geheim', config('mail.mailers.smtp.password'));
        $this->assertTrue(config('filament.require_2fa'));

        // In der Datenbank steht das Geheimnis nie im Klartext.
        $raw = (string) DB::table('settings')->where('key', 'sumup_token')->value('value');
        $this->assertNotSame('', $raw);
        $this->assertStringNotContainsString('sup_sk_geheim', $raw);
    }

    public function test_admin_can_save_technical_settings_without_wiping_stored_secrets(): void
    {
        $admin = User::factory()->admin()->create();
        \App\Support\RuntimeConfig::put('sumup_token', 'alter-schluessel');

        $this->actingAs($admin)->get('/admin/zugangsdaten')
            ->assertOk()
            ->assertSee('E-Mail-Postfach')
            ->assertDontSee('alter-schluessel');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ManageTechnicalSettings::class)
            ->set('data.sumup_merchant_code', 'MC777')
            ->set('data.mail_host', 'smtp.example.test')
            ->set('data.sumup_token', '')
            ->call('save')
            ->assertHasNoErrors();

        $values = \App\Support\RuntimeConfig::values();
        $this->assertSame('alter-schluessel', $values['sumup_token']);
        $this->assertSame('MC777', $values['sumup_merchant_code']);
        $this->assertSame('smtp.example.test', $values['mail_host']);
    }

    public function test_forms_work_in_production_without_spam_protection_keys(): void
    {
        config(['services.turnstile.secret' => null]);
        $this->app->detectEnvironment(fn () => 'production');

        $this->assertTrue(\App\Services\TurnstileVerifier::verify('', '127.0.0.1'));
    }

    public function test_dashboard_shows_getting_started_checklist_and_simple_product_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Erste Schritte')
            ->assertSee('SumUp verbinden');

        $this->actingAs($admin)->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Stück auf Lager')
            ->assertDontSee('URL-Slug');

        $this->actingAs($admin)->get(\App\Filament\Pages\ManageSiteSettings::getUrl())
            ->assertOk()
            ->assertSee('Highlight-Karte: Produkt aus dem Shop');
    }

    public function test_product_gets_its_address_automatically(): void
    {
        $a = \App\Models\Product::query()->create(['name' => 'Booster Display', 'price' => 5, 'stock' => 1, 'is_active' => true]);
        $b = \App\Models\Product::query()->create(['name' => 'Booster Display', 'price' => 5, 'stock' => 1, 'is_active' => true]);

        $this->assertSame('booster-display', $a->slug);
        $this->assertSame('booster-display-2', $b->slug);
    }

    public function test_home_highlight_card_shows_a_real_product_automatically(): void
    {
        \App\Models\Product::query()->create(['name' => 'Altes Produkt', 'price' => 5, 'stock' => 1, 'is_active' => true]);
        \App\Models\Product::query()->create(['name' => 'Unser Highlight', 'price' => 12.5, 'stock' => 1, 'is_active' => true, 'is_featured' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Unser Highlight')
            ->assertDontSee('Event-Laufband');
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
