<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\ShopTypography;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Throwable;
use UnitEnum;

/**
 * Sichtbare Marken-Optionen: Farbe, Schrift, Logo — ohne technische Key-Liste.
 */
class ManageBrandingDesign extends Page
{
    use CanUseDatabaseTransactions;

    public ?array $data = [];

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string | UnitEnum | null $navigationGroup = '⚙️ Konfiguration';

    protected static ?string $navigationLabel = 'Branding & Design';

    protected static ?int $navigationSort = 120;

    public function mount(): void
    {
        $this->fillForm();
    }

    public function getTitle(): string
    {
        return 'Branding & Design';
    }

    protected function fillForm(): void
    {
        $defaults = [
            'primary_color' => '#3b82f6',
            'font_family' => ShopTypography::DEFAULT_FAMILY,
            'logo_path' => null,
            'favicon_path' => null,
        ];

        $filled = [];
        foreach (array_keys($defaults) as $key) {
            $filled[$key] = Setting::get($key, $defaults[$key]);
        }

        $this->form->fill($filled);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Markenfarben')
                    ->description('Die Primärfarbe erscheint im öffentlichen Shop und bestimmt gleichzeitig die Akzentfarbe in diesem Admin-Bereich.')
                    ->schema([
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primärfarbe')
                            ->default('#3b82f6')
                            ->required(),
                    ])
                    ->columns(1),

                Section::make('Typografie')
                    ->schema([
                        Forms\Components\Select::make('font_family')
                            ->label('Schriftart')
                            ->options(array_combine(ShopTypography::selectOptions(), ShopTypography::selectOptions()))
                            ->native(false)
                            ->required()
                            ->helperText('Wird aus Google Fonts eingebunden (Gewichte 400–700). Gilt nur für den öffentlichen Shop.'),
                    ])
                    ->columns(1),

                Section::make('Logo & Favicon')
                    ->description('Laden Sie Dateien hoch — die Vorschau erscheint direkt unter dem jeweiligen Feld.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->imagePreviewHeight('140')
                            ->directory('settings')
                            ->disk('public')
                            ->visibility('public')
                            ->helperText('Empfohlen: PNG oder SVG mit transparentem Hintergrund, max. ca. 800 px Breite.'),
                        Forms\Components\FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->imagePreviewHeight('64')
                            ->directory('settings')
                            ->disk('public')
                            ->visibility('public')
                            ->helperText('Quadratisches Icon (z. B. 32×32 oder 64×64 px), ICO/PNG.'),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            $definitions = [
                'primary_color' => ['type' => 'color', 'group' => 'theme'],
                'font_family' => ['type' => 'string', 'group' => 'theme'],
                'logo_path' => ['type' => 'image', 'group' => 'theme'],
                'favicon_path' => ['type' => 'image', 'group' => 'theme'],
            ];

            foreach ($definitions as $key => $meta) {
                $raw = $data[$key] ?? null;
                if ($key === 'font_family' && is_string($raw)) {
                    $raw = ShopTypography::normalizeFamily($raw);
                }
                $value = $this->normalizeStoredValue($raw);

                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => $meta['type'],
                        'group' => $meta['group'],
                    ]
                );
            }
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        Notification::make()
            ->success()
            ->title('Branding gespeichert')
            ->body('Shop und Admin-Farben werden bei der nächsten Seite neu geladen.')
            ->send();
    }

    /**
     * @param  mixed  $raw
     */
    protected function normalizeStoredValue(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            $first = $raw[0] ?? null;

            return $first !== null && $first !== '' ? (string) $first : null;
        }

        return (string) $raw;
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

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->key('form-actions'),
                    ]),
            ]);
    }
}
