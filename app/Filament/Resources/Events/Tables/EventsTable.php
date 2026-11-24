<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Filament\Resources\Events\Actions\InstagramAction;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['venue', 'series']))
            ->defaultSort('starts_at', 'asc')
            ->persistFiltersInSession()
            ->striped()
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Date')
                    ->dateTime('D d/m/Y · H:i')
                    ->sortable(),
                TextColumn::make('brand')
                    ->label('Nom')
                    ->badge(),
                TextColumn::make('title')
                    ->label('Concert')
                    ->state(fn (Event $record) => $record->display_title)
                    ->description(fn (Event $record) => $record->title ? $record->ensemble : null)
                    ->searchable(['title', 'ensemble', 'performers', 'program'])
                    ->wrap(),
                TextColumn::make('venue.name')
                    ->label('Lieu')
                    ->toggleable(),
                TextColumn::make('series.name')
                    ->label('Série')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                IconColumn::make('is_published')
                    ->label('En ligne')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->label('Période')
                    ->options(['upcoming' => 'À venir', 'past' => 'Passés'])
                    ->default('upcoming')
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'upcoming' => $query->where('starts_at', '>=', now()->startOfDay()),
                        'past' => $query->where('starts_at', '<', now()->startOfDay()),
                        default => $query,
                    }),
                SelectFilter::make('brand')->label('Nom')->options(Brand::class),
                SelectFilter::make('series')->label('Série')->relationship('series', 'name'),
                SelectFilter::make('venue')->label('Lieu')->relationship('venue', 'name')->searchable()->preload(),
                SelectFilter::make('status')->label('Statut')->options(EventStatus::class),
                SelectFilter::make('year')
                    ->label('Année')
                    ->options(fn () => collect(range((int) now()->addYear()->format('Y'), 2012))->mapWithKeys(fn ($y) => [$y => $y])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereBetween('starts_at', [$data['value'].'-01-01 00:00:00', $data['value'].'-12-31 23:59:59'])
                        : $query),
            ])
            ->recordActions([
                EditAction::make()
                    ->tooltip('Modifier les informations de ce concert.'),
                ReplicateAction::make()
                    ->label('Dupliquer')
                    ->iconButton()
                    ->tooltip('Dupliquer : crée une copie (non publiée) : pratique pour un concert d\'une même série. Il ne reste qu\'à changer la date et les musiciens.')
                    ->modalDescription('Crée une copie non publiée de ce concert, que vous pourrez modifier.')
                    ->excludeAttributes(['slug', 'legacy_id', 'created_by', 'updated_by', 'poster'])
                    ->beforeReplicaSaved(function (Model $replica) {
                        $replica->is_published = false;
                        $replica->status = EventStatus::Scheduled;
                        $replica->status_note = null;
                    })
                    ->successRedirectUrl(fn (Model $replica) => EventResource::getUrl('edit', ['record' => $replica])),
                Action::make('voir')
                    ->label('Voir')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->iconButton()
                    ->tooltip('Voir sur le site : ouvre la page publique dans un nouvel onglet.')
                    ->url(fn (Event $record) => $record->url, shouldOpenInNewTab: true),
                InstagramAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publier')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true])),
                    BulkAction::make('unpublish')
                        ->label('Masquer du site')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
