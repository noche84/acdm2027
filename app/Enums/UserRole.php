<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    /** Accès complet, y compris les utilisateurs et les réglages. */
    case Admin = 'admin';

    /** Encode les concerts, les pages, les partenaires et prépare les newsletters. */
    case Editor = 'editor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Editor => 'Éditeur',
        };
    }
}
