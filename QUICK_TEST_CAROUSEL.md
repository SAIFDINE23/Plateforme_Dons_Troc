# 🎯 Quick Test - Carousel & Custom Category

## ⚡ Tests rapides à faire maintenant

### 1️⃣ Carrousel d'images

**Aller sur :** `http://127.0.0.1:8000/annonces/new`

```
1. Charger 3 images JPG valides
   ✓ Voir carrousel avec image grande
   ✓ Voir 3 miniatures en bas

2. Cliquer flèche droite
   ✓ Image change
   ✓ Miniature se met à jour
   ✓ Badge "X/3"

3. Cliquer 2e miniature
   ✓ Image principale change
   ✓ Bordure miniature devient bleue

4. Charger 6e image (max)
   ✓ Input désactivé
   ✓ Pas possible d'ajouter 7e

5. Charger nouvelle image (après avoir supprimé une)
   ✓ Input réactivé
   ✓ Compteur correct
```

### 2️⃣ Catégorie personnalisée

**Aller sur :** `http://127.0.0.1:8000/annonces/new`

```
1. Sélectionner "Livres"
   ✓ Pas d'input additionnel
   ✓ Aucun champ "Préciser votre catégorie"

2. Sélectionner "Autre (préciser)"
   ✓ Affiche input "Préciser votre catégorie"
   ✓ Placeholder : "Ex: Jeux vidéo, Instruments de musique, etc."
   ✓ Message : "Cette catégorie sera validée par nos modérateurs"

3. Taper "Jeux vidéo"
   ✓ Texte visible
   ✓ Laisser vide + soumettre → erreur client

4. Remplir tout + soumettre
   ✓ Annonce créée avec success toast
   ✓ Redirection /home

5. Aller en modération (admin dashboard)
   ✓ Voir l'annonce avec catégorie "Autre"
   ✓ Ou voir "Autre : Jeux vidéo" selon implémentation
```

---

## 🔍 Vérifications en base de données

```bash
# Terminal - Se connecter à PostgreSQL
psql -U postgres -d plateforme_dons_troc

# Vérifier colonne ajoutée
SELECT * FROM annonce WHERE custom_category_name IS NOT NULL;

# Vérifier une annonce créée avec custom category
SELECT id, title, custom_category_name, category_id FROM annonce 
WHERE custom_category_name IS NOT NULL 
ORDER BY id DESC LIMIT 1;
```

**Résultat attendu :**
```
 id                   | title      | custom_category_name  | category_id
──────────────────────┼────────────┼──────────────────────┼────────────
 550e8400-e29b-41d4.. | Test       | Jeux vidéo           | [category_id]
```

---

## 🧪 Test complet : Création + Modération

```
Scénario : Un utilisateur crée une annonce "Jeux vidéo" (catégorie Autre)

1. User side:
   ↓ Va sur /annonces/new
   ↓ Titre: "PS5 en très bon état"
   ↓ Description: "Console **PlayStation 5** en parfait état"
   ↓ Campus: Calais
   ↓ Type: Don
   ↓ Catégorie: "🔍 Autre (préciser)"
   ↓ Input: "Jeux vidéo"
   ↓ Upload 2 images JPG (PS5)
   ↓ Clique flèche carrousel pour vérifier
   ↓ Clique miniature
   ↓ Soumet formulaire
   ↓ Toast "Envoi en cours..."
   ↓ Toast "Annonce envoyée !"
   ↓ Redirection /home

2. Modérateur side:
   ↓ Va sur /admin/dashboard
   ↓ Voit annonce "PS5 en très bon état"
   ↓ Clique "Voir détails"
   ↓ Auto-lock ✓
   ↓ Voit 2 images en carrousel
   ↓ Voit catégorie : "Autre : Jeux vidéo" ou similar
   ↓ Valide ou refuse
   ↓ Auto-unlock ✓
   ↓ Retour dashboard

3. Vérification DB:
   ↓ custom_category_name = "Jeux vidéo"
   ↓ state = PUBLISHED (si validée)
```

---

## ✅ Checklist finale

- [ ] Carrousel fonctionne avec 1 image
- [ ] Carrousel fonctionne avec 6 images
- [ ] Navigation flèches OK
- [ ] Miniatures cliquables OK
- [ ] Catégorie "Autre" affiche input
- [ ] Input catégorie custom validé (obligatoire si "Autre" sélectionné)
- [ ] Texte custom catégorie stocké en DB
- [ ] Pas d'input si autre catégorie sélectionnée après "Autre"
- [ ] Carrousel affiche bien les images en modération
- [ ] Pas de console errors (vérifier DevTools F12)
- [ ] Assets compilés (npm run build = OK)

---

**Test recommandé :** Créer une annonce avec "Jeux vidéo" ou "Instruments de musique"

Bon testing ! 🚀
