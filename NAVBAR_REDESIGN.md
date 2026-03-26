# Redesign de la Navbar - Documentation

## 🎯 Objectif
Restructurer et organiser la navbar de manière professionnelle pour tous les rôles utilisateurs (Utilisateur standard, Modérateur, Responsable).

## 📐 Nouvelle Architecture

### Structure Hiérarchique

```
┌─────────────────────────────────────────────────────────────┐
│                    NAVIGATION PRINCIPALE                     │
├─────────────────────────────────────────────────────────────┤
│ Logo │ Accueil │ Déposer une annonce │ ─── │ Mes annonces │ │
│      │         │                      │ sep │ Mes messages │ │
│      │         │                      │     │ Favoris      │ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    BARRE DE DROITE                          │
├─────────────────────────────────────────────────────────────┤
│ Admin/Modération (si applicable) │ 🔔 Notifications │ 👤 Menu │
└─────────────────────────────────────────────────────────────┘
```

### Sections Détaillées

#### 1. **Navigation Principale (gauche)**
- **Accueil** - Lien principal, toujours visible
  - Non authentifiés → Landing page
  - Authentifiés → Dashboard

- **Déposer une annonce** - CTA principal (gradient bleu)
  - Visible uniquement si authentifié
  - Bouton avec hover effect animé
  - Texte complet et explicite

- **Séparateur visuel** (─) - Ligne fine à desktop, invisible mobile

- **Navigation utilisateur**
  - Mes annonces
  - Mes messages (avec badge)
  - Favoris

#### 2. **Barre de Droite**
- **Admin/Modération** - Pour rôles ROLE_MODERATOR/ROLE_RESPONSABLE
  - Visible seulement si applicable
  - Couleur rouge (#d9534f) pour distinction
  - Masquée en mobile, visible dans dropdown profil

- **Notifications** - Composant React NotificationBell
  - Cloche avec badge

- **Menu Profil** - Dropdown utilisateur
  - Affiche CAS UID
  - Email de l'utilisateur
  - Liens rapides
  - Lien Admin/Modération
  - Bouton Déconnexion

## 🎨 Hiérarchie Visuelle

### Poids Typographique
```
Logo              → Font-weight: 700 (Montserrat Bold)
Liens principaux  → Font-weight: 500
CTA "Déposer"     → Font-weight: 600 + Gradient
Admin/Mod         → Font-weight: 600 (couleur rouge)
```

### Couleurs
```
Navigation standard    → Bleu primaire (#004E86)
Hover état           → Cyan (#009FE3)
CTA "Déposer"        → Gradient bleu → cyan
Admin/Modération     → Rouge (#d9534f)
Texte menu           → Gris (#6c757d)
Icônes hover         → Cyan (#009FE3)
```

### Espacements
```
Padding horizontal (navbar)    → 0.75-1rem par section
Gap entre sections             → 0.75-1rem
Gap responsive mobile          → 0.5rem
Padding items dropdown         → 0.65rem 1.25rem
```

## 🔄 Responsive Design

### Desktop (> 1024px)
- Navigation complète sur une ligne
- Séparateur visuel entre sections
- Menu déroulant en bas à droite
- Icons + texte pour tous les éléments

### Tablet (768px - 1024px)
- Navigation peut se replier
- Menu hamburger actif
- Dropdown complète avec tous les éléments

### Mobile (< 768px)
- Menu hamburger obligatoire
- Navigation empilée verticalement
- Admin/Mod dans dropdown profil uniquement
- Texte limité, priorité aux icônes

## 🔐 Gestion des Rôles

### Utilisateur Standard (ROLE_USER)
```
Visible: Accueil | Déposer | Mes annonces | Mes messages | Favoris
         | Notifications | Profil
```

### Modérateur (ROLE_MODERATOR)
```
Visible: Accueil | Déposer | Mes annonces | Mes messages | Favoris
         | [MODÉRATION] | Notifications | Profil
         + Lien Modération dans dropdown profil
```

### Responsable (ROLE_RESPONSABLE)
```
Visible: Accueil | Déposer | Mes annonces | Mes messages | Favoris
         | [ADMINISTRATION] | Notifications | Profil
         + Lien Administration dans dropdown profil
```

## ✨ Améliorations UX

### States et Interactions
- **Hover**: Couleur cyan + background léger
- **Active**: Font-weight accru + background
- **Focus**: Box-shadow pour accessibilité
- **Animations**: Transitions 0.2s ease

### Feedback Utilisateur
- CTA "Déposer" : `translateY(-2px)` + ombre au hover
- Menu utilisateur : Scale animation sur icône
- Dropdown : Padding-left animation sur hover items

### Accessibilité
- `aria-expanded` sur dropdowns
- `role="button"` sur éléments cliquables
- Contraste de couleur respecté (WCAG AA)
- Focus visible pour navigation clavier

## 📱 Points de Rupture Responsive

```scss
@media (max-width: 991px)  // Hamburger menu activé
@media (max-width: 576px)  // Mobile optimisé
```

## 🔗 Fichiers Modifiés

1. **templates/partials/_navbar.html.twig**
   - Restructuration complète du template
   - Séparation des sections logiques
   - Ajout de classes CSS pour flexibilité

2. **assets/styles/app.scss** (lignes 130-425)
   - `.navbar-eilco` - Container principal
   - `.navbar-nav` - Navigation
   - `.navbar-notifications` - Notifications
   - `.navbar-user-menu` - Menu profil
   - `.navbar-dropdown` - Styles dropdown
   - Responsive media queries

## 🚀 Prochaines Améliorations Potentielles

- [ ] Sticky navbar au scroll
- [ ] Dark mode support
- [ ] Sous-menus pour catégories
- [ ] Recherche globale
- [ ] Préférences utilisateur (notifications)
