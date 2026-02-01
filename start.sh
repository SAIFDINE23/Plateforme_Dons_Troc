#!/bin/bash

# Script de démarrage pour la Plateforme de Dons et Troc
# Usage: ./start.sh

echo "🚀 Démarrage de la Plateforme de Dons et Troc..."
echo ""

# Vérifier si le serveur PHP est déjà en cours
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    echo "⚠️  Le serveur PHP est déjà en cours sur le port 8000"
else
    echo "📦 Démarrage du serveur PHP sur http://localhost:8000"
    php -S localhost:8000 -t public &
    PHP_PID=$!
    echo "   PID: $PHP_PID"
fi

echo ""
echo "📦 Compilation des assets en mode watch..."
npm run watch &
NPM_PID=$!

echo ""
echo "✅ Serveurs démarrés!"
echo ""
echo "📍 Application: http://localhost:8000"
echo "📍 Page de test: http://localhost:8000/default"
echo ""
echo "💡 Pour arrêter les serveurs, appuyez sur Ctrl+C"
echo ""

# Attendre l'interruption
trap "echo '' && echo '🛑 Arrêt des serveurs...' && kill $PHP_PID $NPM_PID 2>/dev/null && echo '✅ Serveurs arrêtés' && exit 0" INT TERM

# Garder le script en cours d'exécution
wait
