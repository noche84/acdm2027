<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Les deux « marques » visibles du public.
 *
 * - ACDM : les concerts du 2e dimanche du mois, à 11h.
 * - FoliaMusica : tous les autres concerts, où et quand elle le souhaite.
 */
enum Brand: string implements HasColor, HasLabel
{
    case Acdm = 'acdm';
    case FoliaMusica = 'foliamusica';

    public function getLabel(): string
    {
        return match ($this) {
            self::Acdm => 'ACDM',
            self::FoliaMusica => 'FoliaMusica',
        };
    }

    public function fullName(): string
    {
        return match ($this) {
            self::Acdm => 'Les Amis des Concerts du Dimanche Matin',
            self::FoliaMusica => 'FoliaMusica',
        };
    }

    public function tagline(): string
    {
        return match ($this) {
            self::Acdm => 'Musique de chambre, chaque 2e dimanche du mois à 11h',
            self::FoliaMusica => 'Des concerts exceptionnels, là où la musique nous emmène',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Acdm => 'primary',
            self::FoliaMusica => 'success',
        };
    }

    /** Classe CSS utilisée sur le site public. */
    public function cssClass(): string
    {
        return 'brand-'.$this->value;
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Acdm => 'agenda',
            self::FoliaMusica => 'foliamusica',
        };
    }
}
