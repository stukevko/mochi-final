<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

class ManageAboutPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string | UnitEnum | null $navigationGroup = '📰 Inhalte';

    protected static ?string $navigationLabel = 'Über uns';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'ueber-uns';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Über uns';
    }

    public function mount(): void
    {
        $this->form->fill($this->getSetting()->attributesToArray());
    }

    protected function getSetting(): SiteSetting
    {
        return SiteSetting::current();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getSetting())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $editorToolbar = [
            'bold',
            'bulletList',
            'h2',
            'h3',
            'italic',
            'link',
            'orderedList',
        ];

        $galleryFields = [];
        foreach (range(1, 5) as $i) {
            $galleryFields[] = FileUpload::make("about_gallery_image_{$i}")
                ->label("Instagram-Bild {$i}")
                ->image()
                ->disk('public')
                ->directory('about-gallery')
                ->imageEditor()
                ->helperText('Quadratisch oder Hochformat — wird im Karussell kompakt angezeigt.');
        }

        return $schema
            ->schema([
                Placeholder::make('preview_about')
                    ->label('Öffentliche Seite')
                    ->content(fn (): string => route('about')),
                Tabs::make('Über uns')
                    ->tabs([
                        Tab::make('Kopf & Intro')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('Seitenkopf')
                                    ->description('Titel und Kurztext oben auf /ueber-uns')
                                    ->schema([
                                        TextInput::make('about_page_title')
                                            ->label('Seitentitel')
                                            ->maxLength(120)
                                            ->placeholder('Über uns'),
                                        TextInput::make('about_hero_subtitle')
                                            ->label('Untertitel')
                                            ->maxLength(200)
                                            ->placeholder('Dein TCG Wohnzimmer in Speyer'),
                                        Textarea::make('about_intro')
                                            ->label('Einleitung')
                                            ->rows(4)
                                            ->maxLength(1200)
                                            ->columnSpanFull(),
                                        TextInput::make('about_meta_description')
                                            ->label('Meta-Beschreibung (SEO)')
                                            ->maxLength(320)
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),
                        Tab::make('Geschichte')
                            ->icon('heroicon-o-book-open')
                            ->schema([
                                Section::make('Haupttext')
                                    ->schema([
                                        RichEditor::make('about_story')
                                            ->label('Unsere Geschichte')
                                            ->toolbarButtons($editorToolbar)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Highlights')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                Section::make('Highlight 1')
                                    ->schema([
                                        TextInput::make('about_highlight_1_title')->label('Titel')->maxLength(80),
                                        Textarea::make('about_highlight_1_body')->label('Text')->rows(3)->maxLength(500),
                                    ])->columns(1),
                                Section::make('Highlight 2')
                                    ->schema([
                                        TextInput::make('about_highlight_2_title')->label('Titel')->maxLength(80),
                                        Textarea::make('about_highlight_2_body')->label('Text')->rows(3)->maxLength(500),
                                    ])->columns(1),
                                Section::make('Highlight 3')
                                    ->schema([
                                        TextInput::make('about_highlight_3_title')->label('Titel')->maxLength(80),
                                        Textarea::make('about_highlight_3_body')->label('Text')->rows(3)->maxLength(500),
                                    ])->columns(1),
                            ]),
                        Tab::make('Weiteres')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Zusätzlicher Abschnitt')
                                    ->description('Optional — z. B. Team, Laden, Community')
                                    ->schema([
                                        TextInput::make('about_extra_title')->label('Titel')->maxLength(120),
                                        RichEditor::make('about_extra_body')
                                            ->label('Inhalt')
                                            ->toolbarButtons($editorToolbar)
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Call-to-Action')
                                    ->schema([
                                        TextInput::make('about_cta_label')
                                            ->label('Button-Text')
                                            ->maxLength(80)
                                            ->placeholder('Besuch uns im Laden'),
                                        TextInput::make('about_cta_url')
                                            ->label('Button-Link')
                                            ->maxLength(500)
                                            ->placeholder('/kontakt oder https://…'),
                                    ])->columns(2),
                            ]),
                        Tab::make('Instagram')
                            ->icon('heroicon-o-camera')
                            ->schema([
                                Section::make('Instagram-Karussell')
                                    ->description('Bis zu 5 Bilder — kompaktes Karussell auf der Über-uns-Seite. Profil-Link kommt aus Website-Einstellungen → Instagram-URL.')
                                    ->schema([
                                        TextInput::make('about_instagram_heading')
                                            ->label('Überschrift über dem Karussell')
                                            ->maxLength(120)
                                            ->placeholder('Ein Blick in unseren Laden'),
                                        ...$galleryFields,
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $this->getSetting()->fill($data);
            $this->getSetting()->save();

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        Notification::make()
            ->success()
            ->title('Über-uns-Seite gespeichert')
            ->body('Die Inhalte sind sofort unter /ueber-uns sichtbar.')
            ->send();
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
