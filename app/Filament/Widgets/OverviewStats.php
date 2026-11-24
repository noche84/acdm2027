<?php

namespace App\Filament\Widgets;

use App\Enums\Brand;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\Event;
use App\Models\Subscriber;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $next = Event::query()->published()->upcoming()->brand(Brand::Acdm)->first();
        $nextFolia = Event::query()->published()->upcoming()->brand(Brand::FoliaMusica)->first();

        return [
            Stat::make('Prochain concert ACDM', $next ? $next->starts_at->locale('fr')->isoFormat('D MMMM') : '—')
                ->description($next?->display_title ?? 'Aucun concert programmé')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url($next ? EventResource::getUrl('edit', ['record' => $next]) : EventResource::getUrl('create')),
            Stat::make('Prochain concert FoliaMusica', $nextFolia ? $nextFolia->starts_at->locale('fr')->isoFormat('D MMMM') : '—')
                ->description($nextFolia?->display_title ?? 'Aucun concert programmé')
                ->icon(Heroicon::OutlinedSparkles)
                ->url($nextFolia ? EventResource::getUrl('edit', ['record' => $nextFolia]) : EventResource::getUrl('create')),
            Stat::make('Abonnés à la newsletter', Subscriber::confirmed()->count())
                ->description(Subscriber::confirmed()->where('source', 'site')->where('confirmed_at', '>=', now()->subDays(30))->count().' inscription(s) via le site ce mois-ci')
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(SubscriberResource::getUrl('index')),
        ];
    }
}
