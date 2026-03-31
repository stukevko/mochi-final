<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = '🛍️ Management';

    protected static bool $isGloballySearchable = false;

    protected static ?string $navigationLabel = 'Kunden';

    protected static ?string $modelLabel = 'Kunde';

    protected static ?string $pluralModelLabel = 'Kunden';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Benutzerdaten')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Passwort')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn ($operation) => $operation === 'create')
                            ->minLength(8)
                            ->helperText(fn ($operation) => $operation === 'edit' ? 'Leer lassen, um das Passwort nicht zu ändern' : ''),

                        Forms\Components\Select::make('role')
                            ->label('Rolle')
                            ->options([
                                'admin' => 'Administrator',
                                'customer' => 'Kunde',
                            ])
                            ->required()
                            ->default('customer'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Deaktivierte Benutzer können sich nicht einloggen'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rolle')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'admin' => 'Administrator',
                        'customer' => 'Kunde',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'admin' => 'danger',
                        'customer' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Bestellungen')
                    ->counts('orders')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verifiziert')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Nicht verifiziert')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registriert')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rolle')
                    ->options([
                        'admin' => 'Administrator',
                        'customer' => 'Kunde',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('E-Mail verifiziert')
                    ->nullable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Verhindere das Löschen des letzten Admins
                        if ($record->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
                            throw new \Exception('Der letzte Administrator kann nicht gelöscht werden.');
                        }
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
