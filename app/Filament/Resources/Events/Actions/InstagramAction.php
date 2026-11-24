<?php

namespace App\Filament\Resources\Events\Actions;

use App\Models\Event;
use App\Services\InstagramImage;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/** Bouton « Image Instagram » : deux images prêtes à publier + le texte à copier. */
class InstagramAction
{
    public static function make(): Action
    {
        return Action::make('instagram')
            ->label('Image Instagram')
            ->icon(Heroicon::OutlinedPhoto)
            ->color('gray')
            ->tooltip('Image Instagram : prépare une image aux couleurs du site et le texte à publier sur Instagram ou Facebook.')
            ->modalHeading('Publier ce concert sur Instagram')
            ->modalIcon(Heroicon::OutlinedPhoto)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalContent(fn (Event $record) => view('filament.instagram', [
                'event' => $record,
                'caption' => app(InstagramImage::class)->caption($record),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer');
    }
}
