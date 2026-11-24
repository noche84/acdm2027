@extends('layouts.site', ['title' => 'Nos partenaires'])

@section('content')
<header class="page-head">
    <div class="wrap">
        <h1>Nos partenaires</h1>
        <p class="lead">Les concerts de l'ACDM et de FoliaMusica existent grâce à eux. Merci !</p>
    </div>
</header>
<section class="wrap section">
    @foreach (\App\Models\Partner::CATEGORIES as $key => $label)
        @if ($groups->has($key))
            <h2 class="section-title">{{ $label }}</h2>
            <ul class="partners">
                @foreach ($groups[$key] as $partner)
                    <li>
                        @if ($partner->url)<a href="{{ $partner->url }}" target="_blank" rel="noopener">@endif
                        @if ($partner->logo_url)
                            <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy">
                        @endif
                        <span>{{ $partner->name }}</span>
                        @if ($partner->url)</a>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endforeach
</section>
@endsection
