# Guide Administrateur ESBTP

## Fonctionnalités implémentées

Ce document décrit les fonctionnalités ajoutées à l'application ESBTP pour les super administrateurs.

### 1. Gestion des enseignants

Un nouveau module de gestion des enseignants a été implémenté, accessible via le menu latéral dans la section **Administration**.

#### Fonctionnalités:
- **Liste des enseignants**: Vue d'ensemble de tous les enseignants avec options de recherche et filtrage
- **Ajout d'enseignants**: Formulaire complet pour créer un nouvel enseignant avec toutes les informations nécessaires
- **Modification des enseignants**: Interface pour mettre à jour les informations des enseignants existants
- **Consultation des détails**: Vue détaillée des informations d'un enseignant spécifique
- **Suppression d'enseignants**: Possibilité de supprimer un enseignant avec confirmation

#### Comment accéder:
1. Connectez-vous en tant que super administrateur
2. Dans le menu latéral, sous la section "Administration", cliquez sur "Gestion des Enseignants"

### 2. Gestion de la comptabilité

Le module de comptabilité a été intégré au tableau de bord du super administrateur, accessible via le menu latéral dans la section **Comptabilité**.

#### Fonctionnalités:
- **Tableau de bord financier**: Vue d'ensemble des finances avec graphiques et statistiques
- **Gestion des paiements**: Suivi des paiements des étudiants
- **Gestion des dépenses**: Suivi des dépenses de l'institution
- **Rapports financiers**: Génération de rapports détaillés sur la situation financière

#### Comment accéder:
1. Connectez-vous en tant que super administrateur
2. Dans le menu latéral, sous la section "Comptabilité", accédez aux différentes options:
   - Tableau de bord financier
   - Paiements
   - Dépenses
   - Rapports financiers

## Identifiants de connexion

Pour accéder au tableau de bord du super administrateur, utilisez les identifiants suivants:

- **URL**: http://localhost:8000/login
- **Email**: ruben@gmail.com
- **Mot de passe**: admin123

## Gestion des droits d'accès

Le super administrateur dispose de tous les droits d'accès nécessaires pour:
- Gérer les enseignants
- Gérer la comptabilité 
- Gérer les filières
- Gérer les classes
- et toutes les autres fonctionnalités existantes

## Dépannage

Si vous rencontrez des problèmes d'accès, vous pouvez:

1. **Réinitialiser le mot de passe du super administrateur**:
   ```
   cd /opt/lampp/htdocs/ESBTP-yAKROv2
   php artisan tinker --execute="require('reset_superadmin_password.php');"
   ```

2. **Vérifier l'accès aux fonctionnalités**:
   ```
   cd /opt/lampp/htdocs/ESBTP-yAKROv2
   php artisan tinker --execute="require('check_superadmin_access.php');"
   ```

3. **Tester l'authentification**:
   ```
   cd /opt/lampp/htdocs/ESBTP-yAKROv2
   php artisan tinker --execute="require('test_login.php');"
   ```

## Aide supplémentaire

Si vous avez des questions ou besoin d'assistance, veuillez contacter l'équipe de support technique. 