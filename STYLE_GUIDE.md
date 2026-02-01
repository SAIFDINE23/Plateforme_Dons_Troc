# 🎨 Guide de Style - ULC'OCCAZ

## Palette de Couleurs EILCO

### Couleurs Primaires
```scss
$primary: #004E86;    // Bleu EILCO - Navbar, Titres
$secondary: #009FE3;  // Cyan - Dégradés, Icônes
$accent: #F07D00;     // Orange - Boutons CTA
```

### Couleurs de Fond
```scss
$body-bg: #F4F7FA;    // Gris bleuté pâle
$footer-bg: #003355;  // Bleu foncé institutionnel
```

### Utilisation des Couleurs

#### 🔵 Bleu Primary (#004E86)
**Où l'utiliser :**
- Titres principaux (h1, h2, h3)
- Icônes de métadonnées
- Bordures de focus formulaires
- Texte des labels

**Exemple :**
```jsx
<User size={20} style={{ color: '#004E86' }} />
<h1 style={{ color: '#004E86' }}>Titre</h1>
```

#### 🌊 Cyan Secondary (#009FE3)
**Où l'utiliser :**
- Icônes de localisation (MapPin)
- Dégradés avec Primary
- Bordures au focus des inputs
- Spinner de chargement

**Exemple :**
```jsx
<MapPin size={18} style={{ color: '#009FE3' }} />
<div className="spinner-eilco"></div>
```

#### 🟠 Orange Accent (#F07D00)
**Où l'utiliser :**
- Boutons d'action principaux (CTA)
- Badges "Troc"
- Icônes de catégorie (Tag)
- Boutons de contact

**Exemple :**
```jsx
<button className="btn btn-primary">Contacter</button>
<Tag size={18} style={{ color: '#F07D00' }} />
```

---

## Typographie

### Polices
```scss
// Titres
font-family: 'Montserrat', sans-serif;
font-weight: 600 | 700;

// Corps de texte
font-family: 'Roboto', sans-serif;
font-weight: 400 | 500;
```

### Hiérarchie des Titres
```jsx
<h1>2.5rem (40px) - Montserrat 600</h1>
<h2>2rem (32px) - Montserrat 600</h2>
<h3>1.75rem (28px) - Montserrat 600</h3>
<h4>1.5rem (24px) - Montserrat 600</h4>
<h5>1.25rem (20px) - Montserrat 600</h5>
<h6>1rem (16px) - Montserrat 600</h6>

<p>1rem (16px) - Roboto 400</p>
<small>0.875rem (14px) - Roboto 400</small>
```

---

## Composants React avec Lucide Icons

### Importation
```jsx
import { MapPin, Tag, Clock, Eye, Bell, User, Mail } from 'lucide-react';
```

### Tailles Recommandées
```jsx
// Petite icône (inline text)
<Clock size={16} />

// Icône standard (métadonnées)
<MapPin size={18} />

// Grande icône (boutons)
<MessageCircle size={20} />

// Très grande (hero sections)
<Bell size={32} />
```

### Icônes par Contexte

#### 📍 Localisation
```jsx
<MapPin size={18} style={{ color: '#009FE3' }} />
Campus: Calais
```

#### 🏷️ Catégorie
```jsx
<Tag size={18} style={{ color: '#F07D00' }} />
Catégorie: Livres
```

#### ⏰ Date/Temps
```jsx
<Clock size={16} style={{ color: '#004E86' }} />
Publié le 15 janvier
```

#### 👤 Utilisateur
```jsx
<User size={18} className="text-muted" />
Par: Jean Dupont
```

#### 📧 Email
```jsx
<Mail size={16} className="text-muted" />
contact@ulco.fr
```

#### 🔔 Notifications
```jsx
// Normal
<Bell size={20} style={{ color: '#004E86' }} />

// Nouvelle notification
<BellRing size={20} style={{ color: '#F07D00' }} />
```

#### ✅ Succès
```jsx
<CheckCircle size={20} className="text-success" />
Annonce validée
```

#### ❌ Erreur
```jsx
<XCircle size={20} className="text-danger" />
Annonce refusée
```

---

## Animations Framer Motion

### Animation de Base (Fade In)
```jsx
import { motion } from 'framer-motion';

<motion.div
  initial={{ opacity: 0, y: 20 }}
  animate={{ opacity: 1, y: 0 }}
  transition={{ duration: 0.5 }}
>
  Contenu
</motion.div>
```

### Stagger Children (Liste)
```jsx
{items.map((item, index) => (
  <motion.div
    key={item.id}
    initial={{ opacity: 0, y: 20 }}
    animate={{ opacity: 1, y: 0 }}
    transition={{ 
      duration: 0.5, 
      delay: index * 0.1  // Stagger effect
    }}
  >
    {item.title}
  </motion.div>
))}
```

### Hover Animations
```jsx
<motion.button
  whileHover={{ scale: 1.02 }}
  whileTap={{ scale: 0.98 }}
  className="btn btn-primary"
>
  Cliquez-moi
</motion.button>
```

### AnimatePresence (Conditional Rendering)
```jsx
import { AnimatePresence } from 'framer-motion';

<AnimatePresence>
  {isVisible && (
    <motion.div
      initial={{ opacity: 0, scale: 0.9 }}
      animate={{ opacity: 1, scale: 1 }}
      exit={{ opacity: 0, scale: 0.9 }}
    >
      Message
    </motion.div>
  )}
</AnimatePresence>
```

### Animation de Sonnerie (Bell Shake)
```jsx
const bellAnimation = {
  rotate: [0, -15, 15, -15, 15, 0],
  transition: { duration: 0.5 }
};

<motion.div animate={bellAnimation}>
  <BellRing size={20} />
</motion.div>
```

---

## Classes Bootstrap Personnalisées

### Cartes
```html
<!-- Carte moderne avec hover -->
<div class="card card-annonce">
  <!-- Shadow-sm par défaut, shadow au hover -->
  <!-- Border-radius: 0.75rem -->
  <!-- Transition: transform 0.3s -->
</div>
```

### Badges
```html
<!-- Badge dégradé EILCO -->
<span class="badge badge-eilco">Campus</span>

<!-- Badge accent orange -->
<span class="badge badge-accent">Important</span>

<!-- Badge de statut -->
<span class="status-badge status-published">PUBLIÉ</span>
<span class="status-badge status-pending">EN ATTENTE</span>
<span class="status-badge status-rejected">REJETÉ</span>
<span class="status-badge status-completed">TERMINÉ</span>
```

### Boutons
```html
<!-- CTA Orange -->
<button class="btn btn-primary btn-pill">
  Action principale
</button>

<!-- Outline Bleu -->
<button class="btn btn-outline-primary">
  Action secondaire
</button>

<!-- Avec icône -->
<button class="btn btn-primary d-flex align-items-center gap-2">
  <MessageCircle size={20} />
  <span>Contacter</span>
</button>
```

### Hero Sections
```html
<!-- Hero avec pattern dégradé -->
<div class="bg-hero-pattern rounded-4 p-5 text-center">
  <h1 class="text-white">Titre</h1>
  <p class="lead text-white">Sous-titre</p>
</div>

<!-- Alternative: Motif carrés -->
<div class="bg-pattern-squares p-5">
  <h2>Contenu</h2>
</div>
```

---

## Bonnes Pratiques

### ✅ À FAIRE

1. **Utiliser les classes SCSS personnalisées**
   ```jsx
   <div className="card card-annonce">
   ```

2. **Combiner Bootstrap + Framer Motion**
   ```jsx
   <motion.div className="btn btn-primary" whileHover={{ scale: 1.02 }}>
   ```

3. **Couleurs cohérentes avec Lucide**
   ```jsx
   <MapPin size={18} style={{ color: '#009FE3' }} />
   ```

4. **Animations fluides**
   ```jsx
   transition={{ duration: 0.3, ease: [0.4, 0, 0.2, 1] }}
   ```

5. **Responsive Design**
   ```html
   <div class="col-12 col-md-6 col-lg-4">
   ```

### ❌ À ÉVITER

1. **Couleurs hardcodées aléatoires**
   ```jsx
   // ❌ NON
   <div style={{ color: '#ff0000' }}>

   // ✅ OUI
   <div style={{ color: '#F07D00' }}>
   ```

2. **Animations trop lourdes**
   ```jsx
   // ❌ NON
   transition={{ duration: 2 }}

   // ✅ OUI
   transition={{ duration: 0.3 }}
   ```

3. **Icônes Bootstrap Icons (obsolètes)**
   ```jsx
   // ❌ NON
   <i className="bi bi-bell"></i>

   // ✅ OUI
   <Bell size={20} />
   ```

4. **Styles inline complexes**
   ```jsx
   // ❌ NON
   <div style={{ background: 'red', padding: '20px', ... }}>

   // ✅ OUI
   <div className="card card-annonce p-4">
   ```

---

## Checklist Composant

Avant de créer un nouveau composant React :

- [ ] Importer Lucide icons nécessaires
- [ ] Importer Framer Motion si animations
- [ ] Utiliser palette EILCO (#004E86, #009FE3, #F07D00)
- [ ] Classes Bootstrap pour layout (grid, spacing)
- [ ] Classes personnalisées (.card-annonce, .badge-eilco)
- [ ] Animations smooth (0.2s - 0.5s max)
- [ ] Responsive (col-12 col-md-6 col-lg-4)
- [ ] Icônes colorées selon contexte
- [ ] Hover states sur éléments cliquables
- [ ] Loading states avec spinner-eilco

---

## Exemples Complets

### Carte d'Annonce
```jsx
import { motion } from 'framer-motion';
import { MapPin, Tag, Clock, Eye } from 'lucide-react';

<motion.div
  whileHover={{ scale: 1.02 }}
  className="card card-annonce"
>
  <img src="..." className="card-img-top" />
  <div class="card-body">
    <h5 className="card-title">Vélo VTT</h5>
    <div className="d-flex align-items-center gap-2 mb-2">
      <MapPin size={16} style={{ color: '#009FE3' }} />
      <span className="small">Calais</span>
    </div>
    <div className="d-flex align-items-center gap-2 mb-2">
      <Tag size={16} style={{ color: '#F07D00' }} />
      <span className="small">Sports</span>
    </div>
    <div className="d-flex align-items-center gap-2">
      <Clock size={16} style={{ color: '#004E86' }} />
      <span className="small">Il y a 2 jours</span>
    </div>
  </div>
  <div className="card-footer">
    <button className="btn btn-primary btn-pill w-100">
      <Eye size={18} /> Voir l'annonce
    </button>
  </div>
</motion.div>
```

### Notification Bell
```jsx
import { Bell, BellRing } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

const NotificationBell = ({ count, hasNew }) => (
  <motion.button 
    className="btn btn-light position-relative"
    animate={hasNew ? { rotate: [0, -15, 15, 0] } : {}}
  >
    {hasNew ? (
      <BellRing size={20} style={{ color: '#F07D00' }} />
    ) : (
      <Bell size={20} style={{ color: '#004E86' }} />
    )}
    <AnimatePresence>
      {count > 0 && (
        <motion.span 
          className="badge-notification"
          initial={{ scale: 0 }}
          animate={{ scale: 1 }}
        >
          {count}
        </motion.span>
      )}
    </AnimatePresence>
  </motion.button>
);
```

---

**Développé pour l'EILCO - Université du Littoral Côte d'Opale** 🎓
