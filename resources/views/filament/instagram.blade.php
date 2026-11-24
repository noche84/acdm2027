@php
    $v = $event->updated_at?->timestamp;
    $formats = [
        'post' => ['Publication', 'Pour le fil Instagram et Facebook (format portrait, le plus visible).'],
        'story' => ['Story', 'Pour les stories : idéal la veille ou le matin du concert, en rappel.'],
    ];
@endphp

<div style="display:flex;flex-direction:column;gap:1.25rem;font-size:.9rem;line-height:1.5">
    <p style="margin:0;color:rgb(107 114 128)">
        Deux images aux couleurs du site, prêtes à publier. Téléchargez celle qu'il vous faut,
        puis publiez-la avec le texte proposé plus bas. Elles reprennent les informations
        <strong>enregistrées</strong> du concert : après une modification, enregistrez puis rouvrez cette fenêtre.
    </p>

    {{-- Les deux images --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem;align-items:start">
        @foreach ($formats as $format => [$label, $help])
            @php($src = route('admin.instagram', ['event' => $event->id, 'format' => $format]).'?v='.$v)
            <div style="border:1px solid rgba(127,127,127,.25);border-radius:.75rem;padding:.75rem;display:flex;flex-direction:column;gap:.6rem">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.5rem">
                    <strong>{{ $label }}</strong>
                    <span style="font-size:.75rem;color:rgb(107 114 128)">{{ implode(' × ', \App\Services\InstagramImage::FORMATS[$format]) }} px</span>
                </div>
                <a href="{{ $src }}" target="_blank" title="Voir en grand" style="display:block;background:rgba(127,127,127,.08);border-radius:.5rem;overflow:hidden">
                    <img src="{{ $src }}" alt="Aperçu {{ $label }}" loading="lazy"
                         style="display:block;width:100%;height:{{ $format === 'story' ? '22rem' : '17rem' }};object-fit:contain">
                </a>
                <p style="margin:0;font-size:.8rem;color:rgb(107 114 128)">{{ $help }}</p>
                <x-filament::button tag="a" :href="$src.'&telecharger=1'" icon="heroicon-o-arrow-down-tray" size="sm">
                    Télécharger
                </x-filament::button>
            </div>
        @endforeach
    </div>

    {{-- Le texte à copier --}}
    <div x-data="{ copied: false }" style="display:flex;flex-direction:column;gap:.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
            <strong>Texte à publier avec l'image</strong>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-clipboard-document"
                x-on:click="navigator.clipboard.writeText($refs.caption.value); copied = true; setTimeout(() => copied = false, 2500)">
                <span x-show="! copied">Copier le texte</span>
                <span x-show="copied" x-cloak>✓ Copié !</span>
            </x-filament::button>
        </div>
        <textarea x-ref="caption" rows="10" readonly
            style="width:100%;border:1px solid rgba(127,127,127,.3);border-radius:.5rem;padding:.6rem .75rem;font:inherit;font-size:.85rem;background:transparent;color:inherit;resize:vertical">{{ $caption }}</textarea>
        <p style="margin:0;font-size:.8rem;color:rgb(107 114 128)">
            Vous pouvez le modifier dans Instagram avant de publier. Les liens n'y sont pas cliquables :
            l'adresse du site suffit.
        </p>
    </div>

    {{-- Conseils --}}
    <div style="border-radius:.75rem;padding:.75rem 1rem;background:rgba(139,30,45,.07)">
        <strong>Pour toucher plus de monde</strong>
        <ul style="margin:.4rem 0 0;padding-left:1.1rem;list-style:disc">
            <li><strong>Identifiez les musiciens</strong> (leur @compte) ou invitez-les en « collaborateurs » : la publication apparaîtra aussi chez leurs abonnés.</li>
            <li>Publiez <strong>une à deux semaines avant</strong> le concert, puis la story <strong>la veille</strong> en rappel.</li>
            <li>Dans Instagram, activez le partage vers Facebook : une seule publication alimente les deux.</li>
            <li>Pas d'affiche ? L'image est quand même complète. S'il y en a une, elle est ajoutée automatiquement.</li>
        </ul>
    </div>
</div>
