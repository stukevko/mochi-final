<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Mail\OrderShipped;
use App\Models\Order;
use App\Support\MoneyFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $recordTitleAttribute = 'order_number';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Bestellungen';

    protected static ?string $modelLabel = 'Bestellung';

    protected static ?string $pluralModelLabel = 'Bestellungen';

    protected static string|\UnitEnum|null $navigationGroup = '🛍️ Management';

    protected static ?int $navigationSort = 20;

    protected static ?int $globalSearchSort = 2;

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'payment_id'];
    }

    public static function infolist(Schema $schema): Schema
    {
        $formatAddress = static function ($state) {
            if ($state === null || $state === '' || $state === []) {
                return '—';
            }

            if (is_string($state)) {
                $decoded = json_decode($state, true);
                if (is_array($decoded)) {
                    $state = $decoded;
                } else {
                    $trimmed = trim($state);

                    return $trimmed !== '' ? $trimmed : '—';
                }
            }

            if (! is_array($state)) {
                return '—';
            }

            $lines = array_filter([
                trim(($state['first_name'] ?? '').' '.($state['last_name'] ?? '')),
                $state['street'] ?? null,
                trim(($state['zip'] ?? '').' '.($state['city'] ?? '')),
                $state['country'] ?? null,
                $state['email'] ?? null,
            ]);

            return $lines === [] ? '—' : implode("\n", $lines);
        };

        return $schema
            ->columns(2)
            ->components([
                Section::make('Bestellinformationen')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Bestellnummer'),
                        TextEntry::make('user.name')
                            ->label('Kunde')
                            ->placeholder('Gastbestellung'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'pending' => 'Ausstehend',
                                'processing' => 'In Bearbeitung',
                                'shipped' => 'Versendet',
                                'delivered' => 'Zugestellt',
                                'cancelled' => 'Storniert',
                                'refunded' => 'Erstattet',
                                default => $state,
                            }),
                        TextEntry::make('payment_status')
                            ->label('Zahlungsstatus')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'pending' => 'Ausstehend',
                                'paid' => 'Bezahlt',
                                'failed' => 'Fehlgeschlagen',
                                'refunded' => 'Erstattet',
                                'cancelled' => 'Storniert',
                                default => $state,
                            }),
                        TextEntry::make('payment_method')
                            ->label('Zahlungsmethode')
                            ->placeholder('—'),
                        TextEntry::make('shipping_carrier')
                            ->label('Versanddienst')
                            ->formatStateUsing(fn ($state) => Order::shippingCarrierOptions()[$state] ?? '—'),
                        TextEntry::make('tracking_number')
                            ->label('Tracking')
                            ->placeholder('—'),
                        TextEntry::make('tracking_url')
                            ->label('Tracking-Link')
                            ->state(fn ($record) => $record->getTrackingUrl())
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Bestellt am')
                            ->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(2),
                Section::make('Beträge')
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label('Nettosumme')
                            ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                        TextEntry::make('shipping_cost')
                            ->label('Versandkosten')
                            ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                        TextEntry::make('tax')
                            ->label('MwSt.')
                            ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                        TextEntry::make('total')
                            ->label('Gesamtbetrag (Brutto)')
                            ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                    ])
                    ->columns(4),
                Section::make('Adressen')
                    ->schema([
                        TextEntry::make('shipping_address')
                            ->label('Lieferadresse')
                            ->formatStateUsing($formatAddress)
                            ->columnSpanFull(),
                        TextEntry::make('billing_address')
                            ->label('Rechnungsadresse')
                            ->formatStateUsing($formatAddress)
                            ->columnSpanFull(),
                    ]),
                Section::make('Bestellpositionen')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Produkt'),
                                TableColumn::make('Variante'),
                                TableColumn::make('SKU'),
                                TableColumn::make('Menge'),
                                TableColumn::make('Stückpreis'),
                                TableColumn::make('Summe'),
                            ])
                            ->schema([
                                TextEntry::make('product_name'),
                                TextEntry::make('variant_name')
                                    ->placeholder('—'),
                                TextEntry::make('sku')
                                    ->placeholder('—'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price')
                                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                                TextEntry::make('total_price')
                                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Notizen')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Interne Notizen')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make('Kunden-Historie')
                    ->description('Weitere Bestellungen desselben Accounts bzw. derselben E-Mail-Adresse.')
                    ->schema([
                        RepeatableEntry::make('customer_order_history')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record->priorOrdersForSameCustomer()->map(fn ($o) => [
                                'order_number' => $o->order_number,
                                'created_at' => $o->created_at,
                                'status' => $o->status,
                                'total' => $o->total,
                            ])->values()->all())
                            ->table([
                                TableColumn::make('Bestellnr.'),
                                TableColumn::make('Datum'),
                                TableColumn::make('Status'),
                                TableColumn::make('Summe'),
                            ])
                            ->schema([
                                TextEntry::make('order_number'),
                                TextEntry::make('created_at')
                                    ->dateTime('d.m.Y H:i'),
                                TextEntry::make('status')
                                    ->formatStateUsing(fn ($state) => Order::STATUS_LABELS[$state] ?? $state),
                                TextEntry::make('total')
                                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->hasPriorOrdersForSameCustomer())
                    ->collapsible(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Bestellinformationen')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Bestellnummer')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('user_id')
                            ->label('Kunde')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Gastbestellung'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Ausstehend',
                                'processing' => 'In Bearbeitung',
                                'shipped' => 'Versendet',
                                'delivered' => 'Zugestellt',
                                'cancelled' => 'Storniert',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('payment_status')
                            ->label('Zahlungsstatus')
                            ->options([
                                'pending' => 'Ausstehend',
                                'paid' => 'Bezahlt',
                                'failed' => 'Fehlgeschlagen',
                                'refunded' => 'Erstattet',
                                'cancelled' => 'Storniert',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('payment_method')
                            ->label('Zahlungsmethode')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Sendungsnummer')
                            ->maxLength(255)
                            ->placeholder('Optional'),
                        Forms\Components\Select::make('shipping_carrier')
                            ->label('Versanddienst')
                            ->options(Order::shippingCarrierOptions())
                            ->placeholder('Optional')
                            ->native(false),
                        Forms\Components\TextInput::make('custom_tracking_url')
                            ->label('Eigener Tracking-Link')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://...')
                            ->visible(fn ($get) => $get('shipping_carrier') === 'other'),
                    ])->columns(2),

                Section::make('Beträge')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Zwischensumme')
                            ->numeric()
                            ->prefix((string) \App\Models\Setting::get('currency_symbol', '€'))
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('shipping_cost')
                            ->label('Versandkosten')
                            ->numeric()
                            ->prefix((string) \App\Models\Setting::get('currency_symbol', '€'))
                            ->default(0),

                        Forms\Components\TextInput::make('tax')
                            ->label('MwSt.')
                            ->numeric()
                            ->prefix((string) \App\Models\Setting::get('currency_symbol', '€'))
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('total')
                            ->label('Gesamtbetrag')
                            ->numeric()
                            ->prefix((string) \App\Models\Setting::get('currency_symbol', '€'))
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(4),

                Section::make('Lieferadresse')
                    ->schema([
                        Forms\Components\KeyValue::make('shipping_address')
                            ->label('Lieferadresse')
                            ->keyLabel('Feld')
                            ->valueLabel('Wert')
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('billing_address')
                            ->label('Rechnungsadresse')
                            ->keyLabel('Feld')
                            ->valueLabel('Wert')
                            ->columnSpanFull(),
                    ]),

                Section::make('Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Bestellnr.')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Bestellnummer kopiert'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Ausstehend',
                        'processing' => 'In Bearbeitung',
                        'shipped' => 'Versendet',
                        'delivered' => 'Zugestellt',
                        'cancelled' => 'Storniert',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Zahlung')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'failed' => 'Fehlgeschlagen',
                        'refunded' => 'Erstattet',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Zahlungsart')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Tracking')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shipping_carrier')
                    ->label('Versand')
                    ->formatStateUsing(fn ($state) => Order::shippingCarrierOptions()[$state] ?? '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Artikel')
                    ->counts('items')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Gesamt')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bestellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'processing' => 'In Bearbeitung',
                        'shipped' => 'Versendet',
                        'delivered' => 'Zugestellt',
                        'cancelled' => 'Storniert',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Zahlungsstatus')
                    ->options([
                        'pending' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'failed' => 'Fehlgeschlagen',
                        'refunded' => 'Erstattet',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis'),
                    ])
                    ->query(function ($query, $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Actions\Action::make('mark_paid')
                    ->label('Bezahlt')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->payment_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Zahlungsstatus auf „Bezahlt" setzen?')
                    ->action(function ($record) {
                        $record->update(['payment_status' => 'paid']);
                        Notification::make()
                            ->success()
                            ->title('Zahlungsstatus aktualisiert')
                            ->send();
                    }),

                Actions\Action::make('mark_shipped_notify')
                    ->label('Versenden & Mail')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->visible(fn ($record) => ! in_array($record->status, ['shipped', 'delivered', 'cancelled'], true))
                    ->modalHeading('Als versendet markieren und Versand-Mail senden')
                    ->modalDescription('Optional Versanddienst und Sendungsnummer eintragen. Beides wird in der Versand-Mail mitgegeben.')
                    ->form([
                        Forms\Components\Select::make('shipping_carrier')
                            ->label('Versanddienst')
                            ->options(Order::shippingCarrierOptions())
                            ->placeholder('Bitte wählen')
                            ->native(false),
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Sendungsnummer (optional)')
                            ->required(fn ($get) => filled($get('shipping_carrier')) && $get('shipping_carrier') !== 'other')
                            ->maxLength(255)
                            ->placeholder('z. B. 00340434161094000000'),
                        Forms\Components\TextInput::make('custom_tracking_url')
                            ->label('Eigener Tracking-Link')
                            ->url()
                            ->required(fn ($get) => $get('shipping_carrier') === 'other')
                            ->maxLength(500)
                            ->placeholder('https://...')
                            ->visible(fn ($get) => $get('shipping_carrier') === 'other'),
                    ])
                    ->action(function ($record, $data) {
                        $trackingNumber = isset($data['tracking_number']) ? trim((string) $data['tracking_number']) : '';
                        $carrier = isset($data['shipping_carrier']) ? (string) $data['shipping_carrier'] : null;
                        $customTrackingUrl = isset($data['custom_tracking_url']) ? trim((string) $data['custom_tracking_url']) : '';

                        $record->update([
                            'status' => 'shipped',
                            'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
                            'shipping_carrier' => filled($carrier) ? $carrier : null,
                            'custom_tracking_url' => $customTrackingUrl !== '' ? $customTrackingUrl : null,
                        ]);
                        $to = $record->customerEmail();
                        if ($to !== null && $to !== '') {
                            Mail::to($to)->send(new OrderShipped($record));
                            Notification::make()
                                ->success()
                                ->title('Versendet')
                                ->body('Status gespeichert. Die Versand-Mail wurde zur Zustellung vorbereitet (Queue je nach .env).')
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->warning()
                            ->title('Versendet ohne E-Mail')
                            ->body('Status wurde gespeichert, aber es wurde keine Kunden-E-Mail gefunden.')
                            ->send();
                    }),

                Actions\Action::make('download_invoice')
                    ->label('Rechnung herunterladen')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function ($record) {
                        $pdf = Pdf::loadView('pdf.invoice', ['order' => $record]);

                        return response()->streamDownload(
                            function () use ($pdf): void {
                                echo $pdf->output();
                            },
                            'rechnung-'.$record->order_number.'.pdf'
                        );
                    }),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('mark_shipped')
                        ->label('Als versendet markieren')
                        ->icon('heroicon-o-truck')
                        ->action(fn ($records) => $records->each->update(['status' => 'shipped']))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('mark_delivered')
                        ->label('Als zugestellt markieren')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['status' => 'delivered']))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withCount('items');
    }
}
