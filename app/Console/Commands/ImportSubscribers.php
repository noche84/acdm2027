<?php

namespace App\Console\Commands;

use App\Enums\SubscriberStatus;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Importe une liste d'adresses depuis un CSV (export MailChimp ou autre) :
 * la première colonne qui contient une adresse e-mail est utilisée.
 *
 *   php artisan acdm:import-subscribers abonnes.csv --status=confirmed
 *   php artisan acdm:import-subscribers desinscrits.csv --status=unsubscribed
 */
class ImportSubscribers extends Command
{
    protected $signature = 'acdm:import-subscribers {file} {--status=confirmed : confirmed ou unsubscribed} {--source=mailchimp}';

    protected $description = 'Importe des abonnés ou des désinscrits depuis un fichier CSV';

    public function handle(): int
    {
        $status = SubscriberStatus::tryFrom($this->option('status'));
        if (! in_array($status, [SubscriberStatus::Confirmed, SubscriberStatus::Unsubscribed], true)) {
            $this->error('--status doit valoir confirmed ou unsubscribed');

            return self::FAILURE;
        }

        $handle = fopen($this->argument('file'), 'r');
        if (! $handle) {
            $this->error('Fichier illisible');

            return self::FAILURE;
        }

        $created = $updated = 0;
        while (($line = fgetcsv($handle, 0, $this->detectDelimiter($this->argument('file')))) !== false) {
            $email = collect($line)->map(fn ($v) => Str::lower(trim((string) $v)))->first(fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL));
            if (! $email) {
                continue;
            }

            $subscriber = Subscriber::firstOrNew(['email' => $email]);
            $isNew = ! $subscriber->exists;

            if ($status === SubscriberStatus::Unsubscribed) {
                // Une désinscription l'emporte toujours.
                $subscriber->fill(['source' => $subscriber->source ?? $this->option('source')]);
                $subscriber->status = SubscriberStatus::Unsubscribed;
                $subscriber->unsubscribed_at ??= now();
            } elseif ($isNew) {
                $subscriber->fill(['source' => $this->option('source')]);
                $subscriber->status = SubscriberStatus::Confirmed;
                $subscriber->confirmed_at = now();
            } else {
                continue; // déjà connu : on ne réabonne jamais quelqu'un qui s'est désinscrit
            }

            $subscriber->save();
            $isNew ? $created++ : $updated++;
        }
        fclose($handle);

        $this->info("{$created} adresses ajoutées, {$updated} mises à jour.");

        return self::SUCCESS;
    }

    private function detectDelimiter(string $file): string
    {
        $first = (string) fgets(fopen($file, 'r'));

        return substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
    }
}
