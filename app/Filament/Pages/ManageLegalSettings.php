<?php

namespace App\Filament\Pages;

use App\Models\CmsPage;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Throwable;
use UnitEnum;

/**
 * Rechtstexte für Shop & Website — ohne CMS-Slug oder DB-Schlüssel.
 */
class ManageLegalSettings extends Page
{
    use CanUseDatabaseTransactions;

    public ?array $data = [];

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static string | UnitEnum | null $navigationGroup = '⚙️ Konfiguration';

    protected static ?string $navigationLabel = 'Rechtstexte';

    protected static ?int $navigationSort = 110;

    public function mount(): void
    {
        $this->fillForm();
    }

    public function getTitle(): string
    {
        return 'Rechtstexte';
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            'legal_impressum' => $this->loadLegalContent('legal_impressum', 'impressum'),
            'legal_agb' => $this->loadLegalContent('legal_agb'),
            'legal_privacy' => $this->loadLegalContent('legal_privacy', 'datenschutz'),
            'legal_widerruf' => $this->loadLegalContent('legal_widerruf', 'widerruf'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
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

        return $schema
            ->schema([
                Tabs::make('Rechtliches')
                    ->tabs([
                        Tab::make('Impressum')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Section::make('Impressum')
                                    ->description('Pflichtangaben nach § 5 TMG / § 55 RStV. Öffentlich unter /impressum')
                                    ->schema([
                                        Forms\Components\Placeholder::make('preview_impressum')
                                            ->label('Vorschau')
                                            ->content(fn (): string => route('legal.impressum')),
                                        Forms\Components\RichEditor::make('legal_impressum')
                                            ->label('Inhalt')
                                            ->toolbarButtons($editorToolbar)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('AGB')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Allgemeine Geschäftsbedingungen')
                                    ->description('Gilt für Online-Bestellungen. Öffentlich unter /agb')
                                    ->schema([
                                        Forms\Components\Placeholder::make('preview_agb')
                                            ->label('Vorschau')
                                            ->content(fn (): string => route('legal.agb')),
                                        Forms\Components\RichEditor::make('legal_agb')
                                            ->label('Inhalt')
                                            ->toolbarButtons($editorToolbar)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Datenschutz')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Datenschutzerklärung')
                                    ->description('DSGVO-konforme Informationen. Öffentlich unter /datenschutz')
                                    ->schema([
                                        Forms\Components\Placeholder::make('preview_privacy')
                                            ->label('Vorschau')
                                            ->content(fn (): string => route('legal.datenschutz')),
                                        Forms\Components\RichEditor::make('legal_privacy')
                                            ->label('Inhalt')
                                            ->toolbarButtons($editorToolbar)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Widerruf')
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->schema([
                                Section::make('Widerrufsbelehrung')
                                    ->description('Inkl. Muster-Widerrufsformular für Verbraucher. Öffentlich unter /widerruf')
                                    ->schema([
                                        Forms\Components\Placeholder::make('preview_widerruf')
                                            ->label('Vorschau')
                                            ->content(fn (): string => route('legal.widerruf')),
                                        Forms\Components\RichEditor::make('legal_widerruf')
                                            ->label('Inhalt')
                                            ->toolbarButtons($editorToolbar)
                                            ->columnSpanFull(),
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

            $definitions = [
                'legal_impressum' => 'Impressum',
                'legal_agb' => 'AGB',
                'legal_privacy' => 'Datenschutzerklärung',
                'legal_widerruf' => 'Widerrufsbelehrung',
            ];

            foreach ($definitions as $key => $label) {
                $html = trim((string) ($data[$key] ?? ''));

                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $html !== '' ? $html : null,
                        'type' => 'text',
                        'group' => 'legal',
                        'description' => $label,
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
            ->title('Rechtstexte gespeichert')
            ->body('Die Texte sind sofort auf der Website sichtbar.')
            ->send();
    }

    protected function loadLegalContent(string $settingKey, ?string $cmsSlug = null): string
    {
        $fromSetting = Setting::get($settingKey);
        if (is_string($fromSetting) && trim($fromSetting) !== '') {
            return $fromSetting;
        }

        if ($cmsSlug !== null) {
            $page = CmsPage::query()->where('slug', $cmsSlug)->first();
            if ($page && filled($page->body)) {
                return (string) $page->body;
            }
        }

        return '';
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
