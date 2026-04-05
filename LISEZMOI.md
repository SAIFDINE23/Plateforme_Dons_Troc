# ULC'OCCAZ — Plateforme de dons et troc entre étudiants ULCO

## 📋 Présentation

ULC'OCCAZ est une plateforme web permettant aux étudiants, enseignants et personnels de l'ULCO d'échanger des objets (dons et troc) entre les campus de Calais, Dunkerque, Boulogne-sur-Mer et Saint-Omer.

**Stack technique :**

| Composant     | Technologie                |
|---------------|----------------------------|
| Backend       | Symfony 7.4 (PHP 8.3+)    |
| Frontend      | React 18 (via Webpack Encore) |
| Base de données | PostgreSQL 16            |
| Temps réel    | Mercure Hub (SSE)          |
| Emails        | Symfony Mailer             |

---

## ⚙️ Prérequis

- **PHP 8.3+** avec extensions : `pdo_pgsql`, `intl`, `mbstring`, `gd`, `zip`, `uuid`
- **Composer** (gestionnaire de dépendances PHP)
- **Node.js 18+** et **npm** (pour compiler le frontend React)
- **PostgreSQL 16** (serveur de base de données)

---

## 🚀 Installation pas à pas

### 1. Installer les dépendances PHP

```bash
composer install
```

### 2. Installer les dépendances JavaScript

```bash
npm install
```

### 3. Configurer l'environnement

Copier le fichier `.env.example` en `.env` et adapter les valeurs :

```bash
cp .env.example .env
```

**Variables essentielles à modifier dans `.env` :**

```dotenv
# Base de données PostgreSQL
DATABASE_URL="postgresql://UTILISATEUR:MOT_DE_PASSE@127.0.0.1:5432/plateforme_dons_troc?serverVersion=16&charset=utf8"

# Clé secrète de l'application (générer une chaîne aléatoire)
APP_SECRET=une-cle-secrete-aleatoire-de-32-caracteres

# Emails (mettre null://null si pas de serveur SMTP)
MAILER_DSN=null://null
```

### 4. Créer la base de données

**Option A — Avec le fichier SQL fourni (recommandé) :**

```bash
# Créer la base de données
createdb plateforme_dons_troc

# Importer l'export complet (schéma + données de test)
psql -d plateforme_dons_troc < database_export.sql
```

**Option B — Avec les migrations Symfony (base vide + fixtures) :**

```bash
# Créer la base
php bin/console doctrine:database:create

# Appliquer les 13 migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Charger les données de test (utilisateurs + annonces + catégories)
php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Compiler le frontend React

```bash
npm run build
```

### 6. Lancer le serveur

```bash
php -S 127.0.0.1:8000 -t public
```

Ouvrir dans le navigateur : **http://127.0.0.1:8000**

---

## 👤 Comptes de test

Tous les comptes utilisent le mot de passe : **`00000000`** (8 zéros)

| Identifiant (CAS UID) | Rôle          | Email                                   |
|------------------------|---------------|-----------------------------------------|
| `aresponsable`         | RESPONSABLE   | alice.responsable@univ-littoral.fr      |
| `bresponsable`         | RESPONSABLE   | bob.responsable@univ-littoral.fr        |
| `jmoderator`           | MODÉRATEUR    | jean.moderator@univ-littoral.fr         |
| `mmoderator`           | MODÉRATEUR    | marie.moderator@univ-littoral.fr        |
| `sleroy`               | UTILISATEUR   | sophie.leroy@etu.univ-littoral.fr       |
| `epetit`               | UTILISATEUR   | emma.petit@etu.univ-littoral.fr         |
| `hmoreau`              | UTILISATEUR   | hugo.moreau@etu.univ-littoral.fr        |
| `ldubois`              | UTILISATEUR   | lucas.dubois@etu.univ-littoral.fr       |
| `oismaili`             | UTILISATEUR   | oussama.ismaili@etu.eilco.univ-littoral.fr |
| `skhantache`           | MODÉRATEUR    | saif-eddine.el-khantache@etu.eilco.univ-littoral.fr |

### Parcours de test recommandé

1. **Se connecter** avec `sleroy` / `00000000` (utilisateur normal)
2. **Définir un alias** → la plateforme le demande à la première connexion
3. **Accepter la charte** → stepper interactif obligatoire
4. **Explorer la page d'accueil** → filtres par campus, catégorie, recherche
5. **Créer une annonce** → formulaire avec upload d'images (max 6, 1 Mo chacune)
6. **Se connecter avec `jmoderator`** → modérer les annonces en attente
7. **Se connecter avec `aresponsable`** → gérer les utilisateurs, changer les rôles

---

## 📌 Note sur l'authentification CAS

L'application est conçue pour fonctionner avec le **CAS de l'ULCO** (`cas.univ-littoral.fr`).

En environnement de développement/test, l'authentification utilise un **formulaire de login classique** (identifiant + mot de passe). L'intégration CAS nécessite que la DSI enregistre l'URL de l'application dans la liste des services CAS autorisés.

Le champ d'identifiant (`casUid`) est prévu pour correspondre à l'identifiant CAS ULCO de chaque utilisateur.

---

## 📌 Note sur Mercure (temps réel)

La messagerie en temps réel utilise **Mercure Hub**. Si Mercure n'est pas disponible :
- La messagerie fonctionne quand même (polling toutes les 15 secondes)
- Les notifications se mettent à jour au rechargement de la page

Pour activer Mercure en local, lancer le hub via Docker :

```bash
docker compose up -d mercure
```

---

## 🏗️ Architecture du projet

```
src/
├── Controller/          # Contrôleurs Symfony (pages HTML)
│   └── Api/             # Contrôleurs API JSON (pour React)
├── Entity/              # Entités Doctrine (= tables en BDD)
├── Repository/          # Requêtes vers la BDD
├── Security/            # Authentification, Voter (permissions)
├── Enum/                # Campus, AnnonceState, AnnonceType
├── DataFixtures/        # Données de test
├── EventSubscriber/     # Listeners Symfony
│
assets/
├── react/
│   ├── controllers/     # Composants React principaux (pages)
│   └── components/      # Composants React réutilisables
├── styles/              # SCSS (design system)
│
templates/               # Templates Twig (structure HTML)
migrations/              # Historique des modifications de BDD
```

---

## 📧 Contact

- **EL KHANTACHE Saif-Eddine** — saif-eddine.el-khantache@etu.eilco.univ-littoral.fr
- **ISMAILI Oussama** — oussama.ismaili@etu.eilco.univ-littoral.fr
