# ULC'OCCAZ — Plateforme de Dons et Troc ULCO

Plateforme d'échanges entre étudiants et personnels de l'Université du Littoral Côte d'Opale.

## Stack technique

| Composant | Version |
|---|---|
| **Backend** | Symfony 7.4 (PHP 8.3+) |
| **Frontend** | React 18 (via Symfony UX) |
| **Base de données** | PostgreSQL 16 |
| **Temps réel** | Mercure (notifications, messages) |
| **Build assets** | Webpack Encore |
| **Authentification** | Form login (prévu : CAS ULCO) |

## Prérequis

- PHP 8.3+ avec extensions : `pdo_pgsql`, `intl`, `mbstring`, `xml`
- Composer 2+
- Node.js 20+ et npm
- PostgreSQL 16+
- Mercure Hub (fourni via Docker Compose)

## Installation

### 1. Cloner le projet

```bash
git clone <url-du-repo>
cd plateforme_dons_troc
```

### 2. Configurer l'environnement

Copier le fichier d'exemple et adapter les valeurs :

```bash
cp .env.example .env.local
```

Variables à configurer dans `.env.local` :

| Variable | Description | Exemple |
|---|---|---|
| `APP_ENV` | Environnement (`dev` ou `prod`) | `prod` |
| `APP_SECRET` | Clé secrète Symfony (32 caractères aléatoires) | `a1b2c3d4...` |
| `DATABASE_URL` | Connexion PostgreSQL | `postgresql://user:pass@host:5432/dbname` |
| `MAILER_DSN` | Serveur SMTP pour l'envoi d'emails | `smtp://user:pass@smtp.host:587` |
| `MERCURE_URL` | URL interne du hub Mercure | `http://localhost:3000/.well-known/mercure` |
| `MERCURE_PUBLIC_URL` | URL publique du hub Mercure | `https://domaine/.well-known/mercure` |
| `MERCURE_JWT_SECRET` | Clé JWT pour Mercure (32+ caractères) | `ChangeMeTo...` |

### 3. Installer les dépendances

```bash
composer install --no-dev --optimize-autoloader
npm install
```

### 4. Créer la base de données

```bash
# Créer la base PostgreSQL
sudo -u postgres psql -c "CREATE DATABASE plateforme_dons_troc;"
sudo -u postgres psql -c "CREATE USER plateforme_user WITH PASSWORD 'MOT_DE_PASSE';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE plateforme_dons_troc TO plateforme_user;"

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Compiler les assets

```bash
# Production
npm run build

# Développement (watch)
npm run watch
```

### 6. Charger les données initiales (optionnel)

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

### 7. Lancer les services

```bash
# Démarrer PostgreSQL et Mercure via Docker
docker compose up -d

# Lancer le serveur Symfony
symfony server:start
# ou
php -S localhost:8000 -t public
```

## Services Docker Compose

Le fichier `compose.yaml` fournit :

| Service | Port | Description |
|---|---|---|
| **database** | 5432 | PostgreSQL 16 |
| **mercure** | 3000 | Hub Mercure (notifications temps réel) |

En développement, `compose.override.yaml` ajoute :

| Service | Port | Description |
|---|---|---|
| **mailer** | 1025 / 8025 | Mailpit (capture d'emails en local) |

## Déploiement en production

```bash
# 1. Configurer .env.local avec APP_ENV=prod
# 2. Installer les dépendances
composer install --no-dev --optimize-autoloader

# 3. Compiler les assets
npm run build

# 4. Vider le cache
php bin/console cache:clear --env=prod

# 5. Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Configurer le serveur web (Apache/Nginx) vers public/
```

### Configuration Apache (exemple)

```apache
<VirtualHost *:443>
    ServerName votre-domaine.univ-littoral.fr
    DocumentRoot /chemin/vers/plateforme_dons_troc/public

    <Directory /chemin/vers/plateforme_dons_troc/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>
</VirtualHost>
```

## Architecture du projet

```
src/
├── Controller/          # Contrôleurs Symfony (routes)
│   └── Api/             # Contrôleurs API (JSON)
├── Entity/              # Entités Doctrine (modèles BD)
├── Repository/          # Requêtes base de données
├── Security/            # Authentification et autorisations
├── Enum/                # Énumérations (Campus, Types...)
├── EventSubscriber/     # Listeners Symfony
├── Command/             # Commandes console
└── DataFixtures/        # Données de test

assets/react/controllers/  # Composants React
templates/                 # Templates Twig
migrations/                # Migrations Doctrine
config/                    # Configuration Symfony
```

## Commandes utiles

```bash
php bin/console cache:clear          # Vider le cache
php bin/console debug:router         # Lister les routes
php bin/console doctrine:migrations:migrate  # Appliquer les migrations
php bin/console doctrine:fixtures:load       # Charger les fixtures
```

## Licence

Projet universitaire — EILCO / Université du Littoral Côte d'Opale
