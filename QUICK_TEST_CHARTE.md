# 🧪 Quick Test - Charte Stepper

## ⚡ Test rapide (5 minutes)

### 1️⃣ Accès au composant

Ouvre dans le navigateur :
```
http://127.0.0.1:8000/charte/stepper
```

**Attendre:** Page charge avec design dark blue/blanc

---

### 2️⃣ Vérifier l'UI

```
✓ En-tête dark blue (#001a33)
✓ Titre: "Charte ULC'OCCAZ"
✓ Badge: "Étape 1/4"
✓ Barre de progression (très fine, dark blue)
✓ Texte de la 1ère section: "Esprit de la plateforme"
✓ 4 badges numérotés (1=dark blue, 2,3,4=gray)
✓ Bouton bleu: "J'ai lu et j'accepte cette partie"
✓ Lien "Revenir à l'accueil"
```

---

### 3️⃣ Accepter section 1

Clique sur bouton bleu

**Attendre:**
```
✓ Passe à étape 2
✓ Badge 1 reste dark blue
✓ Badge 2 devient dark blue (actuelle)
✓ Barre progression remplit à 50% (2/4)
✓ Texte change: "Objets interdits et limites"
✓ Bouton réactivé
```

---

### 4️⃣ Accepter sections 2, 3

Répète 2 fois le même processus

**À étape 3:**
```
✓ Badge 3 dark blue
✓ Barre 75% remplie
✓ Texte: "Respect, courtoisie et rendez-vous"
```

---

### 5️⃣ Accepter section 4 (FINALE)

Clique sur bouton (maintenant: "Accepter la charte et finaliser mon inscription")

**Attendre:**
```
✓ Spinner "Finalisation en cours..."
✓ Toast vert: "Charte acceptée avec succès !"
✓ Redirection vers / (home page) après 1 sec
```

---

### 6️⃣ Vérification BD

Si tu veux vérifier les enregistrements :

```bash
# Terminal PostgreSQL
psql -U postgres -d plateforme_dons_troc

# Query
SELECT c.id, c.section_name, c.agreed_at, u.email
FROM charte_agreement c
JOIN user u ON c.user_id = u.id
ORDER BY c.agreed_at DESC
LIMIT 4;
```

**Résultat attendu:**
```
 id | section_name              | agreed_at            | email
────┼──────────────────────────┼─────────────────────┼─────────────
  1 | Responsabilité de l'ULCO | 2026-02-23 14:35... | test@example.com
  2 | Respect, courtoisie...   | 2026-02-23 14:34... | test@example.com
  3 | Objets interdits...      | 2026-02-23 14:33... | test@example.com
  4 | Esprit de la plateforme  | 2026-02-23 14:32... | test@example.com
```

---

## 🔍 Checklist détaillée

### Design & CSS

- [ ] Fond blanc, texte noir/dark blue
- [ ] Logo dark blue (#001a33) visible
- [ ] Pas de couleurs fluo/chat
- [ ] Espacement cohérent (padding, margin)
- [ ] Rounded corners (24px) sur boutons
- [ ] Responsive mobile (test sur F12 mobile view)

### Logique Stepper

- [ ] Section 1 affichée au démarrage
- [ ] Badge progression correcte (X/4)
- [ ] Barre progression remplit correctement
- [ ] Sections acceptées marquées dark blue
- [ ] Pas possible de skiper une étape
- [ ] Au refresh page, reste sur même étape

### Boutons & Actions

- [ ] Étapes 1-3: texte "J'ai lu et j'accepte cette partie"
- [ ] Étape 4: texte "Accepter la charte et finaliser mon inscription"
- [ ] Bouton "Revenir à l'accueil" toujours visible
- [ ] Spinner pendant l'envoi API
- [ ] Boutons désactivés pendant le traitement

### API Integration

- [ ] Appel POST à `/api/user/charte/accept`
- [ ] Payload JSON correct
- [ ] Réponse 200 OK
- [ ] Toast success s'affiche
- [ ] Redirection /

### Database

- [ ] 4 enregistrements `charte_agreement` créés
- [ ] Champ `section_name` correct
- [ ] Champ `agreed_at` horodaté
- [ ] `user_id` correct
- [ ] Pas de doublons si re-soumission

---

## 🐛 Dépannage

### Problème: Page blanche

**Solution:**
```bash
# Check console DevTools (F12)
# Vérifier logs Symfony
tail -50 var/log/dev.log
```

### Problème: Composant React ne charge pas

**Solution:**
```bash
# Recompile assets
npm run build

# Clear cache Symfony
php bin/console cache:clear
```

### Problème: API 404

**Solution:**
```bash
# Vérifier route existe
php bin/console debug:router | grep charte

# Expected output:
# app_user_charte_accept    POST  /api/user/charte/accept
```

### Problème: Design différent

**Solution:**
```bash
# Vérifier Bootstrap inclus
# Check base.html.twig pour encore_entry_link_tags
# Recompile assets et hard-refresh browser (Ctrl+Shift+R)
```

---

## ✅ Test Scenario Complet

```
User: jmoderator@example.com / password: 00000000

1. Login
2. GET http://127.0.0.1:8000/charte/stepper
3. Click "J'ai lu..." x 3 fois
4. Click "Accepter la charte et finaliser mon inscription"
5. Attendre redirection
6. Vérifier BD

Expected: 4 charteAgreement rows pour ce user
```

---

## 📸 Screenshots à vérifier

- [ ] Étape 1: "Esprit de la plateforme" visible
- [ ] Badge 1/4 en haut à droite
- [ ] Barre progression 25% remplie
- [ ] Boutons dark blue
- [ ] Layout responsive

---

**Bon testing ! 🚀**

Si tout est ✅, c'est prêt pour la production !
