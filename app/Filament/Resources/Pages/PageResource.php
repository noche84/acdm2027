<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\Editor;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    /** Dans l'admin, on adresse les fiches par leur id (le slug change avec le titre). */
    protected static ?string $recordRouteKeyName = 'id';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | UnitEnum | null $navigationGroup = 'Contenu du site';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'page';

    public static function canDelete($record): bool
    {
        return ! $record->is_system && (bool) auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(255),
                TextInput::make('menu_title')->label('Titre court (menu)')->maxLength(60),
                Editor::make('content', full: true)->label('Contenu')->columnSpanFull(),
            ]),
            Section::make('Référencement')->collapsed()->columnSpanFull()->schema([
                TextInput::make('slug')->label('Adresse')->prefix(url('/').'/')->disabled(fn (?Page $record) => $record?->is_system)->dehydrated()->helperText('Les pages du site ont une adresse fixe.'),
                Textarea::make('meta_description')->label('Description pour Google')->rows(2)->maxLength(300),
                Toggle::make('is_published')->label('Publiée')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titre')->description(fn (Page $r) => '/'.$r->slug)->searchable(),
                IconColumn::make('is_published')->label('Publiée')->boolean(),
                TextColumn::make('updated_at')->label('Modifiée')->since()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('voir')->label('Voir')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (Page $r) => route('page', $r), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
