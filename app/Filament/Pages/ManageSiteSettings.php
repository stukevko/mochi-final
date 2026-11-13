<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Support\BenefitTileIcons;
use App\Support\FeaturedProductImageOptimizer;
use App\Support\SecureUrl;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

class ManageSiteSettings extends Page
{
    use CanUseDatabaseTransactions;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $slug = 'site-einstellungen';

    protected static ?string $navigationLabel = 'Startseite';

    protected static string|UnitEnum|null $navigationGroup = '📰 Inhalte';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Website-Einstellungen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getSetting()->attributesToArray());
    }

    protected function getSetting(): SiteSetting
    {
        return SiteSetting::current();
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $this->getSetting()->fill($data);
            $this->getSetting()->save();

            $this->commitDatabaseTransaction();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        Notification::make()
            ->success()
            ->title('Zack! Deine Änderungen sind live. ⚡')
            ->send();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getSetting())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $imageFields = [];
        foreach (range(1, 6) as $i) {
            $imageFields[] = FileUpload::make("footer_image_{$i}")
                ->label("Archiv-Bild {$i}")
                ->image()
                ->disk('public')
                ->directory('footer-carousel')
                ->imageEditor();
        }

        $benefitSections = [];
        foreach (range(1, 4) as $i) {
            $benefitSections[] = Section::make("Vorteil {$i}")
                ->schema([
                    Select::make("benefit_{$i}_icon")
                        ->label('Symbol')
                        ->options(BenefitTileIcons::options())
                        ->default(BenefitTileIcons::defaultForIndex($i - 1))
                        ->native(false),
                    TextInput::make("benefit_{$i}_title")
                        ->label('Kurztext')
                        ->maxLength(80)
                        ->placeholder('z. B. Blitzversand'),
                    TextInput::make("benefit_{$i}_body")
                        ->label('Zusatzzeile')
                        ->maxLength(120)
                        ->placeholder('z. B. In 24h bei dir (DE)')
                        ->columnSpanFull(),
                ])
                ->columns(2);
        }

        return $schema
            ->components([
                Section::make('Startseite')
                    ->description('Links steht die große Überschrift, rechts die Highlight-Karte.')
                    ->schema([
                        TextInput::make('hero_headline')
                            ->label('Große Überschrift')
                            ->maxLength(255)
                            ->placeholder('z. B. Dein Wohnzimmer für TCG in Speyer')
                            ->columnSpanFull(),
                        TextInput::make('hero_visit_store_url')
                            ->label('Link für „Besuch uns im Laden“')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull()
                            ->helperText('Link für „Besuch uns im Laden“ (z. B. Google Maps). Leer = Standard-Suche „Mochi Cards Speyer“ aus der Konfiguration.'),
                        Select::make('featured_product_id')
                            ->label('Highlight-Karte: Produkt aus dem Shop')
                            ->options(fn (): array => \App\Models\Product::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->limit(500)
                                ->pluck('name', 'id')
                                ->mapWithKeys(fn ($name, $id): array => [(string) $id => (string) $name])
                                ->all())
                            ->searchable()
                            ->placeholder('Automatisch (hervorgehobenes oder neuestes Produkt)')
                            ->helperText('Einfach ein Produkt wählen — Bild, Name, Preis und Link kommen von selbst. Das ist alles. Die Felder darunter braucht ihr nur für eine ganz eigene Karte (z. B. eine Aktion ohne Produkt).')
                            ->columnSpanFull(),
                        FileUpload::make('featured_product_image_path')
                            ->label('Eigene Karte · Bild')
                            ->image()
                            ->disk('public')
                            ->directory('site/featured')
                            ->imageEditor()
                            ->automaticallyResizeImagesMode('contain')
                            ->automaticallyResizeImagesToWidth('1400')
                            ->helperText(
                                'Große Fotos werden im Browser verkleinert; beim Speichern zusätzlich serverseitig auf max. '
                                .FeaturedProductImageOptimizer::MAX_WIDTH
                                .' px Breite optimiert (JPEG/WebP/PNG).',
                            )
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('featured_product_title')
                            ->label('Eigene Karte · Titel')
                            ->maxLength(255)
                            ->live(onBlur: true),
                        Textarea::make('featured_product_description')
                            ->label('Eigene Karte · Text (optional)')
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->helperText('Steht unter dem Bild.'),
                        TextInput::make('featured_product_price')
                            ->label('Eigene Karte · Preis (optional)')
                            ->maxLength(64)
                            ->placeholder('z. B. 49,99 €')
                            ->live(onBlur: true),
                        TextInput::make('featured_product_url')
                            ->label('Eigene Karte · Link (optional)')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->helperText('Wohin der Button führt. Leer = in den Shop.'),
                        ViewField::make('shop_renner_preview')
                            ->label('Vorschau der eigenen Karte')
                            ->dehydrated(false)
                            ->view('filament.site-settings.hero-renner-preview')
                            ->viewData(function (Get $get): array {
                                $path = $get('featured_product_image_path');
                                $imageUrl = null;
                                if (filled($path)) {
                                    $p = (string) $path;
                                    if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                                        $imageUrl = SecureUrl::upgrade($p);
                                    } elseif (Storage::disk('public')->exists($p)) {
                                        $imageUrl = SecureUrl::upgrade(Storage::disk('public')->url($p));
                                    } elseif (is_file(public_path($p))) {
                                        $imageUrl = SecureUrl::upgrade(asset($p));
                                    }
                                }

                                return [
                                    'title' => $get('featured_product_title') ?: 'Shop-Highlight',
                                    'price' => $get('featured_product_price'),
                                    'imageUrl' => $imageUrl,
                                ];
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Vier Vorteile (Icon-Leiste)')
                    ->description(
                        'Kompakte Leiste unter dem Hero — vier Kurztexte mit Symbol. Verfügbare Icon-Schlüssel '
                        .'(exakt so wählen): '.BenefitTileIcons::adminIconKeysHint().'.',
                    )
                    ->schema($benefitSections),
                Section::make('Weiteres & Archiv')
                    ->collapsed()
                    ->schema([
                        Toggle::make('background_animations')
                            ->label('Hintergrund-Animation (Glow & Pokéball-Lichter)')
                            ->helperText('Sanft animiert. Aus = statisch, schont Akku.')
                            ->default(true),
                        FileUpload::make('hero_logo_path')
                            ->label('Logo (optional über der Headline)')
                            ->image()
                            ->disk('public')
                            ->directory('site/hero')
                            ->imageEditor()
                            ->helperText('Ohne Upload wird das mitgelieferte Mochi-Cards-Logo verwendet.'),
                        FileUpload::make('hero_background_path')
                            ->label('Hero-Hintergrundbild (optional)')
                            ->image()
                            ->disk('public')
                            ->directory('site/hero')
                            ->imageEditor(),
                        TextInput::make('hero_subline')
                            ->label('Zusatztext (wird im neuen Layout nicht verwendet)')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('hero_sales_teaser')
                            ->label('Legacy: Sales-Teaser (wird nicht mehr angezeigt)')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('hero_learn_more_url')
                            ->label('Direktlink News-Übersicht (optional, für alte Bookmarks)')
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('instagram_url')
                            ->label('Instagram-URL')
                            ->url()
                            ->maxLength(2048)
                            ->helperText('Icon im Header & Footer. Leer = Wert aus .env / config.'),
                        TextInput::make('tiktok_url')
                            ->label('TikTok-URL')
                            ->url()
                            ->maxLength(2048)
                            ->helperText('Optional. Leer = MOCHI_TIKTOK_URL aus .env; wenn auch dort leer, kein TikTok-Icon.'),
                        Section::make('Instagram-Laufband (Archiv-Bilder)')
                            ->description('Diese Bilder erscheinen im scrollenden Feed ganz unten auf der Website.')
                            ->schema($imageFields)
                            ->columns(3)
                            ->collapsed(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment('start')
                            ->key('form-actions'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Speichern')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function hasFormWrapper(): bool
    {
        return true;
    }
}
