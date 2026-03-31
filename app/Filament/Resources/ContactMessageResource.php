<?php

namespace App\Filament\Resources;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationLabel = 'Kontakt';

    protected static ?string $modelLabel = 'Kontaktanfrage';

    protected static ?string $pluralModelLabel = 'Kontaktanfragen';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = '🛍️ Management';

    protected static ?int $navigationSort = 22;

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'message'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Nachricht')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Eingegangen')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (?ContactMessageStatus $state): string => $state?->label() ?? '—')
                            ->color(fn (?ContactMessageStatus $state): string => match ($state) {
                                ContactMessageStatus::New => 'danger',
                                ContactMessageStatus::Read => 'warning',
                                ContactMessageStatus::InProgress => 'info',
                                ContactMessageStatus::Done => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('email')
                            ->label('E-Mail')
                            ->copyable(),
                        TextEntry::make('subject')
                            ->label('Betreff')
                            ->badge()
                            ->formatStateUsing(fn (?ContactSubject $state): string => $state?->label() ?? '—'),
                        TextEntry::make('message')
                            ->label('Nachricht')
                            ->columnSpanFull()
                            ->prose(),
                        TextEntry::make('ip_address')
                            ->label('IP')
                            ->placeholder('—')
                            ->toggleable(isToggledHiddenByDefault: true),
                        TextEntry::make('user_agent')
                            ->label('User-Agent')
                            ->limit(120)
                            ->placeholder('—')
                            ->toggleable(isToggledHiddenByDefault: true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Bearbeitung')
                    ->description('Nachrichten werden nicht im Admin verändert — nur der Bearbeitungsstatus.')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(collect(ContactMessageStatus::cases())->mapWithKeys(
                                fn (ContactMessageStatus $s): array => [$s->value => $s->label()],
                            ))
                            ->required()
                            ->native(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->badge()
                    ->formatStateUsing(fn (?ContactSubject $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?ContactMessageStatus $state): string => $state?->label() ?? '—')
                    ->color(fn (?ContactMessageStatus $state): string => match ($state) {
                        ContactMessageStatus::New => 'danger',
                        ContactMessageStatus::Read => 'warning',
                        ContactMessageStatus::InProgress => 'info',
                        ContactMessageStatus::Done => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->label('Vorschau')
                    ->limit(48)
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ContactMessageStatus::cases())->mapWithKeys(
                        fn (ContactMessageStatus $s): array => [$s->value => $s->label()],
                    )),
                Tables\Filters\SelectFilter::make('subject')
                    ->label('Betreff')
                    ->options(collect(ContactSubject::cases())->mapWithKeys(
                        fn (ContactSubject $s): array => [$s->value => $s->label()],
                    )),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::openCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
