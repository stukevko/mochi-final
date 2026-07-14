<?php

namespace App\Providers\Filament;

use AdriaanZon\FilamentPasskeys\FilamentPasskeysPlugin;
use AdriaanZon\FilamentPasskeys\PasskeyAuthentication;
use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\ConfigureFilamentSession;
use App\Http\Middleware\SetFilamentLocale;
use App\Models\Setting;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\BunnyFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\Table;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->paginationPageOptions([10, 25, 50, 100])
                ->defaultPaginationPageOption(10);
        });
    }

    public function panel(Panel $panel): Panel
    {
        $primaryHex = '#ff7a1f';
        if (Schema::hasTable('settings')) {
            $fromDb = Setting::get('primary_color', $primaryHex);
            $primaryHex = is_string($fromDb) && $fromDb !== '' ? $fromDb : $primaryHex;
            $primaryHex = str_starts_with($primaryHex, '#') ? $primaryHex : '#'.$primaryHex;
        }

        $requireTwoFactor = (bool) env('FILAMENT_REQUIRE_2FA', false);

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile()
            ->plugins([
                FilamentPasskeysPlugin::make(),
            ])
            ->multiFactorAuthentication(
                [
                    PasskeyAuthentication::make(),
                ],
                isRequired: $requireTwoFactor,
            )
            ->brandName('Mochi Hub')
            ->font(
                'Instrument Sans',
                'https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700',
                BunnyFontProvider::class,
            )
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultThemeMode(ThemeMode::Dark)
            ->darkMode(true, true)
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->navigationGroups([
                NavigationGroup::make()->label('🏠 Home')->collapsed(),
                NavigationGroup::make()->label('🛍️ Management'),
                NavigationGroup::make()->label('📰 Inhalte'),
                NavigationGroup::make()->label('⚙️ Konfiguration')->collapsed(),
            ])
            ->globalSearchKeyBindings([
                'command+k',
                'ctrl+k',
            ])
            ->globalSearchFieldKeyBindingSuffix()
            ->colors([
                'primary' => Color::hex($primaryHex),
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                SetFilamentLocale::class,
                ConfigureFilamentSession::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
