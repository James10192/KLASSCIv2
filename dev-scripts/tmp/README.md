# Instructions pour appliquer les corrections ESBTP-yAKRO

Ce document détaille les étapes pour appliquer les corrections à l'application ESBTP-yAKRO afin de la rendre entièrement fonctionnelle selon les spécifications.

## 1. Correction du service d'inscription

Le service d'inscription a été amélioré pour générer automatiquement les matricules des étudiants et créer des comptes utilisateurs.

```bash
# Remplacer le fichier service d'inscription
cp tmp/services/ESBTPInscriptionService.php app/Services/ESBTPInscriptionService.php
```

## 2. Correction du contrôleur SecretaireAdmin

Le contrôleur a été mis à jour pour créer correctement les comptes secrétaires avec les bonnes permissions.

```bash
# Remplacer le contrôleur SecretaireAdmin
cp tmp/controllers/SecretaireAdminController.php app/Http/Controllers/ESBTP/SecretaireAdminController.php
```

## 3. Suppression du SuperAdminSeeder

Le SuperAdminSeeder doit être désactivé car le superAdmin est créé lors de l'installation.

```bash
# Commenter le seeder dans la liste des seeders
# Modifier le fichier database/seeders/DatabaseSeeder.php
# et commenter la ligne qui appelle SuperAdminSeeder
```

## 4. Vérifier les permissions

Assurez-vous que les rôles et permissions sont correctement définis dans l'application :

-   **superAdmin** : accès complet à toutes les fonctionnalités
-   **secretaire** : accès limité selon les spécifications
-   **etudiant** : accès uniquement à son profil et ses données

## 5. Test de l'application

Après avoir appliqué ces corrections, exécutez les tests suivants :

1. Réinitialiser l'application avec `php artisan migrate:fresh`
2. Exécuter les seeders avec `php artisan db:seed`
3. Vérifier le processus d'installation et la création du compte superAdmin
4. Tester la création d'un compte secrétaire
5. Tester l'inscription d'un étudiant et la génération automatique de son identifiant
6. Vérifier que chaque utilisateur a accès à son tableau de bord spécifique

## Points importants

-   Les matricules des étudiants suivent désormais le format : `[CODE_FILIERE][CODE_NIVEAU][ANNÉE][NUMÉRO_SÉQUENTIEL]`
-   Les noms d'utilisateurs des étudiants sont générés automatiquement à partir de leurs noms et prénoms
-   Les mots de passe des étudiants sont générés aléatoirement et affichés à l'inscription
