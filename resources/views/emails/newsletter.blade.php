<x-mail-layout :title="$newsletter->subject">
    @if ($newsletter->body)
        <div>{!! $newsletter->body !!}</div>
    @endif

    @if ($events->isNotEmpty())
        <h2 style="font-size:20px;color:#8b1e2d;font-weight:normal;font-style:italic;margin:28px 0 8px;">Prochains concerts</h2>
        @foreach ($events as $event)
            @php($color = $event->brand === \App\Enums\Brand::Acdm ? '#8b1e2d' : '#b45309')
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:14px 0;border-left:4px solid {{ $color }};">
                <tr><td style="padding:6px 0 6px 14px;">
                    <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:{{ $color }};">
                        {{ $event->brand->getLabel() }} · {{ ucfirst($event->date_label) }} à {{ $event->time_label }}
                    </div>
                    <div style="font-size:19px;margin:2px 0;">
                        @if ($event->title)« {{ $event->title }} »@else{{ $event->display_title }}@endif
                    </div>
                    @if ($event->title && $event->ensemble)<div style="font-weight:bold;">{{ $event->ensemble }}</div>@endif
                    @if ($event->excerpt)<div style="font-size:14px;color:#5b524c;">{{ $event->excerpt }}</div>@endif
                    <div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6b625c;margin-top:4px;">
                        {{ $event->venue?->label }}@if ($event->price_info) · {{ $event->price_info }}@endif
                        @if ($event->status->publicBanner()) · <strong style="color:#b91c1c;">{{ $event->status->publicBanner() }}</strong>@endif
                    </div>
                    <div style="margin-top:6px;"><a href="{{ $event->url }}" style="color:{{ $color }};font-family:Arial,Helvetica,sans-serif;font-size:13px;">Détails du concert →</a></div>
                </td></tr>
            </table>
        @endforeach
    @endif

    <p style="margin-top:28px;font-size:15px;">Réservations : {{ config('acdm.contact.phone') }} · <a href="mailto:{{ config('acdm.contact.email') }}" style="color:#8b1e2d;">{{ config('acdm.contact.email') }}</a></p>

    <x-slot:footer>
        Vous recevez ce message parce que vous êtes inscrit à la newsletter de l'ACDM.
        <a href="{{ $unsubscribeUrl }}" style="color:#8b1e2d;">Se désinscrire</a> en un clic.<br><br>
    </x-slot:footer>
</x-mail-layout>
