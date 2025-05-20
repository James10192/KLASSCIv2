# Guide d'Installation - ESBTP Système de Suivi des Présences

## Prérequis

### Système

-   PHP >= 7.4
-   MySQL >= 5.7
-   Nginx ou Apache
-   Redis >= 6.0
-   Composer
-   Node.js >= 14.x
-   npm >= 6.x

### PHP Extensions

-   BCMath
-   Ctype
-   JSON
-   Mbstring
-   OpenSSL
-   PDO
-   Tokenizer
-   XML
-   Redis

## Installation

### 1. Configuration du Serveur

#### Nginx

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/esbtp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 2. Installation de l'Application

```bash
# Cloner le dépôt
git clone https://github.com/votre-repo/esbtp.git
cd esbtp

# Installer les dépendances PHP
composer install --no-dev

# Installer les dépendances Node.js
npm install
npm run production

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configuration de la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esbtp
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

# Configuration Redis dans .env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Migrations et seeds
php artisan migrate --seed

# Optimisations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 3. Configuration du Service Worker

```bash
# Publier les assets du service worker
php artisan vendor:publish --tag=pwa-assets

# Configurer le service worker dans .env
PWA_ENABLED=true
PWA_CACHE_VERSION=1
```

### 4. Configuration des Tâches Planifiées

Ajouter au crontab :

```bash
* * * * * cd /chemin/vers/esbtp && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Configuration des Workers

Créer un fichier de configuration Supervisord :

```ini
[program:esbtp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /chemin/vers/esbtp/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/esbtp-worker.log
```

## Vérification de l'Installation

### Liste de Contrôle

-   [ ] Application accessible via le navigateur
-   [ ] Connexion à la base de données fonctionnelle
-   [ ] Redis opérationnel
-   [ ] Service worker installé
-   [ ] Workers en cours d'exécution
-   [ ] Tâches planifiées configurées

### Tests

```bash
# Exécuter les tests de base
php artisan test

# Vérifier la connexion à la base de données
php artisan db:monitor

# Vérifier le statut des workers
php artisan queue:status
```

## Dépannage

### Problèmes Courants

1. **Erreur 500**

    - Vérifier les logs dans `storage/logs/laravel.log`
    - Vérifier les permissions des dossiers

2. **Erreur de Base de Données**

    - Vérifier les identifiants dans `.env`
    - Vérifier que MySQL est en cours d'exécution

3. **Problèmes de Cache**
    ```bash
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    ```

## Sécurité Post-Installation

### Liste de Contrôle Sécurité

-   [ ] Désactiver le mode debug en production
-   [ ] Configurer HTTPS
-   [ ] Mettre à jour les mots de passe par défaut
-   [ ] Configurer les sauvegardes
-   [ ] Mettre en place le monitoring

### Configuration HTTPS

```bash
# Installer Certbot
apt-get install certbot python3-certbot-nginx

# Obtenir un certificat
certbot --nginx -d votre-domaine.com
```

## Support

Pour toute assistance :

-   Documentation technique : [Lien](../../technical/README.md)
-   Email : support@esbtp.com
-   Issues GitHub : [Lien vers le repo]
