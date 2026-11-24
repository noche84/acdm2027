@extends('layouts.site', ['title' => 'Newsletter'])

@section('content')
<header class="page-head">
    <div class="wrap">
        <h1>La newsletter</h1>
        <p class="lead">Recevez le programme des concerts de l'ACDM et de FoliaMusica, quelques fois par an.</p>
    </div>
</header>
<section class="wrap section">
    <div class="narrow">
        @if (session('status') === 'check-email')
            <div class="notice notice-ok" role="status">
                <strong>Plus qu'une étape !</strong> Nous venons de vous envoyer un e-mail. Cliquez sur le lien qu'il contient pour confirmer votre inscription (pensez à vérifier vos indésirables).
            </div>
        @endif

        <form method="post" action="{{ route('newsletter.subscribe') }}" class="form-card" novalidate>
            @csrf
            <label for="email">Votre adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror

            <div class="hp" aria-hidden="true">
                <label for="website">Ne pas remplir</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <label class="checkbox">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                <span>J'accepte de recevoir la newsletter de l'ASBL Les Amis des Concerts du Dimanche Matin. Mon adresse ne sera jamais transmise à des tiers et je peux me désinscrire à tout moment. <a href="{{ route('page', 'vie-privee') }}">En savoir plus</a></span>
            </label>
            @error('consent')<p class="field-error">{{ $message }}</p>@enderror

            <button type="submit" class="btn">Je m'inscris</button>
        </form>
    </div>
</section>
@endsection
