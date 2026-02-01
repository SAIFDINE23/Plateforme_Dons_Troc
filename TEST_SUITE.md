# 🧪 TEST SUITE - Gestion des Annonces

## Préparation

**Serveur** : http://localhost:8000
**Base de données** : plateforme_dons_troc (PostgreSQL)

### Utilisateurs de Test Disponibles
- `sleroy` (ROLE_USER) - Password: 00000000 - Campus: N/A
- `jdupont` (ROLE_MODERATOR) - Campus: CALAIS - Password: 00000000
- `lbernard` (ROLE_MODERATOR) - Campus: SAINT_OMER - Password: 00000000
- `mcurie` (ROLE_MODERATOR) - Campus: DUNKERQUE - Password: 00000000
- `pmartin` (ROLE_MODERATOR) - Campus: BOULOGNE - Password: 00000000
- `aglobal` (ROLE_ADMIN) - Campus: N/A (Global) - Password: 00000000

### Données de Test
- 3 annonces "Annonce à modérer #1,2,3" en `PENDING_REVIEW` (CALAIS, propriétaire: sleroy)
- 2 annonces personnelles (sleroy) en `PUBLISHED` et `DRAFT`
- 1 annonce test en `PENDING_REVIEW` (SAINT_OMER)

---

## TEST 1 : MyAnnonces - Utilisateur Lambda

### Objectif
Vérifier que l'utilisateur voit UNIQUEMENT ses annonces, avec les bons statuts.

### Étapes
1. Ouvrir le navigateur → http://localhost:8000/login
2. Se connecter : `cas_uid=sleroy`, `password=00000000`
3. Cliquer sur "Mes Annonces" dans le dropdown utilisateur
4. Vérifier que **exactement 5 annonces** s'affichent :
   - test (PENDING_REVIEW - 🟡 En attente)
   - Annonce à modérer #1,2,3 (PENDING_REVIEW - 🟡 En attente)
   - Ma première annonce (PUBLISHED - 🟢 En ligne)
   - Ma deuxième annonce (DRAFT - ⚫ Brouillon)

### Critères d'Acceptation
- ✅ Badges de couleur corrects
- ✅ Aucune annonce d'autres utilisateurs n'apparaît
- ✅ Les images s'affichent (ou placeholder)
- ✅ Les dates sont au format "01/02/2025"
- ✅ Le bouton "Voir détails" est cliquable
- ✅ L'alerte "Aucune annonce" s'affiche si utilisateur sans annonces

### Commande CURL
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/my/annonces
```

---

## TEST 2 : ModerationDashboard - Modérateur Local (CALAIS)

### Objectif
Vérifier que le modérateur CALAIS ne voit que les annonces de CALAIS en PENDING_REVIEW.

### Étapes
1. Déconnecter (sleroy)
2. Se connecter : `cas_uid=jdupont`, `password=00000000` (Modérateur CALAIS)
3. Cliquer sur "Espace Modération" (navbar)
4. Vérifier que **exactement 3 annonces** s'affichent :
   - Annonce à modérer #1 (CALAIS, PENDING_REVIEW)
   - Annonce à modérer #2 (CALAIS, PENDING_REVIEW)
   - Annonce à modérer #3 (CALAIS, PENDING_REVIEW)
5. L'annonce "test" de SAINT_OMER NE DOIT PAS s'afficher

### Critères d'Acceptation
- ✅ Tableau avec colonnes : Date | Campus | Titre | Auteur | Actions
- ✅ Boutons "✅ Valider" et "❌ Refuser" présents
- ✅ Le campus CALAIS s'affiche en badge bleu
- ✅ Pas d'annonces d'autres campus
- ✅ Spinner de chargement visible lors du click sur un bouton

### Commande CURL
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/pending
```

---

## TEST 3 : ModerationDashboard - Admin Global

### Objectif
Vérifier que l'admin voit TOUTES les annonces PENDING_REVIEW, tous campus confondus.

### Étapes
1. Déconnecter (jdupont)
2. Se connecter : `cas_uid=aglobal`, `password=00000000` (Admin Global)
3. Cliquer sur "Administration" (navbar)
4. Vérifier que **exactement 4 annonces** s'affichent :
   - Annonce à modérer #1,2,3 (CALAIS)
   - test (SAINT_OMER)
5. Les campus différents s'affichent en badges distincts

### Critères d'Acceptation
- ✅ 4 annonces visibles (global)
- ✅ Campus variés (CALAIS, SAINT_OMER)
- ✅ L'admin peut valider une annonce d'un autre campus

### Commande CURL
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/pending
```

---

## TEST 4 : Validation d'Annonce (Happy Path)

### Objectif
Vérifier que la validation change l'état de PENDING_REVIEW à PUBLISHED.

### Étapes
1. Modérateur (jdupont) sur ModerationDashboard
2. Cliquer sur bouton "✅ Valider" pour "Annonce à modérer #1"
3. Vérifier que :
   - La ligne disparaît du tableau
   - La requête POST retourne 200 (ou 201)
   - Le state change en DB à PUBLISHED

### Critères d'Acceptation
- ✅ Requête POST `/api/admin/annonce/{id}/decide` envoyée
- ✅ Body correct : `{ "action": "validate" }`
- ✅ Réponse : message "Annonce validée avec succès"
- ✅ Ligne supprimée du tableau localement (optimistic update)
- ✅ En SQL : `SELECT state FROM annonce WHERE id='...' LIMIT 1;` retourne PUBLISHED

### Commande CURL
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"action":"validate"}' \
  http://localhost:8000/api/admin/annonce/UUID/decide
```

---

## TEST 5 : Refus d'Annonce (Rejection Path)

### Objectif
Vérifier que le refus change l'état à REJECTED.

### Étapes
1. Modérateur (jdupont) sur ModerationDashboard
2. Cliquer sur bouton "❌ Refuser" pour "Annonce à modérer #2"
3. Vérifier que la ligne disparaît
4. Vérifier en DB : state = REJECTED

### Critères d'Acceptation
- ✅ Body correct : `{ "action": "reject" }`
- ✅ Réponse : message "Annonce refusée avec succès"
- ✅ État DB changé à REJECTED

### Commande CURL
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"action":"reject"}' \
  http://localhost:8000/api/admin/annonce/UUID/decide
```

---

## TEST 6 : Sécurité - Modérateur Tente de Valider Hors de Son Campus

### Objectif
Vérifier que un modérateur local ne peut pas valider une annonce d'un autre campus.

### Étapes
1. Modérateur (jdupont - CALAIS)
2. Essayer de valider l'annonce "test" (SAINT_OMER) via API :
   ```bash
   curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"action":"validate"}' \
     http://localhost:8000/api/admin/annonce/{ID_SAINT_OMER}/decide
   ```

### Critères d'Acceptation
- ✅ Réponse HTTP 403 Forbidden
- ✅ Message d'erreur : "Vous ne pouvez pas modérer cette annonce"
- ✅ State en DB remain PENDING_REVIEW (non modifié)

---

## TEST 7 : Sécurité - Utilisateur Non-Authentifié

### Objectif
Vérifier que l'accès est bloqué sans authentification.

### Étapes
1. Ouvrir une fenêtre privée (sans session)
2. Essayer d'accéder à :
   - `/mes-annonces` → Redirect to login
   - `/admin/dashboard` → Redirect to login
   - `/api/my/annonces` → 403 Unauthorized
   - `/api/admin/pending` → 403 Unauthorized

### Critères d'Acceptation
- ✅ Redirection vers login
- ✅ Messages d'erreur appropriés en API

---

## TEST 8 : Sécurité - Utilisateur Lambda Tente d'Accéder à Admin

### Objectif
Vérifier qu'un utilisateur sans ROLE_MODERATOR ne peut pas accéder au dashboard.

### Étapes
1. Se connecter : sleroy (ROLE_USER uniquement)
2. Essayer d'accéder à `/admin/dashboard`

### Critères d'Acceptation
- ✅ Erreur 403 Forbidden
- ✅ Message "Accès refusé"

---

## TEST 9 : Edge Cases - Aucune Annonce à Modérer

### Objectif
Vérifier que le message "Aucune annonce à modérer" s'affiche correctement.

### Étapes
1. Valider/Refuser TOUTES les annonces CALAIS (jdupont)
2. Récharger la page `/admin/dashboard`

### Critères d'Acceptation
- ✅ Message "✅ Aucune annonce à modérer" en vert
- ✅ Texte sympa : "Bravo ! Toutes les annonces en attente ont été traitées."
- ✅ Pas de tableau vide

---

## TEST 10 : Performance & Responsive

### Objectif
Vérifier que l'UI est responsive et performance.

### Étapes sur Mobile (DevTools)
1. Ouvrir `/mes-annonces`
2. Vérifier que les cartes sont stackées en colonne
3. Tester les badges et images sur petit écran

### Critères d'Acceptation
- ✅ Layout responsive (col-md-6, col-lg-4)
- ✅ Pas de scroll horizontal
- ✅ Touches/boutons clickables sur mobile
- ✅ Chargement < 2 secondes

---

## SQL COMMANDS FOR VERIFICATION

### Vérifier les annonces créées
```sql
SELECT id, title, state, campus, (SELECT cas_uid FROM "user" WHERE id = owner_id) as owner
FROM annonce
ORDER BY created_at DESC;
```

### Vérifier les changements d'état
```sql
SELECT id, title, state, updated_at
FROM annonce
WHERE state IN ('PUBLISHED', 'REJECTED')
ORDER BY created_at DESC;
```

### Vérifier les rôles des utilisateurs
```sql
SELECT cas_uid, roles, moderated_campus
FROM "user"
WHERE roles ? 'ROLE_MODERATOR' OR roles ? 'ROLE_ADMIN'
ORDER BY email;
```

---

## ✅ CHECKLIST DE PASSAGE

- [ ] TEST 1 : MyAnnonces affiche les 5 annonces correctes
- [ ] TEST 2 : Modérateur CALAIS voit 3 annonces uniquement
- [ ] TEST 3 : Admin Global voit 4 annonces tous campus
- [ ] TEST 4 : Validation change state à PUBLISHED
- [ ] TEST 5 : Refus change state à REJECTED
- [ ] TEST 6 : Modérateur bloqué hors campus (403)
- [ ] TEST 7 : Non-authentifié bloqué (redirect/403)
- [ ] TEST 8 : Utilisateur lambda bloqué (403)
- [ ] TEST 9 : Message "Aucune annonce" correct
- [ ] TEST 10 : Responsive & Performance OK

---

## 🚀 STATUS

**Build** : ✅ Réussi (npm run build)
**Routes** : ✅ Toutes enregistrées
**API** : ✅ Prête pour test
**React** : ✅ Compilé sans erreurs
**Sécurité** : ✅ @IsGranted en place

**PRÊT POUR TESTING** ✅

