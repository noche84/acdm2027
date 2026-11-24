#!/usr/bin/env bash
# Fabrique une archive prête à envoyer par FTP (hébergement sans SSH : OVH Perso).
#   ./build-deploy.sh            → build/acdm-deploy.zip  (première mise en ligne, avec .env)
#   ./build-deploy.sh --update   → build/acdm-update.zip  (mise à jour : sans .env, sans uploads)
# Les dépendances sont résolues pour PHP 8.4 (config.platform de composer.json), même si
# votre machine a une autre version de PHP.
set -euo pipefail
cd "$(dirname "$0")"

MODE=${1:-}
SRC=$(pwd)
BUILD="$SRC/build"
DIR="$BUILD/acdm-laravel"

rm -rf "$DIR" && mkdir -p "$DIR"
echo "▶ Copie du projet…"
tar -cf - \
  --exclude='./build' --exclude='./vendor' --exclude='./node_modules' --exclude='./.git' \
  --exclude='./.env' --exclude='./.env.backup' --exclude='./.env.production' --exclude='./database/*.sqlite' \
  --exclude='./storage/logs/*.log' --exclude='./storage/logs/*.md' \
  --exclude='./storage/framework/sessions/*' --exclude='./storage/framework/views/*' --exclude='./storage/framework/cache/data/*' \
  --exclude='./bootstrap/cache/*.php' --exclude='./public/storage' --exclude='./public/hot' \
  --exclude='./storage/legacy/*.sql' --exclude='./composer.local.*' --exclude='./tests' --exclude='./phpunit.xml' \
  --exclude='./public/js/filament' --exclude='./public/css/filament' --exclude='./public/fonts/filament' \
  . | ( cd "$DIR" && tar -xf - )
mkdir -p "$DIR/storage/framework/"{sessions,views,cache/data} "$DIR/storage/logs" "$DIR/bootstrap/cache" "$DIR/public/uploads"

echo "▶ Dépendances de production (PHP 8.4)…"
( cd "$DIR" && { composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null \
    || composer update --no-dev --optimize-autoloader --no-interaction --quiet; } )

if [ "$MODE" = "--update" ]; then
  rm -rf "$DIR/public/uploads" "$DIR/public/igalerie"
  ZIP="$BUILD/acdm-update.zip"
else
  if [ -f "$SRC/.env.production" ]; then
    cp "$SRC/.env.production" "$DIR/.env"
  else
    cp .env.ovh.example "$DIR/.env"
    KEY=$(cd "$DIR" && php artisan key:generate --show --no-ansi)
    TOKEN=$(php -r 'echo bin2hex(random_bytes(16));')
    sed -i.bak -e "s|^APP_KEY=.*|APP_KEY=$KEY|" -e "s|^INSTALL_TOKEN=.*|INSTALL_TOKEN=$TOKEN|" "$DIR/.env" && rm -f "$DIR/.env.bak"
    cp "$DIR/.env" "$SRC/.env.production"
    echo "  → .env.production créé (APP_KEY et INSTALL_TOKEN générés)."
  fi
  # Les mots de passe sont demandés ici et restent sur cette machine (jamais affichés).
  if grep -qE '^(DB_PASSWORD|MAIL_PASSWORD)=$' "$SRC/.env.production" && [ -t 0 ]; then
    echo
    echo "▶ Deux informations de votre espace client OVH (copier-coller) :"
    read -rsp "  Mot de passe de la base MySQL (Hébergements > Bases de données) : " DBP; echo
    read -rp  "  Adresse de la boîte d'envoi [newsletter@acdm.eu] : " MBOX; MBOX=${MBOX:-newsletter@acdm.eu}
    read -rsp "  Mot de passe de cette boîte e-mail : " MBP; echo
    DBP="$DBP" MBOX="$MBOX" MBP="$MBP" php -r '
      $f = $argv[1]; $env = file_get_contents($f);
      $q = fn ($v) => "\"".addcslashes($v, "\\\"\$")."\"";
      foreach (["DB_PASSWORD" => getenv("DBP"), "MAIL_PASSWORD" => getenv("MBP"),
                "MAIL_USERNAME" => getenv("MBOX"), "MAIL_FROM_ADDRESS" => getenv("MBOX")] as $k => $v) {
          if ($v !== "" && $v !== false) { $env = preg_replace_callback("/^$k=.*$/m", fn () => "$k=".$q($v), $env); }
      }
      file_put_contents($f, $env);' "$SRC/.env.production"
    cp "$SRC/.env.production" "$DIR/.env"
    echo "  → enregistré dans .env.production"
  fi
  if grep -qE '^(DB_PASSWORD|MAIL_PASSWORD)=$' "$DIR/.env"; then
    echo "  ⚠ DB_PASSWORD ou MAIL_PASSWORD est vide dans .env.production : complétez-le puis relancez."
  fi
  ZIP="$BUILD/acdm-deploy.zip"
fi

rm -f "$ZIP"
( cd "$BUILD" && zip -qr "$ZIP" acdm-laravel )
echo "✔ $ZIP ($(du -h "$ZIP" | cut -f1))"
if [ "$MODE" != "--update" ]; then
  TOKEN=$(grep '^INSTALL_TOKEN=' "$DIR/.env" | cut -d= -f2)
  echo "  Après l'envoi FTP : https://www.acdm.eu/installation?cle=$TOKEN"
fi
