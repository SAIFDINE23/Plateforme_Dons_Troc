# 🚀 COMMANDES ESSENTIELLES - ULC'OCCAZ

## 📦 Installation

```bash
# Installer toutes les dépendances
npm install

# Installer les dépendances backend
composer install
```

---

## 🎨 Compilation Assets (Design)

```bash
# Development avec watch (auto-recompilation)
npm run dev

# Development une fois
npm run build

# Production (minifié, optimisé)
npm run build

# Watch mode (recompile à chaque changement)
npm run watch
```

---

## 🗄️ Base de Données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Appliquer les migrations
php bin/console doctrine:migrations:migrate -n

# Charger les fixtures (données de test)
php bin/console doctrine:fixtures:load -n
```

---

## 🧹 Nettoyage

```bash
# Nettoyer le cache Symfony
php bin/console cache:clear

# Nettoyer les assets compilés
rm -rf public/build

# Rebuild complet (clean + build)
npm run build  # Le script clean automatiquement
```

---

## 🔧 SCSS & Design

```bash
# Modifier les couleurs EILCO
# Éditer: assets/styles/app.scss (lignes 9-17)

# Recompiler après modification SCSS
npm run build

# Watch mode pour développement design
npm run watch
```

---

## 🎬 Serveur de Développement

```bash
# Démarrer le serveur Symfony
symfony server:start

# Ou avec PHP natif
php -S localhost:8000 -t public
```

---

## 📝 Logs & Debugging

```bash
# Voir les logs Symfony
tail -f var/log/dev.log

# Dernières 100 lignes
tail -n 100 var/log/dev.log

# Nettoyer les logs
> var/log/dev.log
```

---

## 🧪 Tests

```bash
# Lancer PHPUnit (si configuré)
php bin/phpunit

# Vérifier les erreurs Symfony
php bin/console about

# Vérifier les routes
php bin/console debug:router
```

---

## 🔍 Inspection Design

```bash
# Lister les assets compilés
ls -lh public/build/

# Taille du bundle CSS
du -h public/build/app.*.css

# Taille du bundle JS
du -h public/build/app.*.js

# Vérifier les warnings SCSS
npm run build 2>&1 | grep -i "warning"
```

---

## 📊 Statistiques Build

```bash
# Build actuel (après refonte design)
Total: 838 KiB
├── runtime.js : 2.56 KiB
├── 408.js     : 501 KiB
├── app.css    : 227 KiB  ← SCSS compilé + Bootstrap
└── app.js     : 107 KiB

# Commande pour voir les stats
npm run build 2>&1 | grep "Entrypoint"
```

---

## 🎨 Workflow Design Quotidien

```bash
# 1. Lancer le watch mode
npm run watch

# 2. Éditer SCSS
vim assets/styles/app.scss

# 3. Voir la recompilation automatique
# 4. Rafraîchir le navigateur (F5)

# 5. Éditer composant React
vim assets/react/controllers/AnnonceList.jsx

# 6. Watch recompile automatiquement
# 7. Hard refresh navigateur (Ctrl+F5)
```

---

## 🐛 Dépannage Fréquent

### Assets ne se chargent pas
```bash
# Nettoyer et rebuild
rm -rf public/build
npm run build
php bin/console cache:clear
```

### SCSS ne compile pas
```bash
# Vérifier sass-loader installé
npm list sass-loader

# Réinstaller si besoin
npm install sass-loader sass --save-dev
```

### Animations ne fonctionnent pas
```bash
# Vérifier framer-motion
npm list framer-motion

# Réinstaller si besoin
npm install framer-motion
```

### Icônes Lucide manquantes
```bash
# Vérifier lucide-react
npm list lucide-react

# Réinstaller si besoin
npm install lucide-react
```

---

## 📦 Dépendances Design

```bash
# Installer toutes les dépendances design
npm install lucide-react framer-motion clsx sass-loader sass --save-dev

# Vérifier les versions
npm list | grep -E "(lucide|framer|clsx|sass)"
```

---

## 🔄 Mise à Jour Design

```bash
# 1. Éditer les variables SCSS
vim assets/styles/app.scss

# 2. Modifier les couleurs (lignes 9-17)
$primary: #004E86;    // Bleu EILCO
$secondary: #009FE3;  // Cyan
$accent: #F07D00;     // Orange

# 3. Recompiler
npm run build

# 4. Vider le cache navigateur
# Ctrl+Shift+R (Chrome/Firefox)
```

---

## 📱 Test Responsive

```bash
# 1. Démarrer le serveur
symfony server:start

# 2. Ouvrir DevTools navigateur
# F12 (Chrome/Firefox)

# 3. Toggle Device Toolbar
# Ctrl+Shift+M (Chrome)
# Ctrl+Shift+M (Firefox)

# 4. Tester sur:
# - iPhone SE (375px)
# - iPad (768px)
# - Desktop (1920px)
```

---

## 🚀 Déploiement Production

```bash
# 1. Build production optimisé
npm run build

# 2. Vérifier la taille des bundles
ls -lh public/build/

# 3. Nettoyer le cache Symfony
php bin/console cache:clear --env=prod

# 4. Optimiser Composer autoload
composer dump-autoload --optimize --classmap-authoritative

# 5. Appliquer migrations
php bin/console doctrine:migrations:migrate --env=prod -n

# 6. Changer permissions fichiers
chmod -R 755 public/build/
```

---

## 🎯 Checklist Avant Push

```bash
# Build sans erreurs
npm run build
echo $?  # Doit afficher 0

# Cache vidé
php bin/console cache:clear

# Pas d'erreurs Symfony
php bin/console about

# Git status propre
git status

# Commit avec message clair
git add .
git commit -m "feat: Refonte design institutionnel EILCO avec SCSS + Framer Motion + Lucide Icons"
git push
```

---

## 📚 Documentation

```bash
# Lire la doc design
cat DESIGN_DOCUMENTATION.md

# Lire le guide de style
cat STYLE_GUIDE.md

# Lire le changelog
cat DESIGN_CHANGELOG.md

# Lire le README design
cat README_DESIGN.md
```

---

## 🔗 Liens Utiles

### Localhost
- Frontend: http://localhost:8000
- Symfony Profiler: http://localhost:8000/_profiler

### Documentation
- [DESIGN_DOCUMENTATION.md](./DESIGN_DOCUMENTATION.md) - Doc complète
- [STYLE_GUIDE.md](./STYLE_GUIDE.md) - Guide de style
- [README_DESIGN.md](./README_DESIGN.md) - Vue d'ensemble

### Ressources Externes
- Framer Motion: https://www.framer.com/motion/
- Lucide Icons: https://lucide.dev/
- Bootstrap 5: https://getbootstrap.com/docs/5.3/
- SCSS: https://sass-lang.com/documentation/

---

## 💡 Trucs & Astuces

### Changer rapidement les couleurs
```bash
# Éditer les variables en haut de app.scss
vim +9 assets/styles/app.scss

# Recompiler en temps réel
npm run watch
```

### Ajouter un composant React rapidement
```bash
# Copier AnnonceCard comme template
cp assets/react/components/AnnonceCard.jsx assets/react/components/NewComponent.jsx

# Éditer et adapter
vim assets/react/components/NewComponent.jsx
```

### Debug CSS en live
```bash
# Ouvrir DevTools (F12)
# Onglet Elements → Styles
# Modifier les styles directement
# Copier les changements dans app.scss
```

---

**Aide rapide à portée de main !** 🚀

*Développé pour l'EILCO - Université du Littoral Côte d'Opale* 🎓
