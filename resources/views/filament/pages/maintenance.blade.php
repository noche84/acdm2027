<x-filament-panels::page>
    <x-filament::section heading="État du site">
        <dl style="display:grid;grid-template-columns:max-content 1fr;gap:.4rem 1.5rem;">
            @foreach ($this->getInfos() as $label => $value)
                <dt style="font-weight:600;">{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            @endforeach
        </dl>
    </x-filament::section>

    <x-filament::section heading="Après l'envoi d'une nouvelle version par FTP" collapsible>
        <ol style="list-style:decimal;padding-left:1.25rem;line-height:1.8;">
            <li>Envoyer les fichiers (sans écraser <code>.env</code> ni <code>public/uploads</code> ni <code>public/igalerie</code>).</li>
            <li>Cliquer sur « Mettre à jour la base ».</li>
            <li>Cliquer sur « Vider les caches ».</li>
        </ol>
    </x-filament::section>

    @if ($output)
        <x-filament::section heading="Résultat">
            <pre style="white-space:pre-wrap;font-size:.85rem;max-height:28rem;overflow:auto;">{{ $output }}</pre>
        </x-filament::section>
    @endif
</x-filament-panels::page>
