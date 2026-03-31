<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Einstellungen';
    
    protected static ?string $modelLabel = 'Einstellung';
    
    protected static ?string $pluralModelLabel = 'Einstellungen';
    
    protected static string | \UnitEnum | null $navigationGroup = '⚙️ System & Technik';
    
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Einstellung')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Schlüssel')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->helperText('Eindeutiger Schlüssel (z.B. shop_name, primary_color)'),
                            
                        Forms\Components\Select::make('group')
                            ->label('Gruppe')
                            ->options([
                                'general' => 'Allgemein',
                                'theme' => 'Design',
                                'shipping' => 'Versand',
                                'payment' => 'Zahlung',
                                'email' => 'E-Mail',
                                'seo' => 'SEO',
                            ])
                            ->default('general')
                            ->required()
                            ->native(false),
                            
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->options([
                                'text' => 'Text',
                                'textarea' => 'Mehrzeiliger Text',
                                'number' => 'Zahl',
                                'boolean' => 'Ja/Nein',
                                'color' => 'Farbe',
                                'image' => 'Bild',
                                'json' => 'JSON',
                            ])
                            ->default('text')
                            ->required()
                            ->reactive()
                            ->native(false),
                            
                        Forms\Components\Toggle::make('is_encrypted')
                            ->label('Verschlüsselt speichern')
                            ->helperText('Für sensible Daten wie API-Schlüssel'),
                    ])->columns(2),
                    
                Section::make('Wert')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Wert')
                            ->visible(fn ($get) => in_array($get('type'), ['text', 'number', null]))
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('value')
                            ->label('Wert')
                            ->rows(4)
                            ->visible(fn ($get) => $get('type') === 'textarea')
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('value')
                            ->label('Wert')
                            ->visible(fn ($get) => $get('type') === 'boolean')
                            ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0')
                            ->afterStateHydrated(fn ($component, $state) => $component->state($state === '1')),
                            
                        Forms\Components\ColorPicker::make('value')
                            ->label('Wert')
                            ->visible(fn ($get) => $get('type') === 'color'),
                            
                        Forms\Components\FileUpload::make('value')
                            ->label('Wert')
                            ->image()
                            ->directory('settings')
                            ->disk('public')
                            ->visibility('public')
                            ->visible(fn ($get) => $get('type') === 'image'),
                            
                        Forms\Components\Textarea::make('value')
                            ->label('Wert (JSON)')
                            ->rows(6)
                            ->visible(fn ($get) => $get('type') === 'json')
                            ->helperText('Gültiges JSON-Format eingeben')
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Beschreibung')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(2)
                            ->helperText('Optionale Beschreibung für Administratoren')
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Gruppe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'general' => 'Allgemein',
                        'theme' => 'Design',
                        'shipping' => 'Versand',
                        'payment' => 'Zahlung',
                        'email' => 'E-Mail',
                        'seo' => 'SEO',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'general' => 'gray',
                        'theme' => 'info',
                        'shipping' => 'warning',
                        'payment' => 'success',
                        'email' => 'primary',
                        'seo' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('key')
                    ->label('Schlüssel')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Schlüssel kopiert'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Wert')
                    ->limit(50)
                    ->formatStateUsing(function ($record, $state) {
                        if ($record?->is_encrypted) {
                            return '********';
                        }
                        if ($record?->type === 'boolean') {
                            return $state === '1' ? 'Ja' : 'Nein';
                        }
                        if ($record?->type === 'color') {
                            return $state;
                        }
                        return $state;
                    }),
                    
                Tables\Columns\ColorColumn::make('value')
                    ->label('')
                    ->visible(fn ($record) => $record?->type === 'color'),
                    
                Tables\Columns\IconColumn::make('is_encrypted')
                    ->label('Verschlüsselt')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group')
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label('Gruppe')
                    ->getTitleFromRecordUsing(fn ($record) => match ($record->group) {
                        'general' => 'Allgemein',
                        'theme' => 'Design',
                        'shipping' => 'Versand',
                        'payment' => 'Zahlung',
                        'email' => 'E-Mail',
                        'seo' => 'SEO',
                        default => $record->group,
                    }),
            ])
            ->defaultGroup('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Gruppe')
                    ->options([
                        'general' => 'Allgemein',
                        'theme' => 'Design',
                        'shipping' => 'Versand',
                        'payment' => 'Zahlung',
                        'email' => 'E-Mail',
                        'seo' => 'SEO',
                    ]),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'text' => 'Text',
                        'textarea' => 'Mehrzeiliger Text',
                        'number' => 'Zahl',
                        'boolean' => 'Ja/Nein',
                        'color' => 'Farbe',
                        'image' => 'Bild',
                        'json' => 'JSON',
                    ]),
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
