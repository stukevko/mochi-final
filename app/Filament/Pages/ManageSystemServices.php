<?php

namespace App\Filament\Pages;

use App\Models\Setting;
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
 * Technische Schalter und Zahlungsarten — klar von Shop-Daten getrennt.
 */
class ManageSystemServices extends Page
{
    use CanUseDatabaseTransactions;

    public ?array $data = [];

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string | UnitEnum | null $navigationGroup = '⚙️ Konfiguration';

    protected static ?string $navigationLabel = 'System & Zahlung';

    protected static ?int $navigationSort = 140;

    public function mount(): void
    {
        $this->fillForm();
    }

    public function getTitle(): string
    {
        return 'System & Zahlung';
    }

    protected function fillForm(): void
    {
        $defaults = [
            'storefront_maintenance' => false,
            'prepayment_enabled' => false,
            'prepayment_bank_account_holder' => '',
            'prepayment_bank_name' => '',
            'prepayment_iban' => '',
            'prepayment_bic' => '',
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
                Section::make('Wartungsmodus (Shop)')
                    ->description('Nur der öffentliche Shop wird gesperrt — das Admin-Panel bleibt erreichbar.')
                    ->schema([
                        Forms\Components\Toggle::make('storefront_maintenance')
                            ->label('Shop zeigt Wartungshinweis')
                            ->helperText('Aktivieren, während Sie Inhalte umstellen. Kund:innen sehen eine freundliche Meldung statt des Katalogs.')
                            ->default(false),
                    ]),

                Section::make('E-Mail-Versand')
                    ->schema([
                        Forms\Components\Placeholder::make('mail_hint')
                            ->label('Hinweis')
                            ->content('Der Versand von System-E-Mails (Bestellungen, Passwort) läuft über die Server-.env: empfohlen lokaler Postfix (MAIL_MAILER=smtp, MAIL_HOST=127.0.0.1, Port 25, Absender noreply-mochi@nexvalue.de). Alternativ sendmail oder optional Resend (RESEND_API_KEY). SPF/DKIM für nexvalue.de setzen. Test: php artisan shop:test-mail.'),
                    ]),

                Section::make('Vorkasse / Überweisung')
                    ->schema([
                        Forms\Components\Toggle::make('prepayment_enabled')
                            ->label('Vorkasse im Checkout anbieten')
                            ->default(false),
                        Forms\Components\TextInput::make('prepayment_bank_account_holder')
                            ->label('Kontoinhaber')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('prepayment_bank_name')
                            ->label('Bankname')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('prepayment_iban')
                            ->label('IBAN')
                            ->maxLength(64),
                        Forms\Components\TextInput::make('prepayment_bic')
                            ->label('BIC')
                            ->maxLength(32),
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
                'storefront_maintenance' => ['type' => 'boolean', 'group' => 'general'],
                'prepayment_enabled' => ['type' => 'boolean', 'group' => 'payment'],
                'prepayment_bank_account_holder' => ['type' => 'text', 'group' => 'payment'],
                'prepayment_bank_name' => ['type' => 'text', 'group' => 'payment'],
                'prepayment_iban' => ['type' => 'text', 'group' => 'payment'],
                'prepayment_bic' => ['type' => 'text', 'group' => 'payment'],
            ];

            foreach ($definitions as $key => $meta) {
                $raw = $data[$key] ?? null;
                $value = $this->normalizeStoredValue($raw, $meta['type'] === 'boolean');

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
            ->body('System-Einstellungen wurden aktualisiert.')
            ->send();
    }

    /**
     * @param  mixed  $raw
     */
    protected function normalizeStoredValue(mixed $raw, bool $asBool = false): ?string
    {
        if ($asBool) {
            return ($raw === true || $raw === 1 || $raw === '1') ? '1' : '0';
        }

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
