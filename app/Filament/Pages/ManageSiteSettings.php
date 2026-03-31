<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Support\BenefitTileIcons;
use App\Support\FeaturedProductImageOptimizer;
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

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'site-einstellungen';

    protected static ?string $navigationLabel = 'Website-Einstellungen';

    protected static string|UnitEnum|null $navigationGroup = '⚙️ System & Technik';

    protected static ?int $navigationSort = 99;

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
                Section::make('Startseite · Das Wesentliche')
                    ->description('Hero links: Headline & Laden-CTA. Rechts: Shop-Renner mit Bild, Name, Preis und Link.')
                    ->schema([
                        TextInput::make('hero_headline')
                            ->label('Hero-Headline')
                            ->maxLength(255)
                            ->placeholder('z. B. Dein Wohnzimmer für TCG in Speyer')
                            ->columnSpanFull(),
                        TextInput::make('hero_visit_store_url')
                            ->label('Laden-CTA (Call to Action)')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull()
                            ->helperText('Link für „Besuch uns im Laden“ (z. B. Google Maps). Leer = Standard-Suche „Mochi Cards Speyer“ aus der Konfiguration.'),
                        TextInput::make('shop_cta_url')
                            ->label('Haupt-Link zum Shop')
                            ->url()
                            ->maxLength(2048)
                            ->helperText(
                                'Steuert die Shop-Links im Header (mobil & Desktop). '
                                .'Auf der Startseite: Basis für die Shop-Renner-Kachel nur, wenn „Direktlink zum Artikel“ unten leer ist — sonst nutzt der Button dort den Direktlink.',
                            ),
                        FileUpload::make('featured_product_image_path')
                            ->label('Shop-Renner · Bild')
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
                            ->label('Shop-Renner · Name')
                            ->maxLength(255)
                            ->live(onBlur: true),
                        Textarea::make('featured_product_description')
                            ->label('Shop-Renner · Beschreibung (optional)')
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->helperText('Wird unter dem Bild/Artwork angezeigt. Leer = Standardtext aus der Konfiguration (Community-Token-Story).'),
                        TextInput::make('featured_product_price')
                            ->label('Shop-Renner · Preis')
                            ->maxLength(64)
                            ->placeholder('z. B. 49,99 €')
                            ->live(onBlur: true),
                        TextInput::make('featured_product_url')
                            ->label('Shop-Renner · Direktlink zum Artikel')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->helperText(
                                'Wenn ausgefüllt: der Button „Zum Shop“ auf der großen Kachel führt genau hierhin (überschreibt den Haupt-Link). '
                                .'Wenn leer: Kachel nutzt den Haupt-Link oben bzw. den ersten Eintrag unter „Shop-Verbindung“.',
                            ),
                        ViewField::make('shop_renner_preview')
                            ->label('Vorschau Shop-Renner')
                            ->dehydrated(false)
                            ->view('filament.site-settings.hero-renner-preview')
                            ->viewData(function (Get $get): array {
                                $path = $get('featured_product_image_path');
                                $imageUrl = null;
                                if (filled($path)) {
                                    $p = (string) $path;
                                    if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                                        $imageUrl = $p;
                                    } elseif (Storage::disk('public')->exists($p)) {
                                        $imageUrl = Storage::disk('public')->url($p);
                                    } elseif (is_file(public_path($p))) {
                                        $imageUrl = asset($p);
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
                            ->imageEditor(),
                        FileUpload::make('hero_background_path')
                            ->label('Hero-Hintergrundbild (optional)')
                            ->image()
                            ->disk('public')
                            ->directory('site/hero')
                            ->imageEditor(),
                        TextInput::make('featured_product_id')
                            ->label('Interne Produkt-ID (nur Anzeige, optional)')
                            ->maxLength(255),
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
