# 📜 Charte Progressiste - Système d'acceptation étape par étape

**Date:** 23 février 2026  
**Statut:** ✅ Complété et compilé  
**Design:** Pro (Blanc, Noir, Dark Blue #001a33)

---

## 📋 Vue d'ensemble

Implémentation d'un système d'acceptation de charte **étape par étape** (stepper) qui oblige l'utilisateur à accepter 4 sections de la charte avant de finaliser son inscription.

### Design et Couleurs

- **Palette:** Blanc, Noir, Dark Blue (#001a33)
- **Style:** Professionnel, minimaliste, sans sur-décoration
- **Typography:** Bootstrap 5 native
- **Composants:** Cartes, badges, barres de progression

---

## 🏗️ Architecture

### 1. Frontend React

**Fichier:** `assets/react/controllers/CharteStepper.jsx`

#### 4 Sections de Charte

```javascript
const CHARTE_SECTIONS = [
    { id: 1, title: "Esprit de la plateforme (Don et Troc)", content: "..." },
    { id: 2, title: "Objets interdits et limites", content: "..." },
    { id: 3, title: "Respect, courtoisie et rendez-vous", content: "..." },
    { id: 4, title: "Responsabilité de l'ULCO", content: "..." }
];
```

#### États (States)

```javascript
const [currentStep, setCurrentStep] = useState(0);           // Index section actuelle
const [loading, setLoading] = useState(false);               // Chargement API
const [acceptedSections, setAcceptedSections] = useState(new Set()); // Sections acceptées
```

#### Flux Utilisateur

```
Étape 1 (index 0)
   ↓ [J'ai lu et j'accepte cette partie]
Étape 2 (index 1)
   ↓ [J'ai lu et j'accepte cette partie]
Étape 3 (index 2)
   ↓ [J'ai lu et j'accepte cette partie]
Étape 4 (index 3)
   ↓ [Accepter la charte et finaliser mon inscription]
   ↓ POST /api/user/charte/accept
   ↓ Redirection /
```

#### UI Components

1. **En-tête**
   - Logo/icône + "Charte ULC'OCCAZ"
   - Sous-titre: "Acceptez notre charte pour accéder à la plateforme"

2. **Corps principal**
   - Titre section (ex: "Esprit de la plateforme")
   - Badge: "Étape 1/4"
   - Barre de progression (% fill = (step+1)/4)
   - Texte section (min-height: 250px, fond gris #f8f9fa)

3. **Suivi des étapes**
   - Badges numérotés (1, 2, 3, 4)
   - Dark blue si acceptée, gris si non
   - Affiche titre court de la section

4. **Boutons**
   - Étapes 1-3: "J'ai lu et j'accepte cette partie" → passe à l'étape suivante
   - Étape 4: "Accepter la charte et finaliser mon inscription" → POST API
   - Lien "Revenir à l'accueil"

### 2. Backend Symfony 7

**Fichier:** `src/Controller/Api/CharteController.php`

#### Route API

```php
POST /api/user/charte/accept
```

#### Payload Request

```json
{
  "sections": [
    "Esprit de la plateforme (Don et Troc)",
    "Objets interdits et limites",
    "Respect, courtoisie et rendez-vous",
    "Responsabilité de l'ULCO"
  ]
}
```

#### Logique

1. Vérifier authentification (ROLE_USER)
2. Pour chaque section :
   - Vérifier si déjà acceptée par l'utilisateur
   - Si non, créer `CharteAgreement` entry
3. Flush la BD
4. Retourner JSON 200 OK

#### Response

```json
{
  "message": "Charte acceptée avec succès",
  "sections_accepted": 4,
  "timestamp": "2026-02-23 14:30:45"
}
```

### 3. Entité BD

**Existante:** `src/Entity/CharteAgreement`

```php
#[ORM\Entity]
class CharteAgreement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sectionName = null;      // "Esprit de la plateforme..."

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $agreedAt = null;  // Timestamp

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'charteAgreements')]
    private ?User $user = null;
}
```

**Relation User:**
```php
#[ORM\OneToMany(targetEntity: CharteAgreement::class, mappedBy: 'user')]
private Collection $charteAgreements;
```

### 4. Template Twig

**Fichier:** `templates/charte/stepper.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Charte ULC'OCCAZ - Première inscription{% endblock %}

{% block body %}
    <div {{ react_component('CharteStepper') }}></div>
{% endblock %}
```

### 5. Contrôleur Web (Optionnel)

**Fichier:** `src/Controller/CharteController.php`

Nouvelle route:
```php
#[Route('/charte/stepper', name: 'app_charte_stepper')]
public function stepper(): Response { ... }
```

Accès: `http://127.0.0.1:8000/charte/stepper`

---

## 🎨 Design Détaillé

### Couleurs

```
Primary:     #001a33 (Dark Blue - couleur logo)
Background:  #f8f9fa (Très clair)
Border:      #e0e0e0 (Gris léger)
Text:        #333 (Noir foncé)
Muted:       #6c757d (Bootstrap default)
```

### Barre de progression

```
Background:  #e9ecef
Fill:        #001a33
Height:      8px
Animation:   width 0.3s ease
```

### Badges

```
Accepted:    #001a33 (dark blue)
Pending:     #d3d3d3 (light gray)
Color:       white text
```

### Boutons

```
Primary:     #001a33 on white
Hover:       Légèrement plus foncé (natural)
Secondary:   Outline dark blue
Font-weight: 600
Border-radius: 24px (rounded-3 Bootstrap)
```

---

## 🔌 Intégration avec flux First Login

**Optionnel:** Rediriger vers `/charte/stepper` au premier login:

```php
// Dans SecurityController ou après login
if (!hasAcceptedCharte($user)) {
    return $this->redirectToRoute('app_charte_stepper');
}
```

---

## 🧪 Test Workflow

### 1. Accès au composant

```bash
http://127.0.0.1:8000/charte/stepper
```

### 2. Acceptation étape par étape

```
Vérifier:
✓ Affichage section 1 (texte, titre, badge 1/4)
✓ Clic bouton → section 2
✓ Barre progression remplit
✓ Badges 1 → dark blue, 2 → gray
✓ Clic sur badge 1 → revient à section 1 (si implémenté)
✓ Clic sur miniature section → jump à cette étape
✓ Section 4 → bouton "Accepter et finaliser"
```

### 3. API call

```bash
curl -X POST http://127.0.0.1:8000/api/user/charte/accept \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=..." \
  -d '{
    "sections": [
      "Esprit de la plateforme (Don et Troc)",
      "Objets interdits et limites",
      "Respect, courtoisie et rendez-vous",
      "Responsabilité de l'ULCO"
    ]
  }'
```

**Response 200:**
```json
{
  "message": "Charte acceptée avec succès",
  "sections_accepted": 4,
  "timestamp": "2026-02-23 14:35:12"
}
```

### 4. Vérification BD

```sql
SELECT c.section_name, c.agreed_at, u.email
FROM charte_agreement c
JOIN user u ON c.user_id = u.id
ORDER BY c.agreed_at DESC;
```

---

## 📦 Compilation & Déploiement

### Frontend

```bash
npm run build
# ✅ 904 KiB entrypoint
# ✅ 22 expected deprecation warnings (Bootstrap/Sass)
```

### Backend

```bash
# Aucune migration nécessaire (entité existe)
symfony serve -d --port=8000 --no-tls
```

---

## 📝 Fichiers Créés/Modifiés

| Fichier | Type | Status |
|---------|------|--------|
| `assets/react/controllers/CharteStepper.jsx` | React | ✅ Créé |
| `src/Controller/Api/CharteController.php` | Symfony API | ✅ Créé |
| `src/Controller/CharteController.php` | Symfony Web | ✅ Modifié (route stepper) |
| `templates/charte/stepper.html.twig` | Twig | ✅ Créé |

---

## 🚀 Prochaines étapes optionnelles

1. **Auto-redirection au premier login**
   - Ajouter logic dans LoginController
   - Vérifier si `charteAgreements.count() === 0`

2. **Swipe/Navigation clavier**
   - Touches flèches pour navigation
   - Gestes swipe sur mobile

3. **Analytics/Logging**
   - Tracker quelles sections sont refusées
   - Identifier les utilisateurs qui abandonnent

4. **Customization par campus**
   - Variantes de charte par ULCO/EILCO/ESTHUA

5. **Versioning de charte**
   - Tracker version (v1, v2)
   - Demander re-acceptation si mise à jour

---

## 🎉 Résumé

✅ **4 sections** de charte bien structurées  
✅ **Stepper UI** professionnel et minimaliste  
✅ **API backend** robuste avec validation  
✅ **Design cohérent** (blanc/noir/dark blue)  
✅ **UX intuitive** - acceptation progressiste obligatoire  
✅ **BD clean** - enregistrements horodatés par section  
✅ **Compilation** webpack success

**Prêt pour le testing et le déploiement ! 🚀**
