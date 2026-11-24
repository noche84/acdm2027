<?php

namespace App\Filament\Resources\Newsletters;

use App\Filament\Resources\Newsletters\Pages\CreateNewsletter;
use App\Filament\Resources\Newsletters\Pages\EditNewsletter;
use App\Filament\Resources\Newsletters\Pages\ListNewsletters;
use App\Filament\Support\Editor;
use App\Models\Newsletter;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterResource extends Resource
{
    protected static ?string $model = Newsletter::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string | UnitEnum | null $navigationGroup = 'Newsletter';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'newsletter';

    protected static ?string $navigationLabel = 'Envois';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->disabled(fn (?Newsletter $record) => $record?->isSent())->schema([
                TextInput::make('subject')->label('Objet de l\'e-mail')->required()->maxLength(150)
                    ->placeholder('Le programme de janvier'),
                Editor::make('body', full: true)->label('Message d\'introduction')
                    ->placeholder('Chers amis, …'),
                Toggle::make('include_upcoming')->label('Ajouter automatiquement les prochains concerts')->default(true)->live(),
                TextInput::make('upcoming_count')->label('Nombre de concerts')->numeric()->minValue(1)->maxValue(12)->default(4)
                    ->visible(fn (Get $get) => $get('include_upcoming')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')->label('Objet')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('sent_at')->label('Envoyée le')->dateTime('d/m/Y H:i'),
                TextColumn::make('recipients_count')->label('Destinataires'),
                TextColumn::make('failures_count')->label('Échecs')->color(fn ($state) => $state ? 'danger' : null),
            ])
            ->recordActions([EditAction::make()->label(fn (Newsletter $r) => $r->isSent() ? 'Voir' : 'Modifier')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletters::route('/'),
            'create' => CreateNewsletter::route('/create'),
            'edit' => EditNewsletter::route('/{record}/edit'),
        ];
    }
}
