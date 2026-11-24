#!/usr/bin/env bash
# Installation locale du site ACDM · FoliaMusica (Linux ou macOS), base SQLite.
# Usage : ./install-local.sh   puis   php artisan serve
set -euo pipefail
cd "$(dirname "$0")"

echo "▶ Vérification de PHP…"
php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
  || { echo "✖ PHP 8.3 minimum requis (trouvé : $(php -r 'echo PHP_VERSION;'))"; exit 1; }

missing=""
for ext in intl mbstring pdo_sqlite xml zip curl; do
  php -m | grep -qi "^${ext}$" || missing="$missing $ext"
done
if [ -n "$missing" ]; then
  echo "✖ Extensions PHP manquantes :$missing"
  echo "  Ubuntu/Debian : sudo apt install$(for e in $missing; do [ $e = pdo_sqlite ] && e=sqlite3; printf ' php-%s' $e; done)"
  exit 1
fi
command -v composer >/dev/null || { echo "✖ Composer introuvable : https://getcomposer.org/download/"; exit 1; }

echo "▶ Dépendances (composer update)…"
composer update --no-interaction

[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate
sed -i.bak 's|^APP_URL=.*|APP_URL=http://127.0.0.1:8000|' .env && rm -f .env.bak

echo "▶ Base de données SQLite…"
touch database/database.sqlite
php artisan migrate --seed --force

if [ -f storage/legacy/agenda.sql ]; then
  echo "▶ Import de l'ancien agenda…"
  opt=""
  [ -f storage/legacy/newsletter.sql ] && opt="--newsletter=storage/legacy/newsletter.sql"
  php artisan acdm:import-legacy storage/legacy/agenda.sql $opt
fi

echo "▶ Création de votre compte administrateur…"
php artisan acdm:user --role=admin

echo
echo "✔ Terminé. Lancez :  php artisan serve"
echo "  Site  : http://127.0.0.1:8000"
echo "  Admin : http://127.0.0.1:8000/admin"
echo "  Les e-mails (confirmation newsletter, envois) sont écrits dans storage/logs/laravel.log"
