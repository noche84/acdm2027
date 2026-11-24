<?php

return [

    /*
    | Identité légale de l'ASBL (Banque-Carrefour des Entreprises).
    | Source : https://kbopub.economie.fgov.be — fiche 0452.638.226
    */
    'legal' => [
        'name' => 'Les Amis des Concerts du Dimanche Matin',
        'short_name' => 'ACDM',
        'form' => 'ASBL',
        'enterprise_number' => '0452.638.226',
        'street' => 'Rue Auguste-Hock 4',
        'postal_code' => '4020',
        'city' => 'Liège',
        'country' => 'Belgique',
        'rpm' => 'RPM Liège, division Liège',
        'founded' => '1994-03-08',
    ],

    'contact' => [
        'email' => env('ACDM_CONTACT_EMAIL', 'cecile.leleux.be@gmail.com'),
        'booking_email' => env('ACDM_BOOKING_EMAIL', 'valdescamps@hotmail.com'),
        'phone' => env('ACDM_PHONE', '0499 25 95 30'),
        'phone_e164' => env('ACDM_PHONE_E164', '+32499259530'),
        'privacy_email' => env('ACDM_PRIVACY_EMAIL', 'cecile.leleux.be@gmail.com'),
    ],

    'hosting' => [
        'name' => 'OVH SAS',
        'address' => '2 rue Kellermann, 59100 Roubaix, France',
        'url' => 'https://www.ovhcloud.com',
    ],

    /* Pages officielles sur les réseaux (aident Google à relier le site à l'association). */
    'social' => [
        'facebook' => env('ACDM_FACEBOOK_URL'),
        'instagram' => env('ACDM_INSTAGRAM_URL'),
        'youtube' => env('ACDM_YOUTUBE_URL'),
    ],

    /* Groupe Facebook : lien affiché dans le pied de page, mais pas déclaré à Google comme
       identité de l'association (un groupe n'est pas une page officielle). */
    'facebook_group' => env('ACDM_FACEBOOK_GROUP_URL', 'https://www.facebook.com/groups/638213836717180'),

    /* Clé de la page /installation (hébergement sans SSH). À vider une fois le site installé. */
    'install_token' => env('INSTALL_TOKEN', ''),

    /* Galerie iGalerie existante, conservée telle quelle dans public/igalerie. */
    'gallery_url' => env('ACDM_GALLERY_URL', '/igalerie/'),

    'newsletter' => [
        /* Nombre maximum d'envois par exécution (limite SMTP OVH : ~200/heure/boîte). */
        'batch_size' => (int) env('NEWSLETTER_BATCH_SIZE', 150),
        /* Pause entre deux envois, en millisecondes. */
        'pause_ms' => (int) env('NEWSLETTER_PAUSE_MS', 250),
    ],
];
