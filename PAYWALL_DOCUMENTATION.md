# Documentation Système Paywall - ESBTP Multi-Tenant

## Vue d'ensemble

Ce système de paywall permet de contrôler l'accès aux fonctionnalités de l'application en fonction des limites d'abonnement configurées pour chaque établissement. Il fonctionne dans l'architecture multi-tenant existante (une branche Git = un établissement = une base de données).

## Architecture

### Principe de fonctionnement
- **Multi-tenant** : Chaque école a sa propre branche Git et sa propre base de données
- **Configuration indépendante** : Chaque école configure ses propres limites et abonnement
- **Stockage local** : Les paramètres sont stockés dans `ESBTPSystemSetting` de chaque école
- **Vérifications temps réel** : Le middleware vérifie automatiquement les limites à chaque requête

### Composants

1. **ESBTPPaywallConfigController** : Gestion de la configuration
2. **PaywallMiddleware** : Vérification automatique des limites
3. **Routes** : `/esbtp/paywall-config`
4. **Interface** : Page de configuration moderne avec statistiques en temps réel

## Configuration École par École

### Workflow de configuration

#### Étape 1 : Sélectionner l'école
```bash
# Changer vers la branche de l'école Saint-Paul
git checkout saint-paul
```

#### Étape 2 : Accéder à la configuration
- URL : `saint-paul.monsite.com/esbtp/paywall-config`
- Menu : Paramètres → Paywall

#### Étape 3 : Configurer les limites
- **Statut** : Activer/Désactiver le paywall
- **Date d'expiration** : Date limite de l'abonnement
- **Limite utilisateurs** : Nombre max d'enseignants/coordinateurs/secrétaires
- **Limite étudiants** : Nombre max d'étudiants actifs
- **Informations plan** : Nom du plan et prix

#### Étape 4 : Sauvegarder
Les paramètres sont automatiquement sauvegardés dans la base de données de l'école.

### Paramètres stockés

Les paramètres suivants sont stockés dans `esbtp_system_settings` :

```php
// Activation du paywall
'paywall_active' => true/false

// Date d'expiration de l'abonnement
'subscription_end_date' => '2024-12-31'

// Limites
'paywall_max_users' => 20
'paywall_max_students' => 500

// Informations du plan
'paywall_plan_name' => 'Plan Standard'
'paywall_plan_price' => 25000

// Fonctionnalités (JSON)
'paywall_features' => '["users_management", "students_management", "reports"]'
```

## Fonctionnement des Vérifications

### Vérifications automatiques

Le middleware `PaywallMiddleware` vérifie automatiquement :

1. **Expiration d'abonnement**
   - Bloque si la date actuelle > date d'expiration
   - Avertit 7 jours avant expiration

2. **Limites d'utilisateurs**
   - Compte les enseignants/coordinateurs/secrétaires actifs
   - Bloque si dépassement
   - Avertit à 90% de la limite

3. **Limites d'étudiants**
   - Compte les étudiants avec inscriptions actives
   - Bloque si dépassement
   - Avertit à 90% de la limite

### Routes exclues

Ces routes ne sont pas soumises au paywall :
- `esbtp.paywall-config.*` (configuration)
- `logout`, `login`, `register`
- `password.*` (réinitialisation mot de passe)

### Code d'Accès d'Urgence

**Code :** `ADMIN2024EMERGENCY`

**Utilisation :**
1. **Méthode 1 - Paramètre URL :**
   ```
   https://ecole.monsite.com/dashboard?emergency_code=ADMIN2024EMERGENCY
   ```

2. **Méthode 2 - Accès direct au paywall :**
   ```
   https://ecole.monsite.com/esbtp/paywall-config?emergency_code=ADMIN2024EMERGENCY
   ```

**Caractéristiques :**
- ✅ **Durée :** Valide pendant 1 heure après activation
- ✅ **Portée :** Bypass complet du paywall
- ✅ **Sécurité :** Stocké en session, pas en URL après première utilisation
- ✅ **Auto-nettoyage :** Session supprimée automatiquement après expiration

**Exemple pratique :**
```bash
# École Saint-Paul est bloquée
git checkout saint-paul

# Vous accédez avec le code d'urgence
# URL: saint-paul.monsite.com/esbtp/paywall-config?emergency_code=ADMIN2024EMERGENCY

# Une fois connecté, vous pouvez naviguer librement pendant 1h
# sans avoir besoin de remettre le code
```

## Interface Utilisateur

### Page de configuration (`/esbtp/paywall-config`)

**Sections :**
1. **Statut Actuel** : Affichage en temps réel des limites et utilisations
2. **Statistiques** : Cards avec jauges visuelles (utilisateurs, étudiants, abonnement, prix)
3. **Configuration** : Formulaire pour modifier les paramètres
4. **Actions** : Sauvegarde et prolongation d'abonnement

**Alertes :**
- 🔴 **Bloqué** : Limites dépassées ou abonnement expiré
- 🟡 **Avertissement** : Proche des limites ou expiration proche
- 🟢 **Normal** : Tout fonctionne correctement

### Page de blocage

Quand l'accès est bloqué, l'utilisateur voit :
- **Raisons du blocage** : Liste claire des problèmes
- **Solutions proposées** : Actions pour résoudre
- **Informations de contact** : Support technique

## Exemples d'Utilisation

### Exemple 1 : École Saint-Paul (Plan Standard)

```bash
git checkout saint-paul
```

**Configuration :**
- 20 utilisateurs max
- 200 étudiants max
- Expire le 31/12/2024
- 25,000 FCFA

**Scenario :** L'école atteint 21 utilisateurs
- ❌ **Accès bloqué** automatiquement
- 📧 Message : "Limite d'utilisateurs dépassée (21/20)"
- ✅ **Solution** : Supprimer un compte ou augmenter la limite

### Exemple 2 : École Marie (Plan Premium)

```bash
git checkout marie
```

**Configuration :**
- 50 utilisateurs max
- 500 étudiants max
- Expire le 15/04/2025
- 45,000 FCFA

**Scenario :** Abonnement expire dans 5 jours
- ⚠️ **Avertissement** dans l'interface
- 🔄 **Action** : Prolonger l'abonnement via l'interface

### Exemple 3 : École Excellence (Démarrage)

```bash
git checkout excellence
```

**Configuration initiale :**
- 10 utilisateurs max
- 100 étudiants max
- 6 mois d'essai
- 15,000 FCFA

## API et Intégrations

### Endpoint de vérification

```php
GET /esbtp/paywall-config/status

Response:
{
    "is_blocked": false,
    "reasons": [],
    "warnings": ["Proche de la limite d'utilisateurs (18/20)"]
}
```

### Utilisation programmatique

```php
// Vérifier le statut
$paywall = app(ESBTPPaywallConfigController::class);
$status = $paywall->checkStatus();

// Obtenir les limites actuelles
$maxUsers = ESBTPSystemSetting::getValue('paywall_max_users', 50);
$maxStudents = ESBTPSystemSetting::getValue('paywall_max_students', 500);
```

## Gestion Multi-École

### Workflow administrateur

1. **Lundi** : École A vous appelle
   ```bash
   git checkout ecole-a
   # Configurer sur ecole-a.monsite.com/esbtp/paywall-config
   ```

2. **Mardi** : École B a un problème
   ```bash
   git checkout ecole-b
   # Résoudre sur ecole-b.monsite.com/esbtp/paywall-config
   ```

3. **Mercredi** : Nouvelle école C
   ```bash
   git checkout ecole-c
   # Configuration initiale sur ecole-c.monsite.com/esbtp/paywall-config
   ```

### Avantages

- **Isolation complète** : Une école ne peut pas voir les données d'une autre
- **Flexibilité tarifaire** : Prix et limites différents par école
- **Scalabilité** : Ajouter de nouvelles écoles facilement
- **Sécurité** : Chaque école a sa propre base de données

## Personnalisation

### Modifier les limites par défaut

Dans `ESBTPPaywallConfigController.php` :

```php
'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50), // Changer 50
'max_students' => ESBTPSystemSetting::getValue('paywall_max_students', 500), // Changer 500
```

### Ajouter de nouvelles vérifications

Dans `PaywallMiddleware.php`, méthode `checkPaywallStatus()` :

```php
// Exemple : Vérifier les modules activés
if ($stats['modules_count'] > $config['max_modules']) {
    $status['is_blocked'] = true;
    $status['reasons'][] = 'Limite de modules dépassée';
}
```

### Personnaliser les messages

Dans les vues :
- `resources/views/esbtp/paywall-config/index.blade.php`
- `resources/views/esbtp/paywall-config/blocked.blade.php`

## Déploiement

### Pour une nouvelle école

1. **Créer la branche**
   ```bash
   git checkout -b nouvelle-ecole
   ```

2. **Configurer le sous-domaine**
   - DNS : `nouvelle-ecole.monsite.com`
   - Base de données : `nouvelle_ecole_db`

3. **Configuration initiale**
   - Accéder à `/esbtp/paywall-config`
   - Définir les limites initiales
   - Configurer la date d'expiration

4. **Push et déploiement**
   ```bash
   git push origin nouvelle-ecole
   ```

### Mise à jour globale

```bash
# Pour toutes les écoles
git checkout main
git commit -m "Update paywall system"

# Ensuite pour chaque école
git checkout ecole-a && git merge main
git checkout ecole-b && git merge main
# etc.
```

## Troubleshooting

### Problème : "Page non trouvée"
- ✅ Vérifier que les routes sont dans `web.php`
- ✅ Vérifier que le contrôleur existe
- ✅ Nettoyer le cache : `php artisan route:clear`

### Problème : "Middleware ne fonctionne pas"
- ✅ Vérifier dans `Kernel.php`
- ✅ Appliquer le middleware aux routes concernées
- ✅ Vérifier les routes exclues

### Problème : "Settings non sauvegardés"
- ✅ Vérifier que `ESBTPSystemSetting` fonctionne
- ✅ Vérifier les permissions de base de données
- ✅ Regarder les logs Laravel

## Sécurité

### Mesures implementées

1. **Validation des données** : Tous les inputs sont validés
2. **CSRF Protection** : Tokens CSRF sur tous les formulaires
3. **Rôles et permissions** : Seuls superAdmin/secretaire peuvent configurer
4. **Isolation** : Chaque école ne peut modifier que ses propres paramètres

### Bonnes pratiques

- Changer régulièrement les mots de passe
- Surveiller les logs d'accès
- Sauvegarder régulièrement les configurations
- Tester les limites avant la mise en production

## Conclusion

Ce système de paywall offre une solution complète et flexible pour gérer les abonnements dans une architecture multi-tenant. Il respecte le principe existant d'isolation par école tout en offrant une interface moderne et intuitive pour la configuration.

**Contact Support :**
- Email : support@votre-domaine.com
- Téléphone : +225 XX XX XX XX XX