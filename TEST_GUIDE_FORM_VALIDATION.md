# 🧪 Guide de test - Validation Formulaire d'Annonce

## ✅ Test 1 : Validation des images (Frontend)

### Scénario 1.1 : Ajouter 8 images
**Étapes :**
1. Aller sur http://127.0.0.1:8000/annonces/new
2. Cliquer sur "Sélectionner des fichiers"
3. Essayer de sélectionner 8 images JPG valides

**Résultat attendu :**
- ❌ Toast d'erreur : "Vous ne pouvez pas ajouter plus de 6 images. Actuellement : X/6"
- ✅ Aucune image n'est ajoutée

### Scénario 1.2 : Image > 1 Mo
**Étapes :**
1. Chercher/créer une image > 1 Mo (ex: 2 Mo)
2. Essayer de l'ajouter au formulaire

**Résultat attendu :**
- ❌ Toast d'erreur : "image.jpg dépasse 1 Mo (2.50 Mo). Fichier rejeté."
- ✅ L'image n'est pas ajoutée
- ✅ Compteur reste inchangé

### Scénario 1.3 : Format non autorisé
**Étapes :**
1. Essayer de charger un fichier `.gif` ou `.bmp`

**Résultat attendu :**
- ❌ Toast d'erreur : "image.gif : Format non accepté. Utilisez JPG, PNG ou WEBP."
- ✅ Le fichier est rejeté

### Scénario 1.4 : Ajouter 6 images + essayer 1 de plus
**Étapes :**
1. Charger 6 images valides (< 1 Mo, JPG/PNG/WEBP)
2. Essayer d'ajouter une 7e image

**Résultat attendu :**
- ✅ Les 6 images s'ajoutent avec toast de succès
- ✅ Input est désactivé après 6 images
- ❌ 7e image est rejetée

### Scénario 1.5 : Supprimer une image
**Étapes :**
1. Avoir 3 images dans le formulaire
2. Cliquer le X rouge sur la 2e image

**Résultat attendu :**
- ✅ L'image est supprimée
- ✅ Toast : "Image supprimée"
- ✅ Compteur passe de 3 à 2
- ✅ Grille se réorganise

---

## ✅ Test 2 : Compteur de caractères (Frontend)

### Scénario 2.1 : Écrire dans la description
**Étapes :**
1. Cliquer dans le champ "Description"
2. Taper du texte au fur et à mesure

**Résultat attendu :**
- ✅ Compteur en bas à droite : "X / 2000 caractères"
- ✅ Compteur se met à jour en temps réel
- ✅ Texte normal tant que < 1800 caractères

### Scénario 2.2 : Dépasser 1800 caractères
**Étapes :**
1. Taper 1900 caractères (ctrl+A pour copier-coller)

**Résultat attendu :**
- ✅ Texte du compteur devient orange et gras
- ✅ Alerte visuelle pour l'utilisateur

### Scénario 2.3 : Bloquer au-delà de 2000
**Étapes :**
1. Essayer de taper au-delà de 2000 caractères

**Résultat attendu :**
- ✅ Le textarea refuse les caractères supplémentaires (maxLength)
- ✅ Compteur reste à 2000

---

## ✅ Test 3 : Aide Markdown (Frontend)

### Scénario 3.1 : Cliquer sur le bouton d'aide
**Étapes :**
1. Chercher le bouton "Aide Markdown" à côté de "Description"
2. Cliquer dessus

**Résultat attendu :**
- ✅ Une modale s'ouvre (centrée, semi-transparente)
- ✅ Titre : "Aide-mémoire Markdown"
- ✅ Tableau avec syntaxe | Exemple | Résultat

### Scénario 3.2 : Vérifier le contenu de la modale
**Étapes :**
1. La modale est ouverte

**Résultat attendu :**
- ✅ Tableau avec 6 lignes :
  - `**texte**` → **Gras**
  - `*texte*` → *Italique*
  - `## Titre` → Titre 2
  - `- Item` → Liste à puces
  - `1. Item` → Liste numérotée
  - `[Lien](url)` → Lien

### Scénario 3.3 : Fermer la modale
**Étapes :**
1. Cliquer sur le X de la modale OU le bouton "Fermer"

**Résultat attendu :**
- ✅ Modale se ferme
- ✅ Retour au formulaire

---

## ✅ Test 4 : Validation Backend (API)

### Scénario 4.1 : Description > 2000 caractères
**Requête cURL :**
```bash
curl -X POST http://127.0.0.1:8000/api/annonces/new \
  -F "title=Test" \
  -F "description=$(python3 -c 'print(\"a\" * 2001)')" \
  -F "campus=CALAIS" \
  -F "type=DON" \
  -F "categoryId=9" \
  -F "images[]=@image1.jpg"
```

**Résultat attendu :**
- ❌ HTTP 400
- ❌ `{"error": "La description ne peut pas dépasser 2000 caractères."}`

### Scénario 4.2 : 7 images uploadées
**Requête cURL :**
```bash
curl -X POST http://127.0.0.1:8000/api/annonces/new \
  -F "title=Test" \
  -F "description=Description" \
  -F "campus=CALAIS" \
  -F "type=DON" \
  -F "categoryId=9" \
  -F "images[]=@img1.jpg" \
  -F "images[]=@img2.jpg" \
  -F "images[]=@img3.jpg" \
  -F "images[]=@img4.jpg" \
  -F "images[]=@img5.jpg" \
  -F "images[]=@img6.jpg" \
  -F "images[]=@img7.jpg"
```

**Résultat attendu :**
- ❌ HTTP 400
- ❌ `{"error": "Vous ne pouvez pas uploader plus de 6 images."}`

### Scénario 4.3 : Image > 1 Mo
**Préparation :**
```bash
# Créer une image de 2 Mo
convert -size 2000x2000 xc:blue big_image.jpg
```

**Requête cURL :**
```bash
curl -X POST http://127.0.0.1:8000/api/annonces/new \
  -F "title=Test" \
  -F "description=Description" \
  -F "campus=CALAIS" \
  -F "type=DON" \
  -F "categoryId=9" \
  -F "images[]=@big_image.jpg"
```

**Résultat attendu :**
- ❌ HTTP 400
- ❌ `{"error": "Image 1 : Chaque image ne doit pas dépasser 1 Mo"}`

### Scénario 4.4 : Upload valide complet
**Requête cURL :**
```bash
curl -X POST http://127.0.0.1:8000/api/annonces/new \
  -F "title=Un super objet" \
  -F "description=Ceci est une **belle** description *en markdown*" \
  -F "campus=CALAIS" \
  -F "type=DON" \
  -F "categoryId=9" \
  -F "images[]=@image1.jpg" \
  -F "images[]=@image2.png" \
  -F "images[]=@image3.webp"
```

**Résultat attendu :**
- ✅ HTTP 201
- ✅ Response JSON :
```json
{
  "message": "Annonce créée avec succès et envoyée pour validation !",
  "annonceId": "550e8400-e29b-41d4-a716-446655440000"
}
```
- ✅ Annonce créée en DB avec state=PENDING_REVIEW
- ✅ 3 images uploadées dans `/public/uploads/annonces/`

---

## ✅ Test 5 : Formulaire complet (End-to-End)

### Scénario 5.1 : Création d'annonce valide via formulaire
**Étapes :**
1. Aller sur http://127.0.0.1:8000/annonces/new
2. Remplir tous les champs :
   - Titre : "Livre de Python très utile" (max 255)
   - Description : "**Excellent** livre pour apprendre Python..." (< 2000)
   - Campus : Calais
   - Type : Don
   - Catégorie : Livres
   - Images : Charger 2-3 images JPG < 1 Mo

3. Cliquer "Publier l'annonce"

**Résultat attendu :**
- ✅ Toast "Envoi en cours..." (spinner)
- ✅ Toast de succès "Annonce envoyée ! En attente de validation."
- ✅ Redirection vers `/home` après 2 secondes
- ✅ Annonce visible dans le dashboard modération

### Scénario 5.2 : Validation client bloque avant envoi
**Étapes :**
1. Remplir le formulaire partiellement (manquer 1 champ)
2. Cliquer "Publier l'annonce"

**Résultat attendu :**
- ❌ Pas de requête réseau
- ❌ Message : "Tous les champs sont obligatoires"

### Scénario 5.3 : Validation server double-check
**Étapes :**
1. Modifier l'import cURL pour forcer un envoi avec image > 1 Mo
2. Le frontend l'aurait rejetée, mais tester si le backend refuse

**Résultat attendu :**
- ❌ HTTP 400
- ❌ Message d'erreur du backend

---

## 📊 Checklist d'acceptation

### Frontend React (AnnonceForm.jsx)
- [ ] Multi-upload d'images fonctionnelle
- [ ] Validation nombre images (6 max)
- [ ] Validation taille image (1 Mo max)
- [ ] Validation format (JPG/PNG/WEBP)
- [ ] Toasts d'erreur s'affichent
- [ ] Aperçu des images avec miniatures
- [ ] Bouton supprimer image fonctionne
- [ ] Compteur caractères description en temps réel
- [ ] Couleur orange à 90% (1800+)
- [ ] Bouton "Aide Markdown" visible
- [ ] Modale Markdown s'ouvre/ferme
- [ ] Tableau Markdown correct

### Backend PHP (AnnonceCreateController.php)
- [ ] Validation description max 2000 car
- [ ] Validation nombre images max 6
- [ ] Validation taille image 1 Mo max
- [ ] Validation format MIME
- [ ] Messages d'erreur clairs
- [ ] HTTP 400 sur erreur
- [ ] HTTP 201 sur succès
- [ ] Images uploadées en `/public/uploads/annonces/`
- [ ] Annonce créée en state PENDING_REVIEW

### Entités Symfony (Annonce.php)
- [ ] Assert\Length sur description
- [ ] Assert\Count sur images
- [ ] Imports corrects

---

## 🚀 Fin des tests

Quand tous les scénarios passent, la fonctionnalité est **prête pour la production** ! 🎉

**Date recommandée :** 23 février 2026
