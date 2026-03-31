<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroSectionResource\Pages;
use App\Models\HeroSection;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HeroSectionResource extends Resource
{
    protected static ?string $model = HeroSection::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Startseiten-Hero';

    protected static ?string $modelLabel = 'Hero-Bereich';

    protected static ?string $pluralModelLabel = 'Hero-Bereiche';

    protected static string | \UnitEnum | null $navigationGroup = '📰 Inhalte';

    protected static ?int $navigationSort = 22;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Inhalt')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->helperText('z. B. home für die Startseite'),

                        Forms\Components\TextInput::make('headline')
                            ->label('Haupttitel')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('subheadline')
                            ->label('Untertitel')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('background_image')
                            ->label('Hintergrundbild')
                            ->image()
                            ->directory('hero')
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cta_label')
                            ->label('Button-Text')
                            ->maxLength(120)
                            ->default('Jetzt entdecken')
                            ->placeholder('Jetzt entdecken'),

                        Forms\Components\TextInput::make('cta_url')
                            ->label('Button-Link')
                            ->maxLength(255)
                            ->default('/shop')
                            ->placeholder('/shop'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('headline')
                    ->label('Titel')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('slug')
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeroSections::route('/'),
            'create' => Pages\CreateHeroSection::route('/create'),
            'edit' => Pages\EditHeroSection::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
