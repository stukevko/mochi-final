<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Throwable;
use UnitEnum;

/**
 * Händlerfreundliche Shop-Daten ohne Schlüssel-Wert-Editoren.
 */
class ManageMerchantSettings extends Page
{
    use CanUseDatabaseTransactions;

    public ?array $data = [];

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string | UnitEnum | null $navigationGroup = '⚙️ Konfiguration';

    protected static ?string $navigationLabel = 'Global Settings';

    protected static ?int $navigationSort = 100;

    public function mount(): void
    {
        $this->fillForm();
    }

    public function getTitle(): string
    {
        return 'Global Settings';
    }

    protected function fillForm(): void
    {
        $defaults = [
            'shop_name' => '',
            'shop_email' => '',
            'shop_phone' => '',
            'shop_address' => '',
            'social_instagram' => '',
            'social_facebook' => '',
            'social_x' => '',
            'footer_text' => '',
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => '19',
            'invoice_prefix' => 'INV-',
            'order_notification_email' => '',
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
                Tabs::make('Verwaltung')
                    ->tabs([
                        Tab::make('Shop-Infos')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Section::make('Sichtbarkeit für Kund:innen')
                                    ->description('Diese Angaben werden im Shop, auf der Service-Seite und üblicherweise auf der PDF-Rechnung verwendet.')
                                    ->schema([
                                        Forms\Components\TextInput::make('shop_name')
                                            ->label('Shop- / Firmenname')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText('Erscheint in der Kopfzeile, im Footer und auf der Rechnung.'),
                                        Forms\Components\TextInput::make('shop_email')
                                            ->label('Öffentliche E-Mail')
                                            ->email()
                                            ->required()
                                            ->helperText('Kontaktadresse, die Kund:innen sehen (z. B. auf Impressum und Service).'),
                                        Forms\Components\TextInput::make('shop_phone')
                                            ->label('Telefon (optional)')
                                            ->tel()
                                            ->maxLength(64)
                                            ->helperText('Wird angezeigt, wenn Ihr Theme eine Telefonnummer vorsieht.'),
                                        Forms\Components\Textarea::make('shop_address')
                                            ->label('Adresse')
                                            ->rows(4)
                                            ->helperText('Vollständige Geschäftsadresse für Impressum und Dokumente.')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Section::make('Social Media & Footer')
                                    ->schema([
                                        Forms\Components\TextInput::make('social_instagram')
                                            ->label('Instagram')
                                            ->url()
                                            ->maxLength(500)
                                            ->helperText('Vollständige Profil-URL, z. B. https://instagram.com/ihrshop'),
                                        Forms\Components\TextInput::make('social_facebook')
                                            ->label('Facebook')
                                            ->url()
                                            ->maxLength(500),
                                        Forms\Components\TextInput::make('social_x')
                                            ->label('X (Twitter)')
                                            ->url()
                                            ->maxLength(500),
                                        Forms\Components\Textarea::make('footer_text')
                                            ->label('Footer-Text / Copyright')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->helperText('Kurzer Hinweis unterhalb der Seite, z. B. © und Rechtsform.')
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),

                        Tab::make('Buchhaltung')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Section::make('Währung & Steuern')
                                    ->description('Werte für Preisanzeige und Rechnungs-Export.')
                                    ->schema([
                                        Forms\Components\TextInput::make('currency')
                                            ->label('Währungscode')
                                            ->required()
                                            ->maxLength(3)
                                            ->helperText('ISO-Code in Großbuchstaben (z. B. EUR, CHF) — wird an Zahlungs-APIs und Exporte übergeben.'),
                                        Forms\Components\TextInput::make('currency_symbol')
                                            ->label('Währungssymbol')
                                            ->required()
                                            ->maxLength(5)
                                            ->helperText('Symbol neben Beträgen im Shop (z. B. €, CHF, $).'),
                                        Forms\Components\TextInput::make('tax_rate')
                                            ->label('Standard-Mehrwertsteuer')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(99)
                                            ->step('0.01')
                                            ->suffix('%')
                                            ->required()
                                            ->helperText('Prozentsatz für neue Berechnungen; prüfen Sie regionale Vorgaben.'),
                                        Forms\Components\TextInput::make('invoice_prefix')
                                            ->label('Rechnungs-Präfix')
                                            ->required()
                                            ->maxLength(20)
                                            ->helperText('Vor der fortlaufenden Nummer, z. B. RE- oder INV-.'),
                                    ])->columns(2),
                            ]),

                        Tab::make('Benachrichtigungen')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Section::make('Neue Bestellungen')
                                    ->description('Wer im Team soll informiert werden, sobald eine Bestellung eingeht?')
                                    ->schema([
                                        Forms\Components\TextInput::make('order_notification_email')
                                            ->label('E-Mail für Bestellbenachrichtigungen')
                                            ->email()
                                            ->helperText('Leer lassen, wenn dieselbe Adresse wie „Öffentliche E-Mail“ genutzt werden soll.')
                                            ->nullable(),
                                    ])->columns(1),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            $definitions = [
                'shop_name' => ['type' => 'text', 'group' => 'general'],
                'shop_email' => ['type' => 'text', 'group' => 'general'],
                'shop_phone' => ['type' => 'text', 'group' => 'general'],
                'shop_address' => ['type' => 'textarea', 'group' => 'general'],
                'social_instagram' => ['type' => 'text', 'group' => 'general'],
                'social_facebook' => ['type' => 'text', 'group' => 'general'],
                'social_x' => ['type' => 'text', 'group' => 'general'],
                'footer_text' => ['type' => 'text', 'group' => 'theme'],
                'currency' => ['type' => 'text', 'group' => 'general'],
                'currency_symbol' => ['type' => 'text', 'group' => 'general'],
                'tax_rate' => ['type' => 'number', 'group' => 'general'],
                'invoice_prefix' => ['type' => 'text', 'group' => 'general'],
                'order_notification_email' => ['type' => 'text', 'group' => 'email'],
            ];

            foreach ($definitions as $key => $meta) {
                $raw = $data[$key] ?? null;
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
            ->title('Gespeichert')
            ->body('Ihre Shop-Daten wurden übernommen.')
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
