<?php

namespace App\Filament\Resources\Series;

use App\Enums\Brand;
use App\Filament\Resources\Series\Pages\ManageSeries;
use App\Filament\Support\Editor;
use App\Models\Series;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SeriesResource extends Resource
{
    protected static ?string $model = Series::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string | UnitEnum | null $navigationGroup = 'Programmation';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'série';

    protected static ?string $pluralModelLabel = 'séries';

    public static function canDelete($record): bool
    {
        return auth()->user()?->isAdmin() && ! $record->events()->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            ToggleButtons::make('brand')->label('Nom')->options(Brand::class)->inline()->required()->columnSpanFull(),
            TextInput::make('name')->label('Nom de la série')->required(),
            TextInput::make('sort')->label('Ordre')->numeric()->default(0),
            Select::make('default_venue_id')->label('Lieu habituel')->relationship('defaultVenue', 'name')->searchable()->preload(),
            TimePicker::make('default_time')->label('Heure habituelle')->seconds(false),
            TextInput::make('default_price_info')->label('Tarif habituel')->placeholder('Entrée gratuite'),
            TextInput::make('website')->label('Site web')->url(),
            Editor::make('description')->label('Texte affiché avec chaque concert de la série')->columnSpanFull(),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('Série')->searchable(),
                TextColumn::make('brand')->label('Nom')->badge(),
                TextColumn::make('defaultVenue.name')->label('Lieu habituel'),
                TextColumn::make('events_count')->label('Concerts')->counts('events'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSeries::route('/')];
    }
}
