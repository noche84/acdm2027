@extends('layouts.site', ['title' => 'Se désinscrire'])

@section('content')
<section class="wrap section">
    <div class="narrow notice-page">
        <h1>Se désinscrire de la newsletter</h1>
        @if ($subscriber)
            <p class="lead">Vous ne souhaitez plus recevoir la newsletter à l'adresse <strong>{{ $subscriber->email }}</strong> ?</p>
            <form method="post" action="{{ route('newsletter.unsubscribe.do', $token) }}">
                @csrf
                <button type="submit" class="btn">Oui, me désinscrire</button>
            </form>
        @else
            <p class="lead">Ce lien n'est plus valable. Si vous recevez encore nos e-mails, écrivez-nous à <a href="mailto:{{ config('acdm.contact.email') }}">{{ config('acdm.contact.email') }}</a>.</p>
        @endif
    </div>
</section>
@endsection
