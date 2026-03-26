# 🎯 Validation et Amélioration du Formulaire de Création d'Annonce

## 📋 Résumé des modifications

Ce document récapitule l'implémentation complète des 3 tâches de validation et d'amélioration du formulaire de création d'annonce.

---

## ✅ 1. BACKEND : Contraintes de validation Symfony

### Fichier : `src/Entity/Annonce.php`

**Imports ajoutés :**
```php
use Symfony\Component\Validator\Constraints as Assert;
```

**Contraintes ajoutées :**

#### 1.1 Description - Limite de 2000 caractères
```php
#[ORM\Column(type: Types::TEXT)]
#[Assert\NotBlank(message: 'La description est obligatoire')]
#[Assert\Length(
    max: 2000,
    maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
)]
private ?string $description = null;
```

#### 1.2 Collection d'images - Maximum 6 images
```php
/**
 * @var Collection<int, AnnonceImage>
 */
#[ORM\OneToMany(targetEntity: AnnonceImage::class, mappedBy: 'annonce', orphanRemoval: true, cascade: ['persist'])]
#[Assert\Count(
    max: 6,
    maxMessage: 'Vous ne pouvez pas ajouter plus de {{ limit }} images'
)]
private Collection $images;
```

### Fichier : `src/Controller/Api/AnnonceCreateController.php`

**Modifications du contrôleur :**

1. **Support du multi-upload** : Récupération de plusieurs fichiers avec `images[]`
2. **Validation de la taille de la description** : Vérification de 2000 caractères max
3. **Validation du nombre d'images** : Maximum 6 images
4. **Validation de chaque image** : 
   - Taille max : **1 Mo** (au lieu de 2 Mo précédemment)
   - Formats : JPG, PNG, WEBP

**Code de validation :**
```php
// Validation de la description
if (strlen($description) > 2000) {
    return $this->json(['error' => 'La description ne peut pas dépasser 2000 caractères.'], 400);
}

// Validation du nombre d'images (max 6)
if (count($imageFiles) > 6) {
    return $this->json(['error' => 'Vous ne pouvez pas uploader plus de 6 images.'], 400);
}

// Validation de chaque image (1 Mo max)
foreach ($imageFiles as $index => $imageFile) {
    $violations = $validator->validate($imageFile, [
        new Assert\File([
            'maxSize' => '1M',
            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
        ])
    ]);
}
```

---

## ✅ 2. FRONTEND : Validation Client (React)

### Fichier : `assets/react/controllers/AnnonceForm.jsx`

**Constantes de validation :**
```javascript
const MAX_FILES = 6;
const MAX_FILE_SIZE = 1048576; // 1 Mo en octets
const MAX_DESCRIPTION_LENGTH = 2000;
```

#### 2.1 Multi-upload d'images avec validation
- ✅ Vérification du nombre total (max 6)
- ✅ Vérification de la taille (max 1 Mo par image)
- ✅ Vérification des formats (JPG, PNG, WEBP)
- ✅ Messages d'erreur détaillés via Toast
- ✅ Aperçu des images avec bouton de suppression

**Code de validation :**
```javascript
const handleFileChange = (e) => {
    const selectedFiles = Array.from(e.target.files);
    
    // Vérifier le nombre total
    const totalFiles = files.length + selectedFiles.length;
    if (totalFiles > MAX_FILES) {
        toast.error(`Vous ne pouvez pas ajouter plus de ${MAX_FILES} images...`);
        return;
    }

    // Valider chaque fichier
    for (const file of selectedFiles) {
        // Vérification de la taille (1 Mo max)
        if (file.size > MAX_FILE_SIZE) {
            toast.error(`${file.name} dépasse 1 Mo. Fichier rejeté.`);
            continue;
        }

        // Vérification du type
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            toast.error(`${file.name} : Format non accepté.`);
            continue;
        }
    }
};
```

#### 2.2 Compteur de caractères pour la description
- Affiche le nombre actuel / maximum (ex: "450 / 2000 caractères")
- Change de couleur en orange quand on dépasse 90% (1800+ caractères)
- Texte en gras + icône de warning

**HTML :**
```jsx
<span className={description.length > MAX_DESCRIPTION_LENGTH * 0.9 ? 'text-warning fw-bold' : ''}>
    {description.length} / {MAX_DESCRIPTION_LENGTH} caractères
</span>
```

#### 2.3 Gestion des images avec aperçu
- Multi-sélection de fichiers
- Aperçu en miniatures (grille 6 colonnes)
- Bouton suppression sur chaque image
- Input désactivé une fois 6 images atteintes
- Compteur : "X / 6 images"

---

## ✅ 3. FRONTEND : Bouton d'aide Markdown

### Modale avec aide-mémoire complète

**Syntaxes supportées :**

| Syntaxe | Exemple | Résultat |
|---------|---------|----------|
| `**texte**` | `**Gras**` | **Gras** |
| `*texte*` | `*Italique*` | *Italique* |
| `## Titre` | `## Titre 2` | Titre 2 |
| `- Item` | `- Premier` | • Premier |
| `1. Item` | `1. Premier` | 1. Premier |
| `[Lien](url)` | `[Google](google.com)` | [Google]() |

**Fonctionnalités :**
- ✅ Bouton "Aide Markdown" à côté du label (avec icône `?`)
- ✅ Modale centrée au clic
- ✅ Tableau interactif avec syntaxe et résultats
- ✅ Message d'astuce en bas
- ✅ Fermeture facile

**Code :**
```jsx
<button
    type="button"
    className="btn btn-sm btn-outline-secondary"
    onClick={() => setShowMarkdownHelp(true)}
>
    <i className="bi bi-question-circle me-1"></i>
    Aide Markdown
</button>
```

---

## 📊 Flux de soumission amélioré

```
┌─────────────────────────────────────┐
│ 1. Utilisateur remplit le formulaire │
└────────────────┬────────────────────┘
                 │
┌────────────────▼────────────────────┐
│ 2. VALIDATION CLIENT (React)         │
│    • Titre, Description, Catégorie   │
│    • Taille description (2000 max)   │
│    • Nombre images (6 max)           │
│    • Taille chaque image (1 Mo max)  │
│    • Format image (JPG/PNG/WEBP)     │
└────────────────┬────────────────────┘
                 │
       ┌─────────┴──────────┐
       │                    │
   ✅ VALIDE           ❌ ERREUR
       │                    │
       ▼                    ▼
  SUBMIT            Afficher Toast
       │
┌──────▼──────────────────────────────┐
│ 3. VALIDATION SERVER (Symfony)       │
│    • Vérifications identiques        │
│    • Upload sécurisé                 │
│    • Validation ORM/Doctrine         │
└────────────────┬────────────────────┘
                 │
       ┌─────────┴──────────┐
       │                    │
   ✅ CRÉÉ          ❌ ERREUR
       │                    │
       ▼                    ▼
  Annonce PENDING    Erreur 400
  (en modération)
```

---

## 🔒 Sécurité renforcée

### Double validation (Client + Serveur)
- ✅ Validation précoce côté client pour UX
- ✅ Validation stricte côté serveur (confiance zéro)
- ✅ Messages d'erreur clairs et actionnables

### Protections des fichiers
- ✅ Vérification du type MIME
- ✅ Limite de taille stricte (1 Mo)
- ✅ Renommage sécurisé des fichiers
- ✅ Stockage en `/public/uploads/annonces`

### Validation des données texte
- ✅ Limitation de la description (2000 caractères)
- ✅ Validation de longueur minimum (implicite via form)
- ✅ Échappement des caractères spéciaux

---

## 📱 UX améliorée

### Retours utilisateur
- ✅ Toast notifications (succès, erreur)
- ✅ Compteur de caractères en temps réel
- ✅ Aperçu des images avec miniatures
- ✅ Bouton suppression rapide sur images
- ✅ Messages d'erreur spécifiques par image

### Accessibilité
- ✅ Labels explicites avec `*` obligatoire
- ✅ Placeholder d'aide
- ✅ Description sous champ avec infos
- ✅ Modale pour aide Markdown (centré, modal)

---

## 🧪 Cas de test recommandés

### Frontend
1. ✅ Sélectionner 8 images → Erreur au dépassement
2. ✅ Image de 2 Mo → Rejetée avec message
3. ✅ Image en format `.gif` → Rejetée
4. ✅ Écrire 2500 caractères → Compteur orange
5. ✅ Supprimer une image → Affichage mis à jour

### Backend
1. ✅ POST avec 8 images → Erreur 400
2. ✅ Image > 1 Mo → Erreur 400
3. ✅ Description > 2000 car. → Erreur 400
4. ✅ Upload valide → Création annonce PENDING_REVIEW

---

## 📝 Fichiers modifiés

```
src/Entity/Annonce.php
├─ Imports Assert Symfony
├─ Contrainte Length sur description (max 2000)
└─ Contrainte Count sur images (max 6)

src/Controller/Api/AnnonceCreateController.php
├─ Support multi-upload images[]
├─ Validation taille description (2000 max)
├─ Validation nombre images (6 max)
├─ Validation taille image (1 Mo max)
└─ Traitement boucle pour toutes les images

assets/react/controllers/AnnonceForm.jsx
├─ État files[] et previews[] (multi-images)
├─ Constantes validation (MAX_FILES, MAX_FILE_SIZE, etc.)
├─ Fonction handleFileChange (multi-upload validation)
├─ Fonction removeImage (suppression image)
├─ Compteur caractères description
├─ Bouton Aide Markdown
└─ Modale aide-mémoire Markdown
```

---

## ✨ Prochaines améliorations possibles

- [ ] Drag & drop pour images
- [ ] Compression d'images côté client
- [ ] Galerie avec réorganisation (drag-reorder)
- [ ] Support de l'éditeur Markdown WYSIWYG
- [ ] Validation en temps réel du titre
- [ ] Sauvegarde automatique (brouillon)

---

**Dernière modification :** 23 février 2026  
**Status :** ✅ Implémentation complète
