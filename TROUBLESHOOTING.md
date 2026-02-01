# 🐛 Problèmes Rencontrés et Solutions

Ce document liste tous les problèmes rencontrés pendant l'installation et comment ils ont été résolus.

## 1. ❌ Module @symfony/stimulus-bundle introuvable

### Problème
```
Module build failed: Module not found:
"@symfony/stimulus-bundle" could not be found
```

### Cause
Le package `@symfony/stimulus-bundle` n'existe pas dans npm. Le bon package est `@symfony/stimulus-bridge`.

### Solution
```bash
npm install @symfony/stimulus-bridge --force
```

Modification de `assets/stimulus_bootstrap.js`:
```javascript
// Avant
import { startStimulusApp } from '@symfony/stimulus-bundle';

// Après
import { startStimulusApp } from '@symfony/stimulus-bridge';
```

---

## 2. ❌ controllers.json introuvable

### Problème
```
Error: Your controllers.json file was not found. 
Be sure to add a Webpack alias from "@symfony/stimulus-bridge/controllers.json"
```

### Cause
Webpack Encore ne savait pas où trouver le fichier `controllers.json`.

### Solution
Ajout d'un alias dans `webpack.config.js`:
```javascript
.addAliases({
    '@symfony/stimulus-bridge/controllers.json': require('path').resolve(__dirname, 'assets/controllers.json')
})
```

---

## 3. ❌ Packages React manquants

### Problème
```
Module build failed: Module not found:
"react" could not be found
```

### Cause
Les packages React n'étaient pas installés après `composer require symfony/ux-react`.

### Solution
```bash
npm install react@18 react-dom@18 @symfony/ux-react --force
```

**Note**: Utilisation de React 18 (pas 19) pour la compatibilité avec Symfony UX React 2.32.

---

## 4. ❌ Package @symfony/ux-turbo manquant

### Problème
```
Error: The file "@symfony/ux-turbo/package.json" could not be found.
```

### Cause
Le fichier `controllers.json` référençait `@symfony/ux-turbo` mais le package npm n'était pas installé.

### Solution
```bash
npm install @symfony/ux-turbo --force
```

---

## 5. ❌ Version de React incompatible

### Problème
Installation initiale de React 19, mais Symfony UX React demande React 18.

### Cause
`npm install react` installe la dernière version (19.x) par défaut.

### Solution
```bash
npm install react@18 react-dom@18 --force
```

---

## 6. ✅ Configuration Stimulus Bridge

### Problème Initial
Le fichier généré automatiquement utilisait `@symfony/stimulus-bundle` qui n'existe pas.

### Solution Finale
`assets/stimulus_bootstrap.js`:
```javascript
import { startStimulusApp } from '@symfony/stimulus-bridge';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
```

---

## 📝 Packages Finaux Installés (npm)

```json
{
    "dependencies": {
        "@hotwired/stimulus": "^3.2.2",
        "@symfony/stimulus-bridge": "^3.3.1",
        "@symfony/ux-react": "^2.32.0",
        "@symfony/ux-turbo": "^2.32.0",
        "react": "^18.3.1",
        "react-dom": "^18.3.1"
    },
    "devDependencies": {
        "@babel/core": "^7.17.0",
        "@babel/preset-env": "^7.16.0",
        "@babel/preset-react": "^7.28.5",
        "@symfony/webpack-encore": "^5.1.0",
        "core-js": "^3.38.0",
        "regenerator-runtime": "^0.13.9",
        "webpack": "^5.74.0",
        "webpack-cli": "^5.1.0"
    }
}
```

---

## 🎯 Commandes d'Installation Correctes (Ordre)

1. **Créer le projet Symfony**:
   ```bash
   composer create-project symfony/skeleton plateforme_dons_troc
   cd plateforme_dons_troc
   ```

2. **Installer Symfony webapp**:
   ```bash
   composer require webapp
   ```

3. **Installer Webpack Encore**:
   ```bash
   composer require symfony/webpack-encore-bundle
   npm install
   ```

4. **Installer Symfony UX React**:
   ```bash
   composer require symfony/ux-react
   ```

5. **Installer les packages npm nécessaires**:
   ```bash
   npm install -D @babel/preset-react --force
   npm install react@18 react-dom@18 @symfony/ux-react --force
   npm install @symfony/stimulus-bridge --force
   npm install @symfony/ux-turbo --force
   npm install @hotwired/stimulus --force
   ```

6. **Compiler les assets**:
   ```bash
   npm run dev
   ```

---

## 💡 Conseils pour Éviter ces Problèmes

1. **Toujours utiliser `--force` avec npm** lors de l'installation des packages Symfony UX pour éviter les conflits de dépendances.

2. **Vérifier la version de React**: S'assurer d'installer React 18, pas 19.

3. **Configuration Webpack**: Ne pas oublier l'alias pour `controllers.json`.

4. **Ordre d'installation**: Suivre l'ordre ci-dessus pour éviter les dépendances manquantes.

5. **Documentation**: Se référer à la documentation officielle de Symfony UX qui peut être plus à jour.

---

## ✅ Résultat Final

Après avoir résolu tous ces problèmes:
- ✅ Compilation réussie sans erreurs
- ✅ 7 fichiers générés dans `public/build/`
- ✅ Serveur de développement fonctionnel
- ✅ Composant React s'affiche correctement

**Temps total de résolution**: ~10 minutes
**Statut**: SUCCÈS
