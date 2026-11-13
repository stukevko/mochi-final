<?php

namespace App\Filament\Pages;

use App\Support\RuntimeConfig;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

/**
 * Alles, was früher in der Datei .env stand, an einer Stelle im Admin:
 * SumUp, E-Mail-Postfach, Spam-Schutz, Anmeldesicherheit.
 */
class ManageTechnicalSettings extends Page
{
    public ?array $data = [];

    protected static ?string $slug = 'zugangsdaten';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = '⚙️ Konfiguration';

    protected static ?string $navigationLabel = 'Zugangsdaten & Technik';

    protected static ?int $navigationSort = 130;

    public function getTitle(): string
    {
        return 'Zugangsdaten & Technik';
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $saved = RuntimeConfig::values();

        // Geheimnisse werden nie zurück ins Formular geschrieben.
        $this->form->fill([
            'sumup_token' => '',
            'sumup_merchant_code' => $saved['sumup_merchant_code'] ?? (string) config('services.sumup.merchant_code', ''),
            'sumup_sales_sync' => (bool) config('services.sumup.sales_sync_enabled', true),
            'mail_host' => $saved['mail_host'] ?? (config('mail.default') === 'smtp' ? (string) config('mail.mailers.smtp.host', '') : ''),
            'mail_port' => $saved['mail_port'] ?? (string) (config('mail.mailers.smtp.port') ?: 587),
            'mail_username' => $saved['mail_username'] ?? (string) config('mail.mailers.smtp.username', ''),
            'mail_password' => '',
            'mail_from_address' => $saved['mail_from_address'] ?? (string) config('mail.from.address', ''),
            'mail_from_name' => $saved['mail_from_name'] ?? (string) config('mail.from.name', ''),
            'turnstile_site_key' => $saved['turnstile_site_key'] ?? (string) config('services.turnstile.site_key', ''),
            'turnstile_secret' => '',
            'require_2fa' => (bool) config('filament.require_2fa', false),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $stored = static fn (string $configKey): string => filled(config($configKey))
            ? 'Ist gespeichert. Leer lassen, um den Wert zu behalten.'
            : 'Noch nicht eingetragen.';

        return $schema->schema([
            Section::make('SumUp (Zahlung und Kasse)')
                ->description('Ohne diese beiden Angaben kann im Shop niemand bezahlen.')
                ->schema([
                    Forms\Components\TextInput::make('sumup_token')
                        ->label('API-Schlüssel')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->maxLength(255)
                        ->helperText('In SumUp: Einstellungen → Entwickler → API-Schlüssel. Der Schlüssel braucht auch die Berechtigung „sales.read“ für den Kassenabgleich. '.$stored('services.sumup.token')),
                    Forms\Components\TextInput::make('sumup_merchant_code')
                        ->label('Händler-Code')
                        ->maxLength(30)
                        ->helperText('Steht in SumUp im Profil, z. B. MC1A2B3C.'),
                    Forms\Components\Toggle::make('sumup_sales_sync')
                        ->label('Ladenverkäufe vom Shop-Bestand abziehen')
                        ->helperText('Ein Bestand für Laden und Shop. Der Abgleich selbst wird unter „Kasse & Shop (SumUp)“ gestartet.')
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('E-Mail-Postfach')
                ->description('Über dieses Postfach gehen Bestellbestätigungen, Rechnungen und Versandmails raus. Die Daten stehen im Kundenbereich deines Hosters unter „E-Mail“ (Postausgangsserver / SMTP).')
                ->schema([
                    Forms\Components\TextInput::make('mail_host')
                        ->label('Postausgangsserver (SMTP)')
                        ->placeholder('z. B. smtp.dein-hoster.de')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('mail_port')
                        ->label('Port')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(65535)
                        ->helperText('Meist 587. Bei „SSL“ 465.'),
                    Forms\Components\TextInput::make('mail_username')
                        ->label('Benutzername')
                        ->maxLength(255)
                        ->helperText('Meist die E-Mail-Adresse des Postfachs.'),
                    Forms\Components\TextInput::make('mail_password')
                        ->label('Passwort des Postfachs')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->maxLength(255)
                        ->helperText($stored('mail.mailers.smtp.password')),
                    Forms\Components\TextInput::make('mail_from_address')
                        ->label('Absender-Adresse')
                        ->email()
                        ->maxLength(255)
                        ->helperText('Muss zum Postfach gehören, sonst landen Mails im Spam.'),
                    Forms\Components\TextInput::make('mail_from_name')
                        ->label('Absender-Name')
                        ->maxLength(120)
                        ->placeholder('Mochi Cards'),
                ])->columns(2),

            Section::make('Spam-Schutz für Kasse und Kontaktformular')
                ->description('Optional. Kostenloser Dienst „Cloudflare Turnstile“ — beide Schlüssel gibt es nach der Anmeldung dort. Ohne Schlüssel bleiben die Formulare nutzbar, nur ohne zusätzliche Prüfung.')
                ->schema([
                    Forms\Components\TextInput::make('turnstile_site_key')
                        ->label('Websiteschlüssel (Site Key)')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('turnstile_secret')
                        ->label('Geheimer Schlüssel (Secret Key)')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->maxLength(255)
                        ->helperText($stored('services.turnstile.secret')),
                ])->columns(2)->collapsed(),

            Section::make('Anmeldung im Admin')
                ->schema([
                    Forms\Components\Toggle::make('require_2fa')
                        ->label('Passkey (Fingerabdruck / Gesicht / Geräte-PIN) für alle Admins vorschreiben')
                        ->helperText('Empfohlen, sobald der Shop live ist. Vorher im eigenen Profil einen Passkey einrichten — sonst wird er bei der nächsten Anmeldung verlangt.'),
                ]),

            Section::make('Automatische Aufgaben')
                ->description('Zahlungsabgleich und Kassenabgleich laufen von selbst.')
                ->schema([
                    Forms\Components\Placeholder::make('cron_hint')
                        ->label('Nur falls dein Hoster „Cronjob per URL“ anbietet')
                        ->content(filled(config('shop.cron_token'))
                            ? 'Diese Adresse alle 5 Minuten aufrufen lassen: '.route('cron.run', ['token' => config('shop.cron_token')])
                            : 'Nicht nötig — auf diesem Server läuft ein eigener Zeitplan.'),
                ])->collapsed(),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (RuntimeConfig::KEYS as $key) {
            $raw = $data[$key] ?? null;

            if (in_array($key, ['sumup_sales_sync', 'require_2fa'], true)) {
                RuntimeConfig::put($key, $raw ? '1' : '0');

                continue;
            }

            $value = is_scalar($raw) ? trim((string) $raw) : '';

            // Leeres Geheimnis-Feld = bisherigen Wert behalten.
            if (in_array($key, RuntimeConfig::SECRETS, true) && $value === '') {
                continue;
            }

            RuntimeConfig::put($key, $value);
        }

        RuntimeConfig::apply();
        $this->fillForm();

        Notification::make()->success()->title('Gespeichert')
            ->body('Die Einstellungen gelten ab sofort.')->send();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMail')
                ->label('Test-Mail an mich senden')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->action(function (): void {
                    $to = (string) (auth()->user()?->email ?? '');

                    if (config('mail.default') === 'log') {
                        Notification::make()->warning()->title('Noch kein Postfach eingetragen')
                            ->body('Bitte zuerst das E-Mail-Postfach ausfüllen und speichern.')->send();

                        return;
                    }

                    try {
                        Mail::raw('Diese Test-Mail kommt aus deinem Shop. Wenn du sie lesen kannst, funktioniert der E-Mail-Versand.', static function ($message) use ($to): void {
                            $message->to($to)->subject('Test-Mail aus deinem Shop');
                        });
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Versand fehlgeschlagen')
                            ->body('Der Postausgangsserver hat abgelehnt: '.mb_strimwidth($e->getMessage(), 0, 220, '…'))->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title('Test-Mail verschickt')
                        ->body('Schau in das Postfach von '.$to.' (auch im Spam-Ordner).')->send();
                }),

            Action::make('testSumUp')
                ->label('SumUp-Verbindung testen')
                ->icon('heroicon-o-credit-card')
                ->color('gray')
                ->action(function (): void {
                    $token = (string) config('services.sumup.token', '');
                    $merchant = (string) config('services.sumup.merchant_code', '');

                    if ($token === '' || $merchant === '') {
                        Notification::make()->warning()->title('SumUp ist noch nicht eingetragen')
                            ->body('Bitte API-Schlüssel und Händler-Code ausfüllen und speichern.')->send();

                        return;
                    }

                    try {
                        $response = Http::withToken($token)->acceptJson()
                            ->withUserAgent(config('app.name', 'Shop').' shop/1.0')
                            ->connectTimeout(5)->timeout(12)
                            ->get('https://api.sumup.com/v1/merchants/'.rawurlencode($merchant));
                    } catch (Throwable) {
                        Notification::make()->danger()->title('SumUp ist gerade nicht erreichbar')
                            ->body('Bitte in ein paar Minuten erneut versuchen.')->send();

                        return;
                    }

                    if ($response->successful()) {
                        Notification::make()->success()->title('SumUp ist verbunden')
                            ->body('Schlüssel und Händler-Code passen zusammen.')->send();

                        return;
                    }

                    Notification::make()->danger()->title('SumUp lehnt die Zugangsdaten ab')
                        ->body($response->status() === 401
                            ? 'Der API-Schlüssel ist ungültig oder abgelaufen.'
                            : 'Händler-Code und Schlüssel passen nicht zusammen (Antwort '.$response->status().').')
                        ->persistent()->send();
                }),
        ];
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
