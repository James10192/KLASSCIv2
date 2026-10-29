# ESBTP-yAKRO - Système de Gestion d'École

Ce projet est un système de gestion scolaire pour l'École Supérieure du Bâtiment et des Travaux Publics (ESBTP) de Yakro.

## Prérequis

-   PHP 7.4 ou supérieur
-   Composer
-   MySQL 5.7 ou supérieur
-   Node.js et npm
-   Serveur web (Apache, Nginx, etc.)

## Installation

### Méthode automatique (recommandée)

#### Sur Linux/MacOS

```bash
# Cloner le dépôt
git clone https://github.com/votre-utilisateur/ESBTP-yAKROv2.git
cd ESBTP-yAKROv2

# Rendre le script d'installation exécutable
chmod +x install.sh

# Exécuter le script d'installation
./install.sh
```

#### Sur Windows

```bash
# Cloner le dépôt
git clone https://github.com/votre-utilisateur/ESBTP-yAKROv2.git
cd ESBTP-yAKROv2

# Exécuter le script d'installation
install.bat
```

### Installation manuelle

1. Cloner le dépôt

    ```bash
    git clone https://github.com/votre-utilisateur/ESBTP-yAKROv2.git
    cd ESBTP-yAKROv2
    ```

2. Installer les dépendances Composer

    ```bash
    composer install
    ```

3. Copier le fichier d'environnement

    ```bash
    cp .env.example .env
    ```

4. Générer la clé d'application

    ```bash
    php artisan key:generate
    ```

5. Configurer la base de données dans le fichier `.env`

    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nom_de_votre_base_de_donnees
    DB_USERNAME=utilisateur_de_votre_base_de_donnees
    DB_PASSWORD=mot_de_passe_de_votre_base_de_donnees
    ```

6. Exécuter les migrations et les seeders

    ```bash
    php artisan migrate --seed
    ```

7. Installer les dépendances npm

    ```bash
    npm install && npm run dev
    ```

8. Effacer le cache
    ```bash
    php artisan optimize:clear
    ```

## Déploiement

Pour déployer une nouvelle version de l'application :

1. Se connecter au serveur de production

    ```bash
    ssh utilisateur@votre-serveur
    ```

2. Accéder au répertoire du projet

    ```bash
    cd /chemin/vers/ESBTP-yAKROv2
    ```

3. Mettre à jour le code source

    ```bash
    git pull origin main
    ```

4. Installer les dépendances et optimiser

    ```bash
    composer install --optimize-autoloader --no-dev
    npm install && npm run production
    ```

5. Mettre à jour la base de données

    ```bash
    php artisan migrate --force
    ```

6. Effacer le cache
    ```bash
    php artisan optimize
    ```

## Structure des migrations et seeders

Le système utilise plusieurs seeders pour initialiser les données :

-   `ESBTPRoleSeeder` : Crée les rôles et permissions de base
-   `ESBTPNiveauEtudeSeeder` : Initialise les niveaux d'études
-   `ESBTPFiliereSeeder` : Initialise les filières disponibles
-   `ESBTPAnneeUniversitaireSeeder` : Crée les années universitaires
-   `ESBTPMatiereSeeder` : Initialise les matières enseignées

## Recommandations

-   **Nomenclature cohérente** : Maintenir une cohérence entre les noms de fichiers et les noms de tables pour éviter les confusions.
-   **Organisation des migrations** : Planifier l'ordre des migrations en tenant compte des dépendances entre les tables.
-   **Documentation** : Documenter les dépendances entre les tables pour faciliter la maintenance.

## Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

## Contact

Pour plus d'informations, veuillez contacter l'administrateur du système.
