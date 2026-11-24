<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Filament\Support\Editor;
use App\Models\Event;
use Closure;
use Filament\Support\Icons\Heroicon;
use App\Models\Series;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Grid::make(1)->columnSpan(['lg' => 2])->schema([
                        self::whenWhere(),
                        self::program(),
                        self::practical(),
                    ]),
                    Grid::make(1)->columnSpan(['lg' => 1])->schema([
                        self::publication(),
                        self::poster(),
                    ]),
                ]),
        ]);
    }

    private static function whenWhere(): Section
    {
        return Section::make('Quand et où ?')
            ->columns(2)
            ->schema([
                ToggleButtons::make('brand')
                    ->label('Organisé sous le nom')
                    ->options(Brand::class)
                    ->default(Brand::Acdm->value)
                    ->inline()
                    ->required()
                    ->live()
                    ->helperText('ACDM : les concerts du 2e dimanche. FoliaMusica : tous les autres.')
                    ->afterStateUpdated(fn (Set $set) => $set('series_id', null))
                    ->columnSpanFull(),

                Select::make('series_id')
                    ->label('Série')
                    ->relationship('series', 'name', fn (Builder $query, Get $get) => $query
                        ->where('brand', $get('brand') instanceof Brand ? $get('brand')->value : $get('brand'))
                        ->orderBy('sort'))
                    ->preload()
                    ->live()
                    ->helperText('Choisir une série remplit le lieu, l\'heure et le tarif habituels.')
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                        $series = $state ? Series::find($state) : null;
                        if (! $series) {
                            return;
                        }
                        if ($series->default_venue_id && ! $get('venue_id')) {
                            $set('venue_id', $series->default_venue_id);
                        }
                        if ($series->default_price_info && ! $get('price_info')) {
                            $set('price_info', $series->default_price_info);
                        }
                        if ($series->default_time && $get('starts_at')) {
                            $set('starts_at', Carbon::parse($get('starts_at'))->setTimeFromTimeString($series->default_time)->format('Y-m-d H:i:s'));
                        }
                    }),

                DateTimePicker::make('starts_at')
                    ->label('Date et heure')
                    ->required()
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('l j F Y, H:i')
                    ->firstDayOfWeek(1)
                    ->minutesStep(15)
                    ->default(fn () => self::nextSecondSunday()),

                Select::make('venue_id')
                    ->label('Lieu')
                    ->relationship('venue', 'name', fn (Builder $query) => $query->orderByDesc('is_active')->orderBy('name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->label)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')->label('Nom du lieu')->required(),
                        TextInput::make('room')->label('Salle'),
                        TextInput::make('street')->label('Rue et numéro'),
                        Grid::make(2)->schema([
                            TextInput::make('postal_code')->label('Code postal'),
                            TextInput::make('city')->label('Localité')->default('Liège'),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function program(): Section
    {
        return Section::make('Le concert')
            ->description('Vous pouvez coller directement le texte de Word : la mise en forme superflue est retirée automatiquement.')
            ->schema([
                TextInput::make('title')
                    ->label('Titre du programme')
                    ->placeholder('Love Story')
                    ->helperText('Sans guillemets : ils sont ajoutés à l\'affichage.')
                    ->maxLength(250),
                TextInput::make('ensemble')
                    ->label('Formation')
                    ->placeholder('Trio Memento, Duo Sublima…')
                    ->maxLength(250),
                Editor::make('performers')
                    ->label('Interprètes')
                    ->placeholder('Un musicien par ligne : « Pierre Vopat, violon »'),
                Editor::make('program')
                    ->label('Programme / compositeurs'),
                Editor::make('description')
                    ->label('Texte de présentation (facultatif)'),
                TextInput::make('video_url')
                    ->label('Vidéo des musiciens (facultatif)')
                    ->placeholder('https://www.youtube.com/watch?v=…')
                    ->prefixIcon(Heroicon::OutlinedPlayCircle)
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Une vidéo où l\'on entend les musiciens invités (ou l\'ensemble) donne envie de venir. Cherchez-la sur YouTube, cliquez sur « Partager », copiez le lien et collez-le ici. Elle s\'affichera sur la page du concert, sous le programme.')
                    ->maxLength(500)
                    ->live(onBlur: true)
                    ->helperText(function (?string $state) {
                        if (blank($state)) {
                            return 'Collez le lien d\'une vidéo YouTube ou Vimeo. Inutile de créer une chaîne : la vidéo du musicien suffit.';
                        }
                        $video = Event::parseVideo($state);

                        return $video ? "✓ Vidéo {$video['provider']} reconnue : elle sera affichée sur la page du concert." : null;
                    })
                    ->rules([
                        fn () => function (string $attribute, $value, Closure $fail) {
                            if (filled($value) && ! Event::parseVideo($value)) {
                                $fail('Ce lien n\'est pas reconnu : copiez l\'adresse d\'une vidéo YouTube ou Vimeo (bouton « Partager » sous la vidéo).');
                            }
                        },
                    ]),
            ]);
    }

    private static function practical(): Section
    {
        return Section::make('Infos pratiques')
            ->columns(2)
            ->collapsible()
            ->schema([
                TextInput::make('price_info')
                    ->label('Tarif particulier')
                    ->placeholder('Entrée gratuite')
                    ->helperText('Laisser vide pour les tarifs habituels de l\'ACDM.'),
                TextInput::make('partner_mention')
                    ->label('Mention de partenariat')
                    ->placeholder('En collaboration avec…'),
                TextInput::make('booking_url')
                    ->label('Lien de réservation (facultatif)')
                    ->url()
                    ->columnSpanFull(),
            ]);
    }

    private static function publication(): Section
    {
        return Section::make('Publication')
            ->schema([
                Toggle::make('is_published')
                    ->label('Visible sur le site')
                    ->default(true),
                ToggleButtons::make('status')
                    ->label('Statut')
                    ->options(EventStatus::class)
                    ->default(EventStatus::Scheduled->value)
                    ->inline()
                    ->required()
                    ->live(),
                TextInput::make('status_note')
                    ->label('Précision')
                    ->placeholder('Reporté au 14 mars, raison…')
                    ->visible(fn (Get $get) => ! in_array($get('status'), [EventStatus::Scheduled, EventStatus::Scheduled->value, null], true)),
            ]);
    }

    private static function poster(): Section
    {
        return Section::make('Affiche')
            ->description('Visible sur la page du concert, dans les partages Facebook / WhatsApp et sur l\'image Instagram.')
            ->schema([
                FileUpload::make('poster')
                    ->hiddenLabel()
                    ->image()
                    ->disk('public')
                    ->directory('affiches')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->imageEditor()
                    ->helperText('JPG ou PNG, 5 Mo maximum.'),
            ]);
    }

    /** Le prochain 2e dimanche du mois, à 11h. */
    public static function nextSecondSunday(): string
    {
        $month = now()->startOfMonth();
        for ($i = 0; $i < 3; $i++) {
            $sunday = $month->copy()->addMonths($i)->firstOfMonth(Carbon::SUNDAY)->addWeek()->setTime(11, 0);
            if ($sunday->isFuture()) {
                return $sunday->format('Y-m-d H:i:s');
            }
        }

        return now()->setTime(11, 0)->format('Y-m-d H:i:s');
    }
}
