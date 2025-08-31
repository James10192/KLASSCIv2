# Guide de Tests - Système de Frais ESBTP 2025

## Vue d'ensemble
Ce guide détaille les tests à effectuer pour valider le nouveau système de frais avec souscriptions optionnelles implémenté en juillet 2025.

## Prérequis
- Base de données avec les tables : `esbtp_frais_categories`, `esbtp_frais_rules`, `esbtp_frais_subscriptions`
- Seeder exécuté : `ESBTPFraisCategorySeeder`
- Utilisateur avec rôle `superAdmin` ou `secretaire`
- Inscriptions d'étudiants existantes

## Tests Fonctionnels

### 1. Test du Seeder et Données de Base

**Objectif** : Vérifier que les frais obligatoires sont créés automatiquement

**Étapes** :
1. Connexion en tant que superAdmin (username: `superadmin`, password: `password123`)
2. Aller à : Menu > Comptabilité > Gestion des Frais
3. Vérifier la présence des 6 catégories de frais :
   - **Obligatoires** : Frais d'inscription, Frais de scolarité
   - **Optionnels** : Frais de cantine, Frais de transport, Frais d'assurance, Frais d'activités

**Résultat attendu** :
- 2 frais obligatoires avec badge rouge "Obligatoire"
- 4 frais optionnels avec badge bleu "Optionnel"
- Statistiques correctes affichées

### 2. Test de Configuration des Frais

**Objectif** : Configurer les montants par filière/niveau

**Étapes** :
1. Aller à : Menu > Comptabilité > Configuration Frais
2. Sélectionner une filière et un niveau
3. Cliquer "Filtrer"
4. Modifier les montants des frais obligatoires
5. Activer l'échéancier pour au moins un frais
6. Cliquer "Enregistrer la configuration"

**Résultat attendu** :
- Interface de configuration s'affiche correctement
- Les montants sont sauvegardés
- Message de succès affiché

### 3. Test de la Page Inscription (Frais Obligatoires)

**Objectif** : Vérifier l'affichage automatique des frais obligatoires

**Étapes** :
1. Aller à une page d'inscription d'étudiant
2. Section "Situation Financière Détaillée"
3. Vérifier le tableau des frais

**Résultat attendu** :
- Seuls les frais obligatoires configurés apparaissent
- Pas de frais optionnels non souscrits
- Statuts corrects (payé/partiel/non payé)
- Boutons d'action appropriés

### 4. Test de Souscription aux Frais Optionnels

**Objectif** : Souscrire à un frais optionnel

**Étapes** :
1. Sur la page inscription, section "Frais Optionnels Disponibles"
2. Cliquer "Souscrire à un frais"
3. Sélectionner un frais optionnel (ex: Cantine)
4. Modifier le montant si nécessaire
5. Ajouter des notes
6. Cliquer "Souscrire"

**Résultat attendu** :
- Modal s'ouvre correctement centré (pas trop haut)
- Montant pré-rempli avec la valeur par défaut
- Description du frais affichée
- Souscription réussie avec message de confirmation
- Frais apparaît maintenant dans le tableau principal

### 5. Test de Désabonnement

**Objectif** : Se désabonner d'un frais optionnel

**Étapes** :
1. Dans le tableau des frais, identifier un frais optionnel souscrit
2. Cliquer le bouton rouge "X" (désabonnement)
3. Confirmer la suppression

**Résultat attendu** :
- Confirmation demandée avec message explicatif
- Frais supprimé du tableau après confirmation
- Frais redevient disponible dans "Frais Optionnels Disponibles"
- Paiements existants conservés

### 6. Test du Modal de Paiement

**Objectif** : Effectuer un paiement sur un frais

**Étapes** :
1. Cliquer le bouton vert "carte de crédit" sur un frais avec solde
2. Sélectionner la catégorie de frais
3. Saisir le montant, mode de paiement, date
4. Valider le paiement

**Résultat attendu** :
- Modal s'ouvre avec catégories disponibles
- Montant pré-rempli selon la catégorie
- Paiement enregistré et tableau mis à jour

### 7. Test de Navigation

**Objectif** : Vérifier l'intégration dans le menu

**Étapes** :
1. Menu principal > Comptabilité
2. Vérifier présence de "Gestion des Frais" et "Configuration Frais"
3. Cliquer sur chaque lien
4. Vérifier que le menu reste ouvert et actif

**Résultat attendu** :
- Liens visibles pour superAdmin et secrétaire
- Navigation fluide
- Menu accordéon fonctionne correctement

## Tests d'Interface

### 8. Test de Responsivité

**Objectif** : Vérifier l'affichage mobile

**Étapes** :
1. Réduire la fenêtre ou utiliser les outils développeur
2. Tester les modals sur mobile
3. Vérifier le tableau en mode mobile

**Résultat attendu** :
- Modals centrés et accessibles
- Tableaux avec scroll horizontal
- Boutons accessibles

### 9. Test de Performance

**Objectif** : Vérifier les temps de chargement

**Étapes** :
1. Chronométrer le chargement de la page inscription
2. Tester avec plusieurs inscriptions
3. Vérifier les requêtes Ajax

**Résultat attendu** :
- Chargement < 3 secondes
- Pas d'erreurs JavaScript
- Requêtes optimisées

## Tests de Régression

### 10. Test de Compatibilité

**Objectif** : Vérifier que l'ancien système n'interfère pas

**Étapes** :
1. Vérifier absence d'erreurs 404 ou 500
2. Tester les anciens paiements
3. Vérifier l'historique des transactions

**Résultat attendu** :
- Aucune erreur liée aux anciennes routes
- Données historiques préservées
- Fonctionnalités existantes intactes

## Tests de Sécurité

### 11. Test des Permissions

**Objectif** : Vérifier les accès par rôle

**Étapes** :
1. Tester avec différents rôles (étudiant, parent, enseignant)
2. Vérifier l'accès aux pages de configuration
3. Tester les souscriptions par différents utilisateurs

**Résultat attendu** :
- Accès limité selon les rôles
- Étudiants ne peuvent pas configurer
- Parents ne voient que leurs enfants

### 12. Test des Validations

**Objectif** : Vérifier les contrôles de saisie

**Étapes** :
1. Tenter de souscrire avec montant négatif
2. Essayer de souscrire deux fois au même frais
3. Tester les champs obligatoires

**Résultat attendu** :
- Montants négatifs rejetés
- Doublons de souscription empêchés
- Messages d'erreur appropriés

## Résolution de Problèmes

### Problème : Modal trop haut
**Solution** : Styles CSS ajoutés avec `modal-dialog-centered` et z-index

### Problème : Frais obligatoires non affichés
**Solution** : Vérifier la configuration par filière/niveau

### Problème : Routes non trouvées
**Solution** : Vérifier routes/web.php et cache de routes

## Checklist de Validation

- [ ] Seeder exécuté et frais créés
- [ ] Configuration par filière/niveau fonctionnelle
- [ ] Frais obligatoires affichés automatiquement
- [ ] Souscription aux frais optionnels possible
- [ ] Désabonnement fonctionnel
- [ ] Modal de paiement opérationnel
- [ ] Navigation dans le menu correcte
- [ ] Interface responsive
- [ ] Performance acceptable
- [ ] Permissions respectées
- [ ] Validations en place
- [ ] Aucune régression détectée

## Notes Importantes

1. **Backup recommandé** avant tests en production
2. **Cache Laravel** à vider après modifications de routes
3. **Logs d'erreur** à surveiller pendant les tests
4. **Feedback utilisateur** à collecter pour améliorations

---

*Guide créé le 16 juillet 2025 pour la validation du système de frais ESBTP*