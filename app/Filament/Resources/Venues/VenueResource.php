<?php

namespace App\Filament\Resources\Venues;

use App\Filament\Resources\Venues\Pages\ManageVenues;
use App\Models\Venue;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string | UnitEnum | null $navigationGroup = 'Programmation';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'lieu';

    protected static ?string $pluralModelLabel = 'lieux';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(255),
            TextInput::make('room')->label('Salle')->placeholder('Auditorium, chapelle d\'hiver…'),
            TextInput::make('street')->label('Rue et numéro'),
            TextInput::make('postal_code')->label('Code postal')->maxLength(10),
            TextInput::make('city')->label('Localité')->default('Liège')->required(),
            TextInput::make('website')->label('Site web')->url(),
            TextInput::make('map_url')->label('Lien Google Maps (facultatif)')->url()->helperText('Sinon, un lien est créé à partir de l\'adresse.')->columnSpanFull(),
            Textarea::make('access_info')->label('Accès, parking, PMR…')->rows(3)->columnSpanFull(),
            Toggle::make('is_active')->label('Proposé dans la liste des lieux')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->description(fn (Venue $r) => $r->room)->searchable()->sortable(),
                TextColumn::make('address')->label('Adresse'),
                TextColumn::make('events_count')->label('Concerts')->counts('events')->sortable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Venue $r) => ! $r->events()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVenues::route('/')];
    }
}
