<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateUser extends Command
{
    protected $signature = 'acdm:user {--email=} {--name=} {--role=admin}';

    protected $description = 'Crée (ou met à jour) un utilisateur de l\'administration';

    public function handle(): int
    {
        $email = $this->option('email') ?: text('Adresse e-mail', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Adresse invalide');
        $name = $this->option('name') ?: text('Nom affiché', required: true);
        $role = UserRole::tryFrom((string) $this->option('role')) ?? UserRole::from(select('Rôle', [UserRole::Admin->value => 'Administrateur', UserRole::Editor->value => 'Éditeur']));
        $pwd = password('Mot de passe (12 caractères minimum)', required: true, validate: fn ($v) => mb_strlen($v) >= 12 ? null : 'Au moins 12 caractères');

        $user = User::updateOrCreate(['email' => strtolower($email)], [
            'name' => $name,
            'password' => $pwd,
            'role' => $role,
            'is_active' => true,
        ]);

        $this->info("Utilisateur {$user->email} prêt ({$role->getLabel()}). Connexion : ".url('/admin'));

        return self::SUCCESS;
    }
}
