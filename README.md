# Site ACDM · FoliaMusica

Nouveau site des **Amis des Concerts du Dimanche Matin** (ACDM) et de **FoliaMusica**.
Il remplace le site de 2012 : Laravel 13, administration Filament 5, PHP 8.3 minimum (dépendances calculées pour PHP 8.4, la version d'OVH).

- **ACDM** : les concerts du 2e dimanche du mois à 11h (Boverie) → page `/agenda`
- **FoliaMusica** : tous les autres concerts (Votre Palais, Saint-Vincent, cathédrale…) → page `/foliamusica`
- Un concert passe **automatiquement** de l'agenda à `/historique` le lendemain de sa date.
- Galerie : l'iGalerie existante est conservée telle quelle dans `public/igalerie`.
- Administration : `/admin` (concerts, séries, lieux, pages, partenaires, newsletter, abonnés, utilisateurs).

## 1. Installation locale (MAMP)

```bash
cd /Applications/MAMP/htdocs/acdm-laravel
composer update            # pas de composer.lock fourni : Composer choisit les versions adaptées à votre PHP
cp .env.example .env
php artisan key:generate
```

Base de données : SQLite par défaut (`touch database/database.sqlite`), ou MySQL de MAMP dans `.env` :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=acdm
DB_USERNAME=root
DB_PASSWORD=root
```

Puis :

```bash
php artisan migrate --seed       # tables + lieux, séries, pages, partenaires
php artisan acdm:user            # crée votre compte administrateur
cp -R ../ACDM2/igalerie public/igalerie
php artisan serve                # http://127.0.0.1:8000  (ou racine MAMP → acdm-laravel/public)
```

## 2. Reprendre les données de l'ancien site

Exportez depuis phpMyAdmin (OVH) les tables `agenda` et `newsletter` **après vos corrections**, puis :

```bash
php artisan acdm:import-legacy storage/legacy/agenda.sql --newsletter=storage/legacy/newsletter.sql
```

- Relançable sans risque : chaque concert est retrouvé par son ancien `id` et mis à jour.
- Un rapport d'anomalies est écrit dans `storage/logs/import-legacy-*.md` (dates improbables, doublons, heures manquantes…).
- Les abonnés MailChimp désinscrits doivent être marqués pour ne plus jamais leur écrire :

```bash
php artisan acdm:import-subscribers desinscrits-mailchimp.csv --status=unsubscribed
php artisan acdm:import-subscribers abonnes-mailchimp.csv --status=confirmed
```

## 3. Mise en ligne sans SSH (OVH Perso)

L'offre Perso d'OVH n'a ni SSH ni Composer : tout se prépare sur votre ordinateur, puis s'envoie par FTP.
L'installation (tables, import de l'ancien agenda, compte administrateur) se fait ensuite **dans le navigateur**.

### Première mise en ligne

1. Sur votre ordinateur : `./build-deploy.sh`
   - crée `build/acdm-deploy.zip` avec `vendor/` (dépendances calculées pour **PHP 8.4**, la version d'OVH) ;
   - crée `.env.production` (APP_KEY et clé d'installation générées) ;
   - **vous demande deux mots de passe à copier-coller** depuis l'espace client OVH : celui de la base MySQL
     et celui de la boîte d'envoi (`newsletter@acdm.eu` par défaut, à créer avant dans *Web Cloud → E-mails*).
     Ils sont enregistrés dans `.env.production`, sur votre machine uniquement.
2. Décompressez l'archive et envoyez le dossier `acdm-laravel/` par FTP (FileZilla), à côté de l'ancien site.
3. Copiez l'ancien dossier `igalerie/` dans `acdm-laravel/public/igalerie/`.
4. Espace client OVH → Hébergements → **Multisite** → `www.acdm.eu` → dossier racine : **`acdm-laravel/public`**.
   Le fichier `public/.ovhconfig` active PHP 8.4 pour ce site seulement (OVH le lit dans le dossier visé par le multisite) : l'ancien site garde sa version de PHP.
5. Ouvrez l'adresse affichée par le script : `https://www.acdm.eu/installation?cle=…`
   - vérifie PHP, les extensions, les droits d'écriture et la connexion MySQL ;
   - crée les tables (la base `acdmikvnliege` est réutilisée, les anciennes tables `agenda` et `newsletter` ne sont pas modifiées) ;
   - importe les concerts et les abonnés **directement depuis l'ancienne table**, sans export ;
   - crée votre compte administrateur.
6. **Fermez l'installateur** : videz `INSTALL_TOKEN=` dans le `.env` du serveur (via FTP).
7. Changez le mot de passe MySQL (l'ancien est écrit en clair dans les fichiers de 2012) et mettez à jour `DB_PASSWORD`.

Sur votre ordinateur, faites une fois `composer update` après avoir reçu cette version : `composer.json` vise désormais PHP 8.4
(`config.platform`), ce qui garantit que l'archive tourne chez OVH même si votre PHP local est plus récent.

### Mises à jour suivantes

1. `./build-deploy.sh --update` → `build/acdm-update.zip` (sans `.env`, sans `public/uploads`, sans `igalerie`).
2. Envoyez les fichiers par FTP en écrasant les anciens.
3. Administration → **Maintenance** → « Mettre à jour la base », puis « Vider les caches ».

### Si vous passez chez Infomaniak (ou en OVH Pro)

Le SSH et Composer y sont disponibles : méthode classique (`composer install --no-dev`, `php artisan migrate --force`)
ou exactement la même méthode que ci-dessus. Pensez à faire pointer le site sur le dossier `public`.

### Fichiers envoyés depuis l'admin

Affiches et logos sont enregistrés dans `public/uploads/` (pas de lien symbolique `storage:link`, impossible sans SSH).
**Ne jamais écraser ce dossier** lors d'une mise à jour, et l'inclure dans vos sauvegardes.

## 4. Newsletter (RGPD)

- Inscription en **double opt-in** : case de consentement + e-mail de confirmation. Les demandes non confirmées sont effacées après 30 jours.
- Chaque envoi contient un lien de désinscription et l'en-tête `List-Unsubscribe` (désinscription en un clic dans Gmail/Outlook).
- Les e-mails partent de la boîte `newsletter@acdm.eu` (comprise dans l'hébergement Perso). Les réponses des abonnés
  arrivent à `ACDM_CONTACT_EMAIL` (en-tête Reply-To) : personne n'a besoin de relever la boîte d'envoi.
- Envoi depuis l'admin : *Newsletter → Envois → Préparer*. Envoyez d'abord un test, puis « Envoyer aux abonnés ».
  L'envoi se fait par lots (`NEWSLETTER_BATCH_SIZE`, 150 par défaut, sous la limite horaire SMTP d'OVH) : s'il reste des abonnés, le bouton devient « Reprendre l'envoi ».

## 5. Rôles

| Rôle | Peut faire |
|---|---|
| Administrateur | tout, y compris gérer les utilisateurs et supprimer des abonnés |
| Éditeur (Cécile, Valérie) | concerts, séries, lieux, pages, partenaires, préparer et envoyer la newsletter |

## 6. Tests

```bash
php artisan test
```

## 7. Git et plusieurs ordinateurs

Dépôt : `https://github.com/noche84/acdm2027` (à garder **privé**).
Ne sont jamais envoyés sur GitHub (voir `.gitignore`) : `.env`, `.env.production` (mots de passe), `vendor/`, `build/`,
les archives `.zip`, les exports `.sql` de l'ancien site (adresses des abonnés), la base SQLite locale, `public/uploads` et la galerie.

Sur un nouvel ordinateur :

```bash
git clone https://github.com/noche84/acdm2027.git acdm-laravel && cd acdm-laravel
./install-local.sh        # composer, .env, base locale, compte admin
```

Pour retrouver les concerts réels en local, copiez `storage/legacy/agenda.sql` (et `newsletter.sql`) depuis un autre poste avant de lancer le script.
Gardez `.env.production` en lieu sûr (gestionnaire de mots de passe) : il contient la clé de l'application en ligne.

## À relire avant la mise en ligne

- Pages *Mentions légales et CGU*, *Vie privée*, *Infos pratiques* (tarifs repris de l'ancien site), *Contact*.
- Adresses des lieux (menu *Lieux*) et liste des partenaires.
