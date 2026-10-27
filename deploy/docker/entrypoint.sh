#!/bin/sh
# Préparation au démarrage : config et vues compilées depuis l'environnement de l'école.
# Les migrations ne sont PAS lancées ici : elles se jouent à la main, après vérification.
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY absente : le conteneur refuse de démarrer." >&2
    exit 1
fi

su -s /bin/sh www-data -c "php artisan config:cache"
su -s /bin/sh www-data -c "php artisan view:cache"

exec "$@"
