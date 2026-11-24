{{-- Carte d'un concert. Variables : $event, $compact (bool) --}}
@php($compact = $compact ?? false)
<article @class(['event-card', $event->brand->cssClass(), 'is-cancelled' => $event->status === \App\Enums\EventStatus::Cancelled, 'compact' => $compact])>
    <a href="{{ $event->url }}" class="event-date" aria-hidden="true" tabindex="-1">
        <span class="d-day">{{ $event->starts_at->locale('fr')->isoFormat('ddd') }}</span>
        <span class="d-num">{{ $event->starts_at->format('j') }}</span>
        <span class="d-month">{{ $event->starts_at->locale('fr')->isoFormat('MMM YYYY') }}</span>
    </a>
    <div class="event-body">
        <p class="event-meta">
            <span class="badge">{{ $event->brand->getLabel() }}</span>
            @if ($event->series && $event->brand === \App\Enums\Brand::FoliaMusica)<span class="series">{{ $event->series->name }}</span>@endif
            @if ($banner = $event->status->publicBanner())<span class="badge badge-status">{{ $banner }}</span>@endif
        </p>
        <h3 class="event-title">
            <a href="{{ $event->url }}">@if ($event->title)« {{ $event->title }} »@else{{ $event->display_title }}@endif</a>
        </h3>
        @if ($event->title && $event->ensemble)<p class="event-ensemble">{{ $event->ensemble }}</p>@endif
        @unless ($compact)
            @if ($event->excerpt)<p class="event-excerpt">{{ $event->excerpt }}</p>@endif
        @endunless
        <p class="event-where">
            <span>{{ ucfirst($event->starts_at->locale('fr')->isoFormat('dddd')) }} à {{ $event->time_label }}</span>
            @if ($event->venue)<span>{{ $event->venue->label }}</span>@endif
            @if ($event->price_info)<span>{{ $event->price_info }}</span>@endif
        </p>
    </div>
</article>
