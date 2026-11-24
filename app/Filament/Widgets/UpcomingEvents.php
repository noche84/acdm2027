<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingEvents extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Les prochains concerts';

    public function table(Table $table): Table
    {
        return $table
            ->query(Event::query()->upcoming()->with('venue'))
            ->paginated(false)
            ->defaultPaginationPageOption(8)
            ->columns([
                TextColumn::make('starts_at')->label('Date')->dateTime('D d/m/Y · H:i'),
                TextColumn::make('brand')->label('Nom')->badge(),
                TextColumn::make('title')->label('Concert')->state(fn (Event $r) => $r->display_title),
                TextColumn::make('venue.name')->label('Lieu'),
                TextColumn::make('status')->label('Statut')->badge(),
            ])
            ->recordUrl(fn (Event $r) => EventResource::getUrl('edit', ['record' => $r]))
            ->recordActions([EditAction::make()->url(fn (Event $r) => EventResource::getUrl('edit', ['record' => $r]))]);
    }
}
