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
# dans un volume à lui, et .env n'est qu'un lien vers lui.
# Pas dans storage/app : ce dossier part en entier dans l'archive remise à
# l'école (tenant:exporter-etablissement), mot de passe de base compris.
# Les variables posées dans Dokploy gardent la priorité : Laravel ne remplace
# jamais une variable d'environnement déjà définie par une ligne de .env.
DOSSIER_INSTANCE=/var/www/html/storage/instance
ENV_PERSISTANT="$DOSSIER_INSTANCE/instance.env"
mkdir -p "$DOSSIER_INSTANCE"
if ! mountpoint -q "$DOSSIER_INSTANCE"; then
    echo "ATTENTION : aucun volume sur $DOSSIER_INSTANCE. La fin de l'installation sera perdue au prochain redéploiement." >&2
fi
if [ ! -f "$ENV_PERSISTANT" ]; then
    printf '# Écrit par l assistant d installation. Les variables de Dokploy priment.\n' > "$ENV_PERSISTANT"
fi
chown www-data:www-data "$DOSSIER_INSTANCE" "$ENV_PERSISTANT"
chmod 700 "$DOSSIER_INSTANCE"
chmod 600 "$ENV_PERSISTANT"
ln -sfn "$ENV_PERSISTANT" /var/www/html/.env

# Un fichier de journal créé par root (planificateur lancé à la main, version
# précédente de l'image) bloquerait toute écriture de PHP-FPM jusqu'à minuit.
chown -R www-data:www-data storage/logs

su -s /bin/sh www-data -c "php artisan config:cache"
su -s /bin/sh www-data -c "php artisan view:cache"

exec "$@"
