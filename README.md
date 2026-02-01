# Plateforme de Dons et Troc

Projet Symfony avec React et PostgreSQL.

## Stack Technique

- **Backend**: Symfony 7.4
- **Frontend**: React 18 avec Symfony UX
- **Base de données**: PostgreSQL 16
- **Build**: Webpack Encore

## Installation

### Prérequis

- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL 16+

### Configuration de la base de données

1. Créer une base de données PostgreSQL:

```bash
sudo -u postgres psql
CREATE DATABASE plateforme_dons_troc;
CREATE USER plateforme_user WITH PASSWORD 'plateforme_password';
GRANT ALL PRIVILEGES ON DATABASE plateforme_dons_troc TO plateforme_user;
\q
```

2. Mettre à jour le fichier `.env` si nécessaire (déjà configuré):

```env
DATABASE_URL="postgresql://plateforme_user:plateforme_password@127.0.0.1:5432/plateforme_dons_troc?serverVersion=16&charset=utf8"
```

3. Créer le schéma de la base de données:

```bash
php bin/console doctrine:migrations:migrate
```

### Installation des dépendances

```bash
# Dépendances PHP
composer install

# Dépendances JavaScript
npm install
```

### Compilation des assets

```bash
# Développement (une fois)
npm run dev

# Développement (avec watch)
npm run watch

# Production
npm run build
```

## Lancement du serveur

```bash
# Avec Symfony CLI
symfony server:start

# Ou avec PHP built-in server
php -S localhost:8000 -t public
```

Accéder à l'application: http://localhost:8000

## Développement

### Structure des composants React

Les composants React sont dans `assets/react/controllers/`. Exemple:

```jsx
// assets/react/controllers/Hello.jsx
import React from 'react';

export default function (props) {
    return <div>Hello {props.fullName}</div>;
}
```

### Utilisation dans Twig

```twig
<div {{ react_component('Hello', { fullName: 'Saif' }) }}>
    Chargement...
</div>
```

### Commandes utiles

```bash
# Créer un nouveau contrôleur
php bin/console make:controller

# Créer une nouvelle entité
php bin/console make:entity

# Créer une migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear
```

## Tests

```bash
php bin/phpunit
```

## Licence

UNLICENSED - Projet privé
