#!/bin/sh
# Préparation au démarrage : config et vues compilées depuis l'environnement de l'école.
# Les migrations ne sont PAS lancées ici : elles se jouent à la main, après vérification.
set -e
cd /var/www/html

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
# Le lien doit appartenir à www-data. /var/www/html est en 1777 et le noyau de
# l'hôte refuse de suivre, dans un tel dossier, un lien dont le propriétaire
# n'est ni le lecteur ni le propriétaire du dossier (fs.protected_symlinks=1).
# Créé par root, il est invisible pour PHP-FPM et pour le config:cache plus bas :
# APP_INSTALLED n'est jamais lu, et RouteServiceProvider renvoie toute page
# authentifiée vers /install, qui la renvoie vers /dashboard, sans fin.
chown -h www-data:www-data /var/www/html/.env

# Clé de chiffrement. Trois cas :
#  - APP_KEY posée directement dans l'environnement (service Dokploy de type
#    Application) : elle prime, rien à faire ;
#  - APP_KEY renseignée dans l'onglet Environment d'un service Compose (école
#    reprise de cPanel) : elle arrive en ECOLE_APP_KEY et devient la clé ;
#  - rien : tirée UNE fois et gardée dans le volume.
# Elle n'est JAMAIS passée vide au conteneur : une commande tapée dans le
# terminal Dokploy hériterait du vide, et un `config:cache` le figerait.
# Le verrou évite que l'application et le planificateur, démarrés ensemble,
# en tirent chacun une différente. Sans volume, refus : une clé neuve à
# chaque déploiement rendrait sessions et données chiffrées illisibles.
if [ -z "$APP_KEY" ]; then
    if ! mountpoint -q "$DOSSIER_INSTANCE" && [ -z "$ECOLE_APP_KEY" ]; then
        echo "APP_KEY absente et aucun volume sur $DOSSIER_INSTANCE : le conteneur refuse de démarrer." >&2
        echo "Créez le service en type Compose (deploy/docker/docker-compose.yml), ou posez APP_KEY, puis redéployez." >&2
        exit 1
    fi
    CLE="$DOSSIER_INSTANCE/app.key"
    export CLE
    flock "$DOSSIER_INSTANCE/.cle.lock" sh -c '
        if [ -n "$ECOLE_APP_KEY" ]; then
            printf %s "$ECOLE_APP_KEY" > "$CLE"
        elif [ ! -s "$CLE" ]; then
            php -r "echo \"base64:\".base64_encode(random_bytes(32));" > "$CLE"
        fi
    '
    chown www-data:www-data "$CLE"
    chmod 600 "$CLE"
    ECOLE_APP_KEY=$(cat "$CLE")
    export ECOLE_APP_KEY
fi

# Variables facultatives de Dokploy (préfixe ECOLE_) recopiées dans .env, qui
# fait foi pour toutes les commandes, terminal compris ; vérifie le fuseau.
php bin/deploy/ecrire-env-instance.php "$ENV_PERSISTANT"
chown www-data:www-data "$ENV_PERSISTANT"
chmod 600 "$ENV_PERSISTANT"

# Un fichier de journal créé par root (planificateur lancé à la main, version
# précédente de l'image) bloquerait toute écriture de PHP-FPM jusqu'à minuit.
chown -R www-data:www-data storage/logs

# public/storage vit dans le conteneur, pas dans un volume : sans ce lien
# recréé à chaque démarrage, logos, photos et pièces jointes ne s'affichent plus
# après un redéploiement.
su -s /bin/sh www-data -c "php artisan storage:link" >/dev/null 2>&1 || true

su -s /bin/sh www-data -c "php artisan config:cache"
su -s /bin/sh www-data -c "php artisan view:cache"

exec "$@"
