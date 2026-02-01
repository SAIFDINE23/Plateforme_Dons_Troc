#!/bin/bash
# Commandes utiles pour le projet Plateforme de Dons et Troc

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  Plateforme de Dons et Troc - Commandes Utiles                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

function show_menu() {
    echo "Que voulez-vous faire?"
    echo ""
    echo "1)  Démarrer le serveur de développement"
    echo "2)  Compiler les assets (une fois)"
    echo "3)  Compiler les assets en mode watch"
    echo "4)  Vider le cache Symfony"
    echo "5)  Créer une nouvelle entité"
    echo "6)  Créer un nouveau contrôleur"
    echo "7)  Créer une migration"
    echo "8)  Exécuter les migrations"
    echo "9)  Afficher les routes"
    echo "10) Installer les dépendances"
    echo "11) Créer la base de données PostgreSQL"
    echo "12) Afficher l'état du serveur"
    echo "0)  Quitter"
    echo ""
}

while true; do
    show_menu
    read -p "Votre choix: " choice
    echo ""
    
    case $choice in
        1)
            echo "🚀 Démarrage du serveur sur http://localhost:8000"
            php -S localhost:8000 -t public
            ;;
        2)
            echo "📦 Compilation des assets..."
            npm run dev
            ;;
        3)
            echo "👀 Compilation en mode watch (Ctrl+C pour arrêter)..."
            npm run watch
            ;;
        4)
            echo "🧹 Nettoyage du cache..."
            php bin/console cache:clear
            ;;
        5)
            echo "📝 Création d'une nouvelle entité"
            read -p "Nom de l'entité: " entity_name
            php bin/console make:entity "$entity_name"
            ;;
        6)
            echo "🎮 Création d'un nouveau contrôleur"
            read -p "Nom du contrôleur: " controller_name
            php bin/console make:controller "$controller_name"
            ;;
        7)
            echo "📋 Création d'une migration..."
            php bin/console make:migration
            ;;
        8)
            echo "▶️  Exécution des migrations..."
            php bin/console doctrine:migrations:migrate
            ;;
        9)
            echo "🗺️  Routes disponibles:"
            php bin/console debug:router
            ;;
        10)
            echo "📦 Installation des dépendances..."
            echo "PHP..."
            composer install
            echo "JavaScript..."
            npm install
            ;;
        11)
            echo "🗄️  Création de la base de données PostgreSQL"
            echo "Exécution des commandes SQL..."
            sudo -u postgres psql << EOF
CREATE DATABASE plateforme_dons_troc;
CREATE USER plateforme_user WITH PASSWORD 'plateforme_password';
GRANT ALL PRIVILEGES ON DATABASE plateforme_dons_troc TO plateforme_user;
\q
EOF
            echo "✅ Base de données créée!"
            ;;
        12)
            echo "📊 État du serveur:"
            if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
                echo "✅ Serveur en cours d'exécution sur le port 8000"
                echo "🌐 http://localhost:8000"
            else
                echo "❌ Aucun serveur détecté sur le port 8000"
            fi
            ;;
        0)
            echo "👋 Au revoir!"
            exit 0
            ;;
        *)
            echo "❌ Choix invalide"
            ;;
    esac
    
    echo ""
    read -p "Appuyez sur Entrée pour continuer..."
    clear
done
