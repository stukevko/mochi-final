<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EventCalendarPage;
use App\Filament\Pages\ManageAboutPage;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Posts\PostResource;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class AdminQuickLinksWidget extends Widget
{
    protected string $view = 'filament.widgets.admin-quick-links';

    /** Oberstes Dashboard-Element (nach 2FA direkt sichtbar). */
    protected static ?int $sort = -70;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{
     *     eventCreateUrl: string,
     *     siteSettingsUrl: string,
     *     aboutPageUrl: string,
     *     postCreateUrl: string,
     *     eventsIndexUrl: string,
     *     calendarPageUrl: string,
     *     profileUrl: ?string,
     * }
     */
    protected function getViewData(): array
    {
        return [
            'eventCreateUrl' => EventResource::getUrl('create'),
            'siteSettingsUrl' => ManageSiteSettings::getUrl(),
            'aboutPageUrl' => ManageAboutPage::getUrl(),
            'postCreateUrl' => PostResource::getUrl('create'),
            'eventsIndexUrl' => EventResource::getUrl('index'),
            'calendarPageUrl' => EventCalendarPage::getUrl(),
            'profileUrl' => Filament::getProfileUrl(),
        ];
    }
}
