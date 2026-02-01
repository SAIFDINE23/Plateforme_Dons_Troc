# 📋 GESTION DES ANNONCES - Documentation Complète

## 🎯 Fonctionnalités Implémentées

### 1. **API - Gestion des Annonces** (`src/Controller/Api/ManagementApiController.php`)

#### A. GET /api/my/annonces
**Pour l'utilisateur lambda**

- **Sécurité** : `ROLE_USER` (utilisateur connecté)
- **Description** : Récupère toutes les annonces de l'utilisateur connecté
- **Tri** : Par `createdAt` DESC (plus récentes en premier)

**Réponse JSON :**
```json
[
  {
    "id": "uuid-format",
    "title": "Titre de l'annonce",
    "status": "PENDING_REVIEW|PUBLISHED|REJECTED|COMPLETED|DRAFT",
    "date": "01/02/2025",
    "image": "/uploads/annonces/filename.jpg"
  }
]
```

---

#### B. GET /api/admin/pending
**Pour les Modérateurs & Admins**

- **Sécurité** : `ROLE_MODERATOR` ou `ROLE_ADMIN`
- **Logique Global vs Local** ⚠️ CRUCIAL :
  - **ROLE_ADMIN** (Global) : Récupère **TOUTES** les annonces avec `state = 'PENDING_REVIEW'`
  - **ROLE_MODERATOR** (Local) : Récupère les annonces `PENDING_REVIEW` **ET** `campus = user->moderatedCampus`
- **Tri** : Par `createdAt` ASC (anciennes en premier)

**Réponse JSON :**
```json
[
  {
    "id": "uuid-format",
    "title": "Titre",
    "description": "Les 150 premiers caractères...",
    "owner": "cas_uid de l'auteur",
    "campus": "CALAIS|DUNKERQUE|BOULOGNE|SAINT_OMER",
    "date": "01/02/2025 14:30",
    "image": "/uploads/annonces/filename.jpg"
  }
]
```

---

#### C. POST /api/admin/annonce/{id}/decide
**Action de Modération**

- **Sécurité** : `ROLE_MODERATOR` ou `ROLE_ADMIN`
- **Vérification de sécurité locale** : Si modérateur local, vérifie que l'annonce appartient à son campus
- **Body JSON attendu** :
  ```json
  { "action": "validate" }  // ou "reject"
  ```
- **Actions** :
  - `"validate"` : Change state → `PUBLISHED` ✅
  - `"reject"` : Change state → `REJECTED` ❌

**Réponse JSON (201 Created):**
```json
{
  "message": "Annonce validée avec succès",
  "annonceId": "uuid-format",
  "newState": "PUBLISHED"
}
```

---

## 🎨 Frontend - Composants React

### 1. **MyAnnonces.jsx** (`assets/react/controllers/MyAnnonces.jsx`)

**Utilisation** :
- Route : `/mes-annonces`
- Composant : `{{ react_component('MyAnnonces') }}`
- Sécurité : `ROLE_USER`

**Fonctionnalités** :
- ✅ Fetch sur `/api/my/annonces`
- ✅ Affichage en cartes (Bootstrap grid col-md-6, col-lg-4)
- ✅ Badges de statut avec couleurs distinctes :
  - 🟡 `PENDING_REVIEW` → Badge jaune ("⏳ En attente")
  - 🟢 `PUBLISHED` → Badge vert ("✅ En ligne")
  - 🔴 `REJECTED` → Badge rouge ("❌ Refusée")
  - ⚫ `COMPLETED` → Badge gris ("🏁 Terminée")
- ✅ Image preview avec fallback
- ✅ Lien vers détails (placeholder `/annonce/{id}`)
- ✅ Message "Aucune annonce" avec bouton créer
- ✅ Design responsive avec spinner de chargement

**States React** :
```javascript
const [annonces, setAnnonces] = useState([]);
const [loading, setLoading] = useState(true);
const [error, setError] = useState('');
```

---

### 2. **ModerationDashboard.jsx** (`assets/react/controllers/ModerationDashboard.jsx`)

**Utilisation** :
- Route : `/admin/dashboard`
- Composant : `{{ react_component('ModerationDashboard') }}`
- Sécurité : `ROLE_MODERATOR` (admins inclus)

**Fonctionnalités** :
- ✅ Fetch sur `/api/admin/pending`
- ✅ Tableau HTML avec Bootstrap `table.table.table-hover`
- ✅ Colonnes : Date | Campus | Titre | Auteur | Actions
- ✅ Actions par ligne :
  - 🟢 "✅ Valider" → Vert, appel POST avec `action: "validate"`
  - 🔴 "❌ Refuser" → Rouge, appel POST avec `action: "reject"`
- ✅ Suppression locale de la ligne après décision
- ✅ Spinners de chargement au clic
- ✅ Message "Aucune annonce à modérer" sympa
- ✅ Gestion des erreurs avec alerte dismissible

**States React** :
```javascript
const [annonces, setAnnonces] = useState([]);
const [loading, setLoading] = useState(true);
const [error, setError] = useState('');
const [processingId, setProcessingId] = useState(null);
```

---

## 🚀 Contrôleurs Twig

### UserController.php
```php
#[Route('/mes-annonces', name: 'app_user_annonces')]
#[IsGranted('ROLE_USER')]
public function myAnnonces(): Response {
    return $this->render('user/my_annonces.html.twig');
}
```

### AdminController.php
```php
#[Route('/admin/dashboard', name: 'app_admin_dashboard')]
#[IsGranted('ROLE_MODERATOR')]
public function dashboard(): Response {
    return $this->render('admin/dashboard.html.twig');
}
```

---

## 📍 Routes Registrées

| Route | Méthode | Sécurité | Composant |
|-------|---------|----------|-----------|
| `/mes-annonces` | GET | ROLE_USER | MyAnnonces |
| `/admin/dashboard` | GET | ROLE_MODERATOR | ModerationDashboard |
| `/api/my/annonces` | GET | ROLE_USER | API |
| `/api/admin/pending` | GET | ROLE_MODERATOR | API |
| `/api/admin/annonce/{id}/decide` | POST | ROLE_MODERATOR | API |

---

## 🔐 Sécurité

### Modération Local vs Global
```php
// ROLE_ADMIN : Global (toutes les annonces)
if ($this->isGranted('ROLE_ADMIN')) {
    // Accès à TOUTES les annonces PENDING_REVIEW
}

// ROLE_MODERATOR : Local (son campus)
if (!$this->isGranted('ROLE_ADMIN') && $user->getModeratedCampus()) {
    $qb->andWhere('a.campus = :campus')
        ->setParameter('campus', $user->getModeratedCampus());
}
```

### Vérification Campus pour les Modérateurs
```php
// Un modérateur ne peut valider/refuser que les annonces de son campus
if (!$this->isGranted('ROLE_ADMIN') && $user->getModeratedCampus()) {
    if ($annonce->getCampus() !== $user->getModeratedCampus()) {
        return $this->json(['error' => '...'], 403);
    }
}
```

---

## 📊 Schéma Annonces

Statuts possibles :
- `DRAFT` - Brouillon (créé mais non soumis)
- `PENDING_REVIEW` - En attente de modération (workflow critique)
- `PUBLISHED` - Publiée et visible
- `REJECTED` - Refusée par un modérateur
- `COMPLETED` - Transaction finalisée
- `ARCHIVED` - Archivée

---

## 🧪 Test Manual

### 1. Se connecter en tant que utilisateur
```bash
# Accédez à http://localhost:8000/login
# cas_uid: sleroy
# password: 00000000
```

### 2. Voir ses annonces
```bash
GET http://localhost:8000/mes-annonces
# Affiche "Ma première annonce" (PUBLISHED)
# et "Ma deuxième annonce" (DRAFT)
```

### 3. Se connecter en tant que modérateur
```bash
# cas_uid: jdupont (modérateur CALAIS)
# password: 00000000
```

### 4. Voir le dashboard
```bash
GET http://localhost:8000/admin/dashboard
# Affiche les 3 annonces "Annonce à modérer #1,2,3" (PENDING_REVIEW, CALAIS)
```

### 5. Valider une annonce
```bash
POST http://localhost:8000/api/admin/annonce/{id}/decide
Body: { "action": "validate" }
# State change: PENDING_REVIEW → PUBLISHED
```

---

## 🎨 UI/UX

### Navigation (Navbar Updated)
- ✅ Lien "Mes Annonces" → `/mes-annonces` (dropdown utilisateur)
- ✅ Lien "Espace Modération" → `/admin/dashboard` (visible pour MODERATOR/ADMIN)

### Badges de Statut
- 🟡 PENDING_REVIEW : `badge bg-warning text-dark`
- 🟢 PUBLISHED : `badge bg-success`
- 🔴 REJECTED : `badge bg-danger`
- ⚫ COMPLETED/DRAFT : `badge bg-secondary`

---

## 📝 Fichiers Créés/Modifiés

✅ `src/Controller/Api/ManagementApiController.php` - Contrôleur API complet
✅ `src/Controller/UserController.php` - Route `/mes-annonces`
✅ `src/Controller/AdminController.php` - Route `/admin/dashboard`
✅ `assets/react/controllers/MyAnnonces.jsx` - Composant utilisateur
✅ `assets/react/controllers/ModerationDashboard.jsx` - Composant modération
✅ `templates/user/my_annonces.html.twig` - Template utilisateur
✅ `templates/admin/dashboard.html.twig` - Template admin
✅ `templates/partials/_navbar.html.twig` - Liens mis à jour

---

## 🚀 Build & Cache

```bash
npm run build
php bin/console cache:clear
```

✅ **Statut** : Compilation réussie (11 fichiers générés)

