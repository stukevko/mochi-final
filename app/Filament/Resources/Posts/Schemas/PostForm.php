<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\GameType;
use App\Enums\PostType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inhalt')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, string $operation) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug((string) $state));
                            }),
                        Toggle::make('allow_slug_edit')
                            ->label('Slug freischalten (öffentliche URL ändert sich)')
                            ->visibleOn('edit')
                            ->default(false)
                            ->live()
                            ->dehydrated(false),
                        TextInput::make('slug')
                            ->label('URL-Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Automatisch nur beim Erstellen. Bei bestehenden Beiträgen nur nach Freischalten ändern, um tote Links zu vermeiden.')
                            ->readOnly(fn (Get $get, string $operation): bool => $operation === 'edit' && ! $get('allow_slug_edit')),
                        Select::make('type')
                            ->label('Typ')
                            ->options(collect(PostType::cases())->mapWithKeys(fn (PostType $t) => [$t->value => $t->label()]))
                            ->required()
                            ->native(false),
                        Select::make('post_category_id')
                            ->label('Kategorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('game_type')
                            ->label('Spiel / Badge (optional)')
                            ->options(collect(GameType::casesForSelect())->mapWithKeys(fn (GameType $g) => [$g->value => $g->label()]))
                            ->live()
                            ->native(false)
                            ->nullable()
                            ->placeholder('Ohne Spiel-Badge')
                            ->helperText('Farb-Badge bei News — z. B. Pokémon gelb, One Piece rot.'),
                        TextInput::make('game_type_other')
                            ->label('Name der eigenen Spielart')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => $get('game_type') === GameType::Custom->value)
                            ->required(fn (Get $get): bool => $get('game_type') === GameType::Custom->value)
                            ->helperText('Pflicht, wenn „Eigene Spielart“ gewählt ist.'),
                        Textarea::make('excerpt')
                            ->label('Kurztext / Teaser')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('cover_image_path')
                            ->label('Vorschaubild (Karten-Optik)')
                            ->image()
                            ->disk('public')
                            ->directory('posts/covers')
                            ->imageEditor()
                            ->columnSpanFull(),
                        RichEditor::make('body')
                            ->label('Text')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Veröffentlichung')
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Veröffentlicht')
                            ->default(false),
                        DateTimePicker::make('published_at')
                            ->label('Veröffentlicht am')
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }
}
