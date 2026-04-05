#!/bin/sh
set -e

echo "🚀 Démarrage de l'application ULC'OCCAZ..."

# Adapter le port Nginx si Render fournit $PORT
if [ -n "$PORT" ]; then
    echo "📌 Port détecté : $PORT"
    sed -i "s/listen 10000/listen $PORT/" /etc/nginx/http.d/default.conf
fi

# S'assurer que les répertoires existent et sont accessibles
mkdir -p var/cache var/log var/share public/uploads/annonces
chown -R www-data:www-data var/ public/uploads/

# Vider le cache et le réchauffer
echo "🧹 Warmup du cache..."
php bin/console cache:clear --env=prod --no-debug 2>/dev/null || true
php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true

# Exécuter les migrations automatiquement
echo "📦 Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 || echo "⚠️  Migrations échouées (la base est peut-être déjà à jour)"

echo "✅ Application prête !"

# Démarrer supervisord (PHP-FPM + Nginx)
exec /usr/bin/supervisord -c /etc/supervisord.conf
