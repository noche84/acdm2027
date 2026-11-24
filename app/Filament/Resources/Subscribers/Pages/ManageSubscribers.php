<?php

namespace App\Filament\Resources\Subscribers\Pages;

use App\Enums\SubscriberStatus;
use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\Subscriber;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageSubscribers extends ManageRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function () {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['email', 'statut', 'confirme_le']);
                    Subscriber::confirmed()->orderBy('email')->each(fn (Subscriber $s) => fputcsv($out, [$s->email, $s->status->getLabel(), $s->confirmed_at?->format('Y-m-d')]));
                    fclose($out);
                }, 'abonnes-acdm-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
            CreateAction::make()
                ->label('Ajouter une adresse')
                ->mutateDataUsing(fn (array $data) => $data + [
                    'status' => SubscriberStatus::Confirmed,
                    'source' => 'admin',
                    'consented_at' => now(),
                    'confirmed_at' => now(),
                ]),
        ];
    }
}
