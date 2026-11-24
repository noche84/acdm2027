<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventStatus: string implements HasColor, HasLabel
{
    case Scheduled = 'scheduled';
    case SoldOut = 'sold_out';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => 'Prévu',
            self::SoldOut => 'Complet',
            self::Postponed => 'Reporté',
            self::Cancelled => 'Annulé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scheduled => 'success',
            self::SoldOut => 'warning',
            self::Postponed => 'info',
            self::Cancelled => 'danger',
        };
    }

    /** Libellé affiché en bandeau sur le site public (null = rien à afficher). */
    public function publicBanner(): ?string
    {
        return match ($this) {
            self::Scheduled => null,
            self::SoldOut => 'Complet',
            self::Postponed => 'Reporté',
            self::Cancelled => 'Annulé',
        };
    }
}
