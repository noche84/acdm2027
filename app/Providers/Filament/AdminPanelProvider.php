<?php

namespace App\Providers\Filament;

use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->brandName('ACDM · FoliaMusica')
            ->favicon(asset('favicon.svg'))
            ->brandLogo(asset('images/logo-acdm.svg'))
            ->darkModeBrandLogo(asset('images/logo-acdm-blanc.svg'))
            ->brandLogoHeight('2.75rem')
            ->colors([
                'primary' => Color::generateV3Palette('#a3283a'),
                'success' => Color::generateV3Palette('#c2620f'),
                'gray' => Color::Stone,
            ])
            ->font('ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif', provider: LocalFontProvider::class)
            ->defaultAvatarProvider(\App\Filament\Support\InitialsAvatarProvider::class)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups(['Programmation', 'Contenu du site', 'Newsletter', 'Administration'])
            ->navigationItems([
                NavigationItem::make('Voir le site')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->sort(100),
                NavigationItem::make('Galerie photos')
                    ->url(fn () => config('acdm.gallery_url'), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedPhoto)
                    ->group('Contenu du site')
                    ->sort(90),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
