# Déploiement AGREE SIKUL — Docker / Dokploy

Architecture identique à IIPS_Sante : **un seul container** qui sert PHP-FPM, Nginx et le queue worker via Supervisor. Le front Vue 3 est buildé par Vite **dans l'image** (multi-stage), Laravel + Inertia sert tout depuis `/var/www/html/public`.

## Fichiers

| Fichier | Rôle |
|---|---|
| `Dockerfile` | Image multi-stage (composer → vite → php-fpm-alpine) |
| `.dockerignore` | Exclut node_modules, vendor, .env locaux, etc. |
| `docker-compose.yml` | Service `app` exposé sur `:8091` (host) |
| `dokploy.yml` | Config Dokploy (env, secrets, volumes, hooks, healthcheck) |
| `.env.production` | Template `.env` injecté dans l'image (à dupliquer en `.env` local pour test) |
| `deploy.sh` | Wrapper bash : `deploy / build / start / stop / logs / shell / fresh / …` |

## Test local rapide

```bash
# 1. Préparer .env
cp .env.production .env
# édite .env (DB_*, secrets…)

# 2. Build + run + setup en une commande
chmod +x deploy.sh
./deploy.sh deploy

# 3. Ouvre http://localhost:8091
```

Le script :
- vérifie Docker / docker compose
- copie `.env.production → .env` si absent
- génère `JWT_SECRET` si placeholder détecté
- build image, start container, attend la DB, run migrations, optimize Laravel
- vérifie `/health`

## Déploiement Dokploy

### 1. Création de l'app dans Dokploy

- **Source** : Git repo (branche `main` ou `Dokploy`)
- **Build type** : Dockerfile (auto-détecté)
- **Domain** : configure ton domaine, active SSL Let's Encrypt

### 2. Variables d'environnement (Dokploy UI)

Tout ce qui est dans `dokploy.yml` → section `env` est appliqué automatiquement. Les valeurs sensibles (`secrets` dans `dokploy.yml`) doivent être saisies manuellement dans l'onglet **Environment** :

```
APP_KEY=base64:...                    # garde celui de .env.production ou regénère
DB_USERNAME=...
DB_PASSWORD=...
JWT_SECRET=...
PUSHER_APP_KEY=...
FIREBASE_API_KEY=...
SMS_API_AUTHORIZATION=...
PISPI_CLIENT_ID=...
…
```

### 3. Premier déploiement

`RUN_MIGRATIONS=true` est dans `dokploy.yml` → migrations exécutées au démarrage.

Pour seeder **uniquement la 1ère fois** :
1. Dans Dokploy → Environment, ajoute `RUN_SEED=true`
2. Redeploy
3. Une fois le seed passé, retire `RUN_SEED=true` et redeploy (sinon il rejouera à chaque restart, ce qui peut casser des données existantes selon les seeders)

### 4. Volumes persistants

`storage-data` (5 Gi) est monté sur `/var/www/html/storage/app/public` → tous les uploads (logos, fichiers, photos) survivent aux redéploiements.

### 5. Domaine + SSL

`dokploy.yml` :
```yaml
domains:
  - domain: "${DOKPLOY_DOMAIN}"
    ssl: true
    forceHttps: true
```

L'`APP_URL` est calculé automatiquement depuis `${DOKPLOY_DOMAIN}` (template Dokploy).

## Toggle local / ngrok / prod (`APP_TUNNEL`)

Les modifications faites dans `config/app.php` + `AppServiceProvider` permettent de basculer l'URL/HTTPS/cookies en changeant **une seule variable** :

| Mode | Quand l'utiliser | URL effective |
|---|---|---|
| `local` | Dev sur ta machine | `APP_URL_LOCAL` (ex. `http://localhost:8091`) |
| `ngrok` | Démo via tunnel ngrok | `APP_URL_NGROK` + `URL::forceScheme('https')` |
| `prod` | Production Dokploy | `APP_URL` |

`.env.production` met `APP_TUNNEL=prod` → comportement standard prod. Pour tester en local, repasse à `APP_TUNNEL=local` + `php artisan config:clear`.

## Commandes utiles

```bash
./deploy.sh logs          # tail logs (Ctrl+C pour quitter)
./deploy.sh shell         # shell dans le container app
./deploy.sh migrate       # php artisan migrate --force
./deploy.sh fresh         # migrate:fresh --seed (DESTRUCTIF)
./deploy.sh clear         # php artisan optimize:clear
./deploy.sh restart       # restart container sans rebuild
./deploy.sh stop          # arrêt complet
```

## Healthcheck

`GET /health` renvoie `200 OK` (configuré dans Nginx). Utilisé par Dokploy + Docker Compose pour les probes.

## Différences vs IIPS_Sante

| | IIPS_Sante | AgreeSikul |
|---|---|---|
| Port host | 8090 | **8091** |
| Image tag | `iips_sante:latest` | `agreesikul:latest` |
| App name (Dokploy) | iips-sante | **agree-sikul** |
| Volumes | aucun | **storage-data 5 Gi** (uploads) |
| Hooks | basiques | + `RUN_SEED` toggle |
| Toggle multi-env | ❌ | ✅ `APP_TUNNEL` |

## Pièges fréquents

| Symptôme | Cause | Fix |
|---|---|---|
| `419 PAGE EXPIRED` | `SESSION_SECURE_COOKIE=true` mais URL en HTTP | mettre `false` ou activer SSL Dokploy |
| Assets en 404 | `npm run build` non fait dans l'image | `./deploy.sh build` (le multi-stage le fait normalement) |
| `Class "Modules\X\..." not found` | `composer install` ne charge pas Modules | déjà géré (autoload psr-4 dans `composer.json`) |
| Migrations rejouent les seeders | `RUN_SEED=true` resté dans env | retirer après le 1er déploiement |
| URL générées en `http://` derrière HTTPS | `TrustProxies` pas configuré | déjà fait : `TRUSTED_PROXIES=*` dans `.env.production` |
| `Database is ready!` boucle | `DB_HOST` inaccessible depuis le container | vérifier que la DB accepte les connexions externes (firewall, bind-address MySQL) |
