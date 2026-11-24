<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\ManagePartners;
use App\Models\Partner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static string | UnitEnum | null $navigationGroup = 'Contenu du site';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'partenaire';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nom')->required(),
            Select::make('category')->label('Catégorie')->options(Partner::CATEGORIES)->required()->default('soutien'),
            TextInput::make('url')->label('Site web')->url(),
            TextInput::make('sort')->label('Ordre d\'affichage')->numeric()->default(0),
            FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('partners')->visibility('public')->maxSize(2048)->columnSpanFull(),
            Toggle::make('is_active')->label('Affiché sur le site')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('logo')->disk('public')->height(40),
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('category')->label('Catégorie')->formatStateUsing(fn ($state) => Partner::CATEGORIES[$state] ?? $state),
                IconColumn::make('is_active')->label('Affiché')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePartners::route('/')];
    }
}
