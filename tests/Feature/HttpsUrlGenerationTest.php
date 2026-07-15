<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsUrlGenerationTest extends TestCase
{
    public function test_enforce_https_upgrades_storage_and_asset_base_urls(): void
    {
        config([
            'app.url' => 'https://mochi-cards.de',
            'filesystems.disks.public.url' => 'http://mochi-cards.de/storage',
            'app.asset_url' => 'http://mochi-cards.de',
        ]);

        AppServiceProvider::enforceHttpsAbsoluteUrls();

        $this->assertSame('https', parse_url(url('/shop'), PHP_URL_SCHEME));
        $this->assertSame('https://mochi-cards.de/storage', config('filesystems.disks.public.url'));
        $this->assertSame('https://mochi-cards.de', config('app.asset_url'));
    }

    public function test_force_https_middleware_redirects_insecure_requests(): void
    {
        config(['app.force_https' => true]);

        $response = $this->get('http://mochi-cards.de/shop');

        $response->assertRedirect('https://mochi-cards.de/shop');
    }

    public function test_url_generator_uses_https_after_force_scheme(): void
    {
        URL::forceScheme('https');
        config(['app.url' => 'https://mochi-cards.de']);

        $this->assertStringStartsWith('https://', url('/build/assets/app.js'));
        $this->assertStringStartsWith('https://', asset('images/mochi-logo-placeholder.png'));
    }
}
