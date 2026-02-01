# 🚀 Quick Start Guide

## Installation Ultra-Rapide

```bash
# 1. Installer les dépendances
composer install
npm install

# 2. Configurer la base de données (PostgreSQL)
# Modifier .env si nécessaire, puis:
sudo -u postgres psql -c "CREATE DATABASE plateforme_dons_troc;"
sudo -u postgres psql -c "CREATE USER plateforme_user WITH PASSWORD 'plateforme_password';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE plateforme_dons_troc TO plateforme_user;"

# 3. Compiler les assets
npm run dev

# 4. Démarrer le serveur
./start.sh
# OU
php -S localhost:8000 -t public
```

## Accès

- **Application**: http://localhost:8000
- **Page de test React**: http://localhost:8000/default

## Développement

```bash
# Terminal 1: Serveur PHP
php -S localhost:8000 -t public

# Terminal 2: Watch mode pour les assets
npm run watch
```

## Structure du Projet

```
plateforme_dons_troc/
├── assets/
│   ├── react/
│   │   └── controllers/      # Composants React
│   │       └── Hello.jsx
│   ├── controllers/          # Contrôleurs Stimulus
│   ├── styles/              # CSS
│   └── app.js              # Point d'entrée JS
├── config/                 # Configuration Symfony
├── public/
│   └── build/             # Assets compilés
├── src/
│   ├── Controller/        # Contrôleurs Symfony
│   └── Entity/           # Entités Doctrine
├── templates/            # Templates Twig
│   ├── base.html.twig
│   └── default/
│       └── index.html.twig
├── .env                  # Configuration environnement
├── composer.json         # Dépendances PHP
├── package.json         # Dépendances JS
├── webpack.config.js    # Configuration Webpack
└── README.md           # Documentation complète
```

## Commandes Essentielles

```bash
# Backend
php bin/console make:controller MonController
php bin/console make:entity MaTable
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Frontend
npm run dev        # Compile une fois
npm run watch      # Compile et surveille
npm run build      # Compile pour production

# Cache
php bin/console cache:clear
```

## Créer un Composant React

1. **Créer** `assets/react/controllers/MonComposant.jsx`:
   ```jsx
   import React from 'react';
   
   export default function (props) {
       return <div>Hello {props.name}!</div>;
   }
   ```

2. **Utiliser** dans un template Twig:
   ```twig
   <div {{ react_component('MonComposant', { name: 'World' }) }}>
       Loading...
   </div>
   ```

3. **Recompiler**:
   ```bash
   npm run dev
   ```

## Aide

- 📖 Documentation complète: [README.md](README.md)
- 🔧 Guide d'installation détaillé: [INSTALLATION.md](INSTALLATION.md)
- 🐛 Problèmes courants: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

## Support

- Symfony: https://symfony.com/doc
- React: https://react.dev
- Symfony UX: https://ux.symfony.com
