<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

/**
 * Remplace les commandes « php artisan » sur un hébergement sans SSH (OVH Perso).
 * Après l'envoi d'une nouvelle version par FTP : « Mettre à jour la base », puis « Vider les caches ».
 */
class Maintenance extends Page
{
    protected string $view = 'filament.pages.maintenance';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string | UnitEnum | null $navigationGroup = 'Administration';

    protected static ?string $title = 'Maintenance';

    protected static ?int $navigationSort = 10;

    public string $output = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /** @return array<string, string> */
    public function getInfos(): array
    {
        $pending = $this->pendingMigrations();

        return [
            'Version de PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Base de données' => DB::connection()->getDriverName().' · '.DB::connection()->getDatabaseName(),
            'Mises à jour de la base en attente' => $pending ? implode(', ', $pending) : 'aucune',
            'Ancienne table agenda présente' => Schema::hasTable('agenda') ? 'oui ('.DB::table('agenda')->count().' lignes)' : 'non',
            'Mode' => app()->environment().(config('app.debug') ? ' · DEBUG ACTIVÉ (à couper en production)' : ''),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('migrate')
                ->label('Mettre à jour la base')
                ->icon(Heroicon::OutlinedCircleStack)
                ->requiresConfirmation()
                ->modalDescription('Applique les changements de structure livrés avec la nouvelle version du site. Sans risque pour les données.')
                ->action(fn () => $this->runArtisan('migrate', ['--force' => true], 'Base de données à jour')),
            Action::make('clear')
                ->label('Vider les caches')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(fn () => $this->runArtisan('optimize:clear', [], 'Caches vidés')),
            Action::make('reimport')
                ->label('Réimporter l\'ancien agenda')
                ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
                ->color('gray')
                ->visible(fn () => Schema::hasTable('agenda'))
                ->requiresConfirmation()
                ->modalDescription('Relit l\'ancienne table « agenda » et met à jour les concerts importés (retrouvés par leur ancien numéro). Les concerts créés dans la nouvelle administration ne sont pas touchés, mais les modifications faites ici sur un concert importé seront écrasées.')
                ->action(fn () => $this->runArtisan('acdm:import-legacy', ['--from-db' => true], 'Import terminé')),
        ];
    }

    private function runArtisan(string $command, array $params, string $title): void
    {
        @set_time_limit(300);

        try {
            $code = Artisan::call($command, $params);
            $this->output = trim(Artisan::output());
            Notification::make()->title($code === 0 ? $title : 'Terminé avec des erreurs')->{$code === 0 ? 'success' : 'warning'}()->send();
        } catch (\Throwable $e) {
            $this->output = $e->getMessage();
            Notification::make()->title('Erreur')->body($e->getMessage())->danger()->persistent()->send();
        }
    }

    /** @return array<int, string> */
    private function pendingMigrations(): array
    {
        $files = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'));
        $ran = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('migration') : collect();

        return $files->diff($ran)->values()->all();
    }
}
