<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\Actions\InstagramAction;
use App\Filament\Resources\Events\EventResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voir')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->tooltip('Ouvre la page publique de ce concert dans un nouvel onglet.')
                ->url(fn () => $this->record->url, shouldOpenInNewTab: true),
            InstagramAction::make(),
            DeleteAction::make()
                ->tooltip('Supprime définitivement ce concert. Pour le cacher seulement, décochez « Visible sur le site ».'),
        ];
    }
}
