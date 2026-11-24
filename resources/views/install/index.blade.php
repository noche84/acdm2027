<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Installation · ACDM</title>
<style>
    body { font: 16px/1.55 system-ui, sans-serif; background: #f6f2ee; color: #2b2522; margin: 0; }
    main { max-width: 46rem; margin: 2rem auto; background: #fff; border-top: 6px solid #8b1e2d; border-radius: 6px; padding: 1.5rem 2rem 2rem; }
    h1 { font-family: Georgia, serif; color: #8b1e2d; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
    td { padding: .35rem .5rem; border-bottom: 1px solid #eee; }
    .ok { color: #15803d; font-weight: 600; } .ko { color: #b91c1c; font-weight: 600; }
    label { display: block; margin: .8rem 0 .2rem; font-weight: 600; }
    input[type=text], input[type=email], input[type=password] { width: 100%; padding: .55rem; border: 1px solid #ccc; border-radius: 4px; font: inherit; box-sizing: border-box; }
    button { margin-top: 1.2rem; background: #8b1e2d; color: #fff; border: 0; padding: .7rem 1.4rem; border-radius: 4px; font: 600 1rem system-ui; cursor: pointer; }
    pre { background: #2b2522; color: #f1e8e2; padding: 1rem; border-radius: 4px; white-space: pre-wrap; font-size: .82rem; max-height: 24rem; overflow: auto; }
    .box { padding: .8rem 1rem; border-radius: 4px; margin: 1rem 0; }
    .err { background: #fee2e2; } .done { background: #dcfce7; } .warn { background: #fef3c7; }
</style>
</head>
<body>
<main>
    <h1>Installation du site ACDM</h1>

    @foreach ($errors as $error)
        <div class="box err">{{ $error }}</div>
    @endforeach

    @if (! empty($done))
        <div class="box done">
            <strong>Installation terminée.</strong> Connectez-vous à <a href="{{ url('/admin') }}">l'administration</a>.<br>
            <strong>Important :</strong> videz maintenant la ligne <code>INSTALL_TOKEN=</code> dans le fichier <code>.env</code> pour fermer cette page.
        </div>
    @endif

    <table>
        <tr><td>PHP {{ $phpVersion }}</td><td class="{{ $phpOk ? 'ok' : 'ko' }}">{{ $phpOk ? 'OK' : '8.3 minimum requis' }}</td></tr>
        @foreach ($extensions as $ext => $loaded)
            <tr><td>Extension {{ $ext }}</td><td class="{{ $loaded ? 'ok' : 'ko' }}">{{ $loaded ? 'OK' : 'manquante' }}</td></tr>
        @endforeach
        @foreach ($writable as $dir => $ok)
            <tr><td>Écriture dans {{ $dir }}</td><td class="{{ $ok ? 'ok' : 'ko' }}">{{ $ok ? 'OK' : 'impossible (droits FTP 755)' }}</td></tr>
        @endforeach
        <tr><td>Clé de l'application (APP_KEY)</td><td class="{{ $appKey ? 'ok' : 'ko' }}">{{ $appKey ? 'OK' : 'absente du .env' }}</td></tr>
        <tr><td>Base de données</td><td class="{{ $dbOk ? 'ok' : 'ko' }}">{{ $dbOk ? $dbName : $dbError }}</td></tr>
        <tr><td>Tables du nouveau site</td><td>{{ $installed ? 'présentes ('.$events.' concerts)' : 'à créer' }}</td></tr>
        <tr><td>Ancienne table agenda</td><td>{{ $legacyRows !== null ? $legacyRows.' concerts' : 'absente' }}</td></tr>
    </table>

    @if ($dbOk && $appKey && $phpOk && empty($done))
        <form method="post" action="{{ url('/installation') }}">
            <input type="hidden" name="cle" value="{{ $token }}">

            @if ($legacyRows !== null)
                <label style="font-weight:400"><input type="checkbox" name="import" value="1" checked> Importer les {{ $legacyRows }} concerts et les abonnés de l'ancien site</label>
            @endif

            @if (! $hasAdmin)
                <h2>Votre compte administrateur</h2>
                <label for="name">Nom</label><input id="name" type="text" name="name" required value="{{ request('name') }}">
                <label for="email">E-mail</label><input id="email" type="email" name="email" required value="{{ request('email') }}">
                <label for="password">Mot de passe (12 caractères minimum)</label><input id="password" type="password" name="password" required minlength="12">
            @else
                <p class="box warn">Un administrateur existe déjà : le bouton ci-dessous applique seulement les mises à jour de la base.</p>
            @endif

            <button type="submit">{{ $installed ? 'Mettre à jour' : 'Installer le site' }}</button>
        </form>
    @endif

    @if ($log)
        <h2>Journal</h2>
        <pre>{{ $log }}</pre>
    @endif
</main>
</body>
</html>
