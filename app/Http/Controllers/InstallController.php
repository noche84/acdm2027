<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Installation par le navigateur, pour les hébergements sans SSH (OVH Perso).
 *
 * Activée uniquement si INSTALL_TOKEN est rempli dans .env :
 *   https://www.acdm.eu/installation?cle=VOTRE_CLE
 * Une fois l'installation faite, videz INSTALL_TOKEN : la page renvoie alors une erreur 404.
 *
 * Ces routes n'utilisent ni session ni cache (les tables n'existent pas encore) :
 * la clé secrète, envoyée à chaque requête, tient lieu de protection.
 */
class InstallController extends Controller
{
    public function show(Request $request): View
    {
        $this->authorizeToken($request);

        return view('install.index', $this->state() + ['token' => $request->query('cle'), 'log' => null, 'errors' => []]);
    }

    public function run(Request $request): View
    {
        $this->authorizeToken($request);
        @set_time_limit(300);

        $errors = [];
        $log = [];
        $state = $this->state();

        $wantsAdmin = ! $state['hasAdmin'];
        if ($wantsAdmin) {
            $name = trim((string) $request->input('name'));
            $email = strtolower(trim((string) $request->input('email')));
            $password = (string) $request->input('password');

            if ($name === '') {
                $errors[] = 'Indiquez votre nom.';
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            }
            if (mb_strlen($password) < 12) {
                $errors[] = 'Le mot de passe doit faire au moins 12 caractères.';
            }
        }

        if (! $errors) {
            try {
                $log[] = $this->artisan('migrate', ['--force' => true]);
                $log[] = $this->artisan('db:seed', ['--force' => true]);

                if ($request->boolean('import') && Schema::hasTable('agenda')) {
                    $log[] = $this->artisan('acdm:import-legacy', ['--from-db' => true]);
                }

                if ($wantsAdmin) {
                    User::updateOrCreate(['email' => $email], [
                        'name' => $name,
                        'password' => $password,
                        'role' => UserRole::Admin,
                        'is_active' => true,
                    ]);
                    $log[] = "Compte administrateur créé : {$email}";
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return view('install.index', $this->state() + [
            'token' => $request->input('cle'),
            'log' => $log ? implode("\n\n", array_filter($log)) : null,
            'errors' => $errors,
            'done' => ! $errors,
        ]);
    }

    private function authorizeToken(Request $request): void
    {
        $expected = (string) config('acdm.install_token');
        $given = (string) ($request->input('cle') ?? $request->query('cle'));

        abort_if($expected === '' || ! hash_equals($expected, $given), 404);
    }

    /** @return array<string, mixed> */
    private function state(): array
    {
        try {
            DB::connection()->getPdo();
            $dbOk = true;
            $dbError = null;
        } catch (\Throwable $e) {
            $dbOk = false;
            $dbError = $e->getMessage();
        }

        $installed = $dbOk && Schema::hasTable('events');

        return [
            'phpVersion' => PHP_VERSION,
            'phpOk' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'extensions' => collect(['pdo_mysql', 'mbstring', 'intl', 'xml', 'zip', 'curl', 'fileinfo', 'gd'])->mapWithKeys(fn ($e) => [$e => extension_loaded($e)])->all(),
            'writable' => [
                'storage' => is_writable(storage_path()),
                'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
                'public/uploads' => is_dir(public_path('uploads')) ? is_writable(public_path('uploads')) : is_writable(public_path()),
            ],
            'appKey' => filled(config('app.key')),
            'dbOk' => $dbOk,
            'dbError' => $dbError,
            'dbName' => $dbOk ? DB::connection()->getDatabaseName() : null,
            'installed' => $installed,
            'hasAdmin' => $installed && User::where('role', UserRole::Admin->value)->exists(),
            'legacyRows' => $dbOk && Schema::hasTable('agenda') ? DB::table('agenda')->count() : null,
            'events' => $installed ? Event::count() : 0,
        ];
    }

    private function artisan(string $command, array $params): string
    {
        Artisan::call($command, $params);

        return '$ php artisan '.$command."\n".trim(Artisan::output());
    }
}
