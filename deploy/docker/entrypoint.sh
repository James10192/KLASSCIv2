#!/bin/sh
# Préparation au démarrage : config et vues compilées depuis l'environnement de l'école.
# Les migrations ne sont PAS lancées ici : elles se jouent à la main, après vérification.
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY absente : le conteneur refuse de démarrer." >&2
    exit 1
fi

# L'assistant d'installation écrit dans .env (configuration saisie, puis
# APP_INSTALLED=true à la fin). Dans le conteneur, ce fichier disparaîtrait au
# redéploiement suivant et l'école retomberait sur l'assistant : il vit donc
# dans le volume de storage/app, et .env n'est qu'un lien vers lui.
# Les variables posées dans Dokploy gardent la priorité : Laravel ne remplace
# jamais une variable d'environnement déjà définie par une ligne de .env.
ENV_PERSISTANT=/var/www/html/storage/app/instance.env
if [ ! -f "$ENV_PERSISTANT" ]; then
    printf '# Écrit par l assistant d installation. Les variables de Dokploy priment.\n' > "$ENV_PERSISTANT"
fi
chown www-data:www-data "$ENV_PERSISTANT"
chmod 600 "$ENV_PERSISTANT"
ln -sfn "$ENV_PERSISTANT" /var/www/html/.env

su -s /bin/sh www-data -c "php artisan config:cache"
su -s /bin/sh www-data -c "php artisan view:cache"

exec "$@"
