# Corrections nécessaires pour ESBTP-yAKRO

## 1. Problèmes identifiés

1. **SuperAdminSeeder désactivé**: Le superAdmin n'est pas créé automatiquement lors de l'installation, cela nécessite une intervention manuelle.

2. **Inconsistance des contrôleurs**: Deux structures parallèles (avec préfixe "ESBTP" et dans sous-dossier "ESBTP/") créent de la confusion.

3. **Dashboards spécifiques aux rôles**: Les dashboards doivent être adaptés spécifiquement aux permissions de chaque rôle.

4. **Problèmes d'installation**: Le processus d'installation ne configure pas correctement tous les éléments nécessaires.

5. **Création automatique d'utilisateurs**: Les identifiants étudiants ne sont pas générés automatiquement.

## 2. Corrections à faire

1. **Modification du SecretaireAdminController**:

    - S'assurer que le contrôleur permet la création de comptes secrétaire avec les bonnes permissions
    - Simplifier le processus de création de compte

2. **Génération automatique d'identifiants étudiants**:

    - Modifier le `ESBTPInscriptionService` pour générer automatiquement un matricule unique
    - Format à utiliser: `[CODE_FILIERE][CODE_NIVEAU][ANNÉE][NUMÉRO_SÉQUENTIEL]`
    - Exemple: `GC1BTS23001` pour le premier étudiant en Génie Civil 1ère année BTS inscrit en 2023

3. **Correction des routes**:

    - Standardiser les routes pour éviter les doublons
    - Assurer que les routes respectent les permissions définies

4. **Correction des permissions de secrétaire**:

    - S'assurer que le rôle "secretaire" a bien les permissions requises:
        - filières (view)
        - formations (view)
        - niveaux d'études (view)
        - classes (view)
        - students (create, view)
        - exams (view)
        - matières (view)
        - grades (create, view)
        - bulletins (generate, view)
        - timetables (create, view)
        - messages (send)
        - attendances (create, view)

5. **Désactivation du SuperAdminSeeder**:
    - Le SuperAdmin doit être créé lors de l'installation uniquement, pas via les seeders.

## 3. Tests à effectuer

1. **Test complet de réinitialisation**: Exécuter le script `reset_app.sh` pour vérifier que l'application se réinitialise correctement.

2. **Test d'installation**: Vérifier que le processus d'installation crée bien le compte superAdmin.

3. **Test de création de secrétaire**: Vérifier que le superAdmin peut créer un compte secrétaire fonctionnel.

4. **Test d'inscription étudiant**: Vérifier que l'inscription d'un étudiant génère bien un identifiant unique automatiquement.

5. **Test des dashboards**: Vérifier que chaque rôle a son dashboard adapté.

6. **Test des permissions**: Vérifier que chaque rôle n'a accès qu'aux fonctionnalités autorisées.
