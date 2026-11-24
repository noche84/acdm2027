<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Les concerts et les pages ont une adresse publique par « slug » (getRouteKeyName), mais dans
 * l'administration on les retrouve par leur identifiant ($recordRouteKeyName = 'id') : le slug
 * peut changer quand on modifie la date ou le titre.
 *
 * Filament construit pourtant ses liens avec la clé publique du modèle (le slug) : on remplace
 * donc l'enregistrement par son identifiant avant de fabriquer l'adresse.
 */
trait UsesIdInUrls
{
    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        if (($parameters['record'] ?? null) instanceof Model) {
            $parameters['record'] = $parameters['record']->getKey();
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }
}
