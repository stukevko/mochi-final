<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Services\PaymentGatewayHealthService;
use App\Support\MoneyFormatter;
use Filament\Forms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationLabel = 'Zahlungen einrichten';

    protected static ?string $modelLabel = 'Zahlart';

    protected static ?string $pluralModelLabel = 'Zahlarten';
    
    protected static string | \UnitEnum | null $navigationGroup = '⚙️ Konfiguration';

    protected static ?int $navigationSort = 110;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Kurz erklärt')
                    ->schema([
                        SchemaView::make('filament.components.payment-setup-intro'),
                    ]),

                Section::make('Was Kundinnen und Kunden sehen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name in Kasse & E-Mails')
                            ->required()
                            ->maxLength(255)
                            ->helperText('z. B. „Karte (Stripe)“, „PayPal“, „Rechnung“.'),

                        Forms\Components\TextInput::make('code')
                            ->label('Interner Kurzname (Technik)')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->disabledOn('edit')
                            ->helperText('Kleinbuchstaben, z. B. stripe, paypal, sumup. Nach dem ersten Speichern nicht mehr ändern — sonst passt das Checkout nicht.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Hinweistext (optional)')
                            ->rows(2)
                            ->helperText('Ein Satz unter der Zahlart im Checkout, z. B. „Sie werden zu PayPal weitergeleitet.“')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Kunden können mit dieser Zahlart bezahlen')
                            ->default(false)
                            ->helperText('Erst aktivieren, wenn die Verbindungsprüfung geklappt hat oder Sie Vorkasse/Rechnung bewusst ohne API testen.'),

                        Forms\Components\Toggle::make('is_test_mode')
                            ->label('Übungs- / Testmodus')
                            ->default(true)
                            ->helperText('Ein = Sandbox-Keys und keine echten Abbuchungen (je nach Anbieter).'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Reihenfolge')
                            ->numeric()
                            ->default(0)
                            ->helperText('Niedrigere Zahl = weiter oben in der Kasse.'),
                    ])->columns(2),

                Section::make('Stripe: ein Schlüssel reicht meist')
                    ->visible(fn ($get) => strtolower((string) $get('code')) === 'stripe')
                    ->schema([
                        Forms\Components\TextInput::make('merchant_stripe_secret')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->maxLength(512)
                            ->helperText('Aus dem Stripe-Dashboard unter Entwickler → API-Schlüssel. Beginnt mit sk_test_ oder sk_live_. Wird verschlüsselt gespeichert.'),
                    ]),

                Section::make('PayPal: Client-ID und Secret')
                    ->visible(fn ($get) => strtolower((string) $get('code')) === 'paypal')
                    ->schema([
                        Forms\Components\TextInput::make('merchant_paypal_client_id')
                            ->label('Client ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('merchant_paypal_secret')
                            ->label('Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(512),
                    ])
                    ->columns(2)
                    ->description('Entwickler-Dashboard von PayPal: dort App anlegen und die beiden Werte kopieren.'),

                Section::make('SumUp')
                    ->visible(fn ($get) => strtolower((string) $get('code')) === 'sumup')
                    ->schema([
                        Forms\Components\TextInput::make('merchant_sumup_key')
                            ->label('API-Key / Token')
                            ->password()
                            ->revealable()
                            ->maxLength(512)
                            ->helperText('Wert aus dem SumUp-Händlerbereich (API).'),
                    ]),

                Section::make('Experten: Schlüssel/Wert-Tabelle')
                    ->description('Nur nötig für Sonderfälle oder wenn Ihre Agentur zusätzliche Felder braucht. Überschreibt nicht automatisch die Felder oben, sondern ergänzt das gespeicherte Konfigurations-JSON.')
                    ->schema([
                        Forms\Components\KeyValue::make('config')
                            ->label('Roh-Konfiguration')
                            ->keyLabel('Schlüssel')
                            ->valueLabel('Wert')
                            ->addActionLabel('Zeile hinzufügen')
                            ->helperText('Typisch z. B. secret_key, client_id, client_secret. Wird verschlüsselt gespeichert.')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),

                Section::make('Optional: Mindest- und Höchstbetrag')
                    ->schema([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Mindestbetrag')
                            ->numeric()
                            ->prefix((string) Setting::get('currency_symbol', '€'))
                            ->nullable()
                            ->helperText('Leer lassen = kein Minimum.'),

                        Forms\Components\TextInput::make('max_amount')
                            ->label('Höchstbetrag')
                            ->numeric()
                            ->prefix((string) Setting::get('currency_symbol', '€'))
                            ->nullable()
                            ->helperText('Leer lassen = kein Maximum.'),
                    ])->columns(2)
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('code')
                    ->label('Intern')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Im Checkout aktiv')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_test_mode')
                    ->label('Testbetrieb')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->falseIcon('heroicon-o-check-badge')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Min.')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                    ->placeholder('-'),
                    
                Tables\Columns\TextColumn::make('max_amount')
                    ->label('Max.')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                    ->placeholder('-'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
                    
                Tables\Filters\TernaryFilter::make('is_test_mode')
                    ->label('Modus')
                    ->placeholder('Alle')
                    ->trueLabel('Testmodus')
                    ->falseLabel('Live-Modus'),
            ])
            ->actions([
                Actions\Action::make('test_connection')
                    ->label('Verbindung prüfen')
                    ->icon('heroicon-o-signal')
                    ->action(function ($record) {
                        $result = app(PaymentGatewayHealthService::class)->check($record);

                        Notification::make()
                            ->title($result->ok ? 'Verbindung / Konfiguration' : 'Prüfung fehlgeschlagen')
                            ->body($result->message)
                            ->success($result->ok)
                            ->danger(! $result->ok)
                            ->send();
                    }),
                Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Im Checkout aus' : 'Im Checkout an')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                        
                    Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                        
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGateways::route('/'),
            'create' => Pages\CreatePaymentGateway::route('/create'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }
}
