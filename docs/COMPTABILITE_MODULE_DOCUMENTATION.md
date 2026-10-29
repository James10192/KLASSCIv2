# Module Comptabilité KLASSCI - Documentation

> **Date de mise à jour** : 12 novembre 2025
> **Objectif** : Documentation du module comptabilité en production

---

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Gestion des Frais](#gestion-des-frais)
3. [Gestion des Paiements](#gestion-des-paiements)
4. [Configuration](#configuration)
5. [Modèles de Données](#modèles-de-données)
6. [API & Intégrations](#api--intégrations)

---

## 🎯 Vue d'ensemble

### Description

Le module comptabilité KLASSCI gère l'intégralité du cycle financier des établissements :
- Création et configuration des catégories de frais
- Affectation des frais aux classes/étudiants
- Gestion des paiements avec validation multi-niveau
- Génération automatique de reçus
- Suivi en temps réel et exports

### Accès Rapides

**Pages principales** :
- **Gestion des Frais** : `http://localhost:8000/esbtp/frais`
- **Configuration Frais** : `http://localhost:8000/esbtp/frais/configure`
- **Liste des Paiements** : `http://localhost:8000/esbtp/paiements`
- **Suivi par Catégorie** : `http://localhost:8000/esbtp/paiements/suivi-categories`

### Controllers & Modèles

| Composant | Fichier | Responsabilité |
|-----------|---------|----------------|
| **ESBTPFraisController** | `app/Http/Controllers/ESBTP/ESBTPFraisController.php` | Gestion complète des frais |
| **ESBTPPaiementController** | `app/Http/Controllers/ESBTP/ESBTPPaiementController.php` | Gestion complète des paiements |
| **ESBTPPaiement** | `app/Models/ESBTPPaiement.php` | Modèle central paiements |
| **ESBTPFraisCategory** | `app/Models/ESBTPFraisCategory.php` | Catégories de frais flexibles |

---

## 💰 Gestion des Frais

### Vue d'ensemble

**URL principale** : `http://localhost:8000/esbtp/frais`

Système flexible de gestion des frais scolaires permettant de créer et configurer n'importe quelle catégorie de frais (inscription, scolarité, examen, transport, cantine, etc.).

#### Routes Actives (37 routes)

```php
// Fichier: routes/web.php lignes 266-303

// 🟢 CRUD de base
GET     /esbtp/frais                              -> index()          // Liste des frais
GET     /esbtp/frais/create                       -> create()         // Formulaire création
POST    /esbtp/frais                              -> store()          // Enregistrer
GET     /esbtp/frais/{frais}                      -> show()           // Détails
GET     /esbtp/frais/{frais}/edit                 -> edit()           // Formulaire édition
PUT     /esbtp/frais/{frais}                      -> update()         // Mettre à jour
DELETE  /esbtp/frais/{frais}                      -> destroy()        // Supprimer

// 🟢 Configuration (pages principales sidebar)
GET     /esbtp/frais/configure                    -> configure()      // Page configuration principale
GET     /esbtp/frais/optional-config              -> optionalConfig() // Configuration frais optionnels
POST    /esbtp/frais/save-assignment              -> saveAssignment() // Enregistrer affectations

// 🟢 Gestion des catégories
GET     /esbtp/frais/get-categories               -> getCategories()  // API liste catégories
POST    /esbtp/frais/update-configuration         -> updateConfiguration()
POST    /esbtp/frais/{category}/toggle            -> toggleCategory() // Activer/désactiver
POST    /esbtp/frais/{fraisCategory}/toggle-active -> toggleActive()
POST    /esbtp/frais/reset-defaults               -> resetDefaults()  // Réinitialiser

// 🟢 Gestion des variantes
GET     /esbtp/frais/category-variants/{category} -> getCategoryVariants()
GET     /esbtp/frais/all-variants                 -> getAllVariants()
POST    /esbtp/frais/variants                     -> storeVariant()
PUT     /esbtp/frais/variants/{variant}           -> updateVariant()
DELETE  /esbtp/frais/variants/{variant}           -> destroyVariant()

// 🟢 Gestion des configurations
PUT     /esbtp/frais/configurations/{configuration} -> updateConfigurationInline()
POST    /esbtp/frais/configurations/{configuration}/toggle -> toggleConfigurationStatus()
GET     /esbtp/frais/configurations/{configuration}/options -> getConfigurationOptions()

// 🟢 Gestion des options
PUT     /esbtp/frais/options/{option}             -> updateOption()
POST    /esbtp/frais/options/{option}/toggle      -> toggleOptionStatus()
DELETE  /esbtp/frais/options/{option}             -> destroyOption()

// 🟢 Gestion des affectations (assignments)
GET     /esbtp/frais/options/{option}/assignments -> getOptionAssignments()
POST    /esbtp/frais/options/assignments          -> saveOptionAssignments()
DELETE  /esbtp/frais/assignments/{assignment}     -> removeAssignment()
DELETE  /esbtp/frais/options/{option}/assignments -> clearOptionAssignments()

// 🟢 Relances automatiques
GET     /esbtp/frais/{category}/overdue-students  -> getStudentsWithOverduePayments()
POST    /esbtp/frais/{category}/schedule-reminders -> scheduleAutomaticReminders()

// 🟢 API externe
GET     /frais/categories                         -> getCategoriesForApi() // API pour LMS
```

#### Vues Associées

```
resources/views/esbtp/frais/
├── index.blade.php           ✅ Page liste des frais
├── configure.blade.php       ✅ Page configuration principale
├── optional-config.blade.php ✅ Configuration frais optionnels
├── create.blade.php          ✅ Formulaire création
├── edit.blade.php            ✅ Formulaire édition
└── show.blade.php            ✅ Détails frais
```

#### Fonctionnalités Clés

1. **Catégories Flexibles** : Créer n'importe quelle catégorie (Inscription, Scolarité S1, Scolarité S2, Examen, etc.)
2. **Variantes** : Montants différents selon filière/niveau (ex: Inscription BTP 100k, GC 150k)
3. **Affectation Intelligente** : Assigner automatiquement les frais aux classes
4. **Options** : Frais optionnels (Transport, Cantine, etc.)
5. **Relances Automatiques** : Système de rappel pour retards de paiement

#### Modèles Utilisés

- `ESBTPFraisCategory` - Catégories de frais (Inscription, Scolarité, etc.)
- `ESBTPFraisVariant` - Variantes de montants selon filière/niveau
- `ESBTPFraisConfiguration` - Configuration par classe
- `ESBTPFraisOption` - Frais optionnels
- `ESBTPFraisSubscription` - Souscriptions étudiants (qui doit payer quoi)

---

### 2. Gestion des Paiements (ESBTPPaiementController)

**URL principale** : `http://localhost:8000/esbtp/paiements`

**Statut** : ✅ **ACTIF - BIEN IMPLÉMENTÉ**

**Description** : Système complet de gestion des paiements avec validation, suivi, exports et génération de reçus.

#### Routes Actives (36 routes)

```php
// Fichier: routes/web.php lignes 746-781

// 🟢 CRUD de base
GET     /esbtp/paiements                          -> index()          // Liste paiements
GET     /esbtp/paiements/create                   -> create()         // Formulaire création
POST    /esbtp/paiements                          -> store()          // Enregistrer
GET     /esbtp/paiements/{paiement}               -> show()           // Détails
GET     /esbtp/paiements/{paiement}/edit          -> edit()           // Formulaire édition
PUT     /esbtp/paiements/{paiement}               -> update()         // Mettre à jour
DELETE  /esbtp/paiements/{paiement}               -> destroy()        // Supprimer

// 🟢 Suivi et monitoring (page sidebar)
GET     /esbtp/paiements/suivi-categories         -> suiviCategories() // 📊 Tableau de bord par catégorie
GET     /esbtp/paiements/suivi-categories/refresh -> suiviCategoriesRefresh() // AJAX refresh
GET     /esbtp/paiements/suivi-categories/load/{statut} -> loadStudentsByStatut() // Charger étudiants

// 🟢 Exports (UTILISÉ EN PRODUCTION)
GET     /esbtp/paiements/export/excel             -> exportExcel()    // ⭐ Export Excel
GET     /esbtp/paiements/export/csv               -> exportCsv()      // ⭐ Export CSV
GET     /esbtp/paiements/export/pdf               -> exportPdf()      // ⭐ Export PDF

// 🟢 Génération de reçus
GET     /esbtp/paiements/{paiement}/preview       -> previewRecu()    // Aperçu reçu
GET     /esbtp/paiements/{paiement}/recu          -> genererRecu()    // Télécharger reçu PDF

// 🟢 Validation/Rejet
POST    /esbtp/paiements/{paiement}/valider       -> valider()        // Valider paiement
POST    /esbtp/paiements/{paiement}/rejeter       -> rejeter()        // Rejeter paiement
POST    /esbtp/paiements/{paiement}/valider-rapide -> validerRapide() // Validation rapide

// 🟢 Actions groupées
POST    /esbtp/paiements/bulk-valider             -> bulkValider()    // Valider plusieurs
POST    /esbtp/paiements/bulk-rejeter             -> bulkRejeter()    // Rejeter plusieurs

// 🟢 Consultation
GET     /esbtp/paiements/etudiant/{etudiant}      -> paiementsEtudiant() // Historique étudiant

// 🟢 Reliquats
POST    /reliquats/pay                            -> payReliquat()    // Payer reliquat ancien

// 🟢 AJAX refresh
GET     /esbtp/paiements/refresh                  -> refresh()        // Rafraîchir liste
GET     /esbtp/paiements/check-updates            -> checkForUpdates() // Vérifier nouveautés
GET     /esbtp/paiements/{paiement}/refresh-ligne -> refreshLigne()   // Rafraîchir ligne

// 🟡 DEBUG (à vérifier si utilisé)
GET     /esbtp/paiements/test-filters             -> testFilters()    // Page test filtres
```

#### Vues Associées

```
resources/views/esbtp/paiements/
├── index.blade.php           ✅ Page liste paiements (avec filtres, exports)
├── suivi-categories.blade.php ✅ Tableau de bord suivi par catégorie
├── create.blade.php          ✅ Formulaire création
├── edit.blade.php            ✅ Formulaire édition
├── show.blade.php            ✅ Détails paiement
└── partials/
    ├── recu.blade.php        ✅ Template reçu PDF
    └── filters.blade.php     ✅ Filtres avancés
```

#### Fonctionnalités Clés

1. **CRUD Complet** : Création, modification, suppression paiements
2. **Validation Multi-Niveau** : Validation immédiate, rapide ou différée
3. **Actions Groupées** : Valider/rejeter plusieurs paiements en masse
4. **Exports Multiples** : Excel, CSV, PDF avec respect des filtres
5. **Génération Automatique Reçus** : Numéro unique auto-généré (format: RECYY-00001)
6. **Suivi Par Catégorie** : Dashboard montrant état paiements par catégorie de frais
7. **Reliquats** : Gestion des dettes d'années précédentes
8. **AJAX Refresh** : Mise à jour en temps réel sans recharger page (polling 30s)

#### Modèles Utilisés

- `ESBTPPaiement` - Paiements étudiants (modèle principal)
- `ESBTPFraisCategory` - Catégories de frais (relation)
- `ESBTPInscription` - Inscription associée
- `ESBTPEtudiant` - Étudiant qui paie
- `ESBTPReliquatDetail` - Détails reliquats

---

### 3. Configuration Frais (Sidebar - 2 pages)

**URLs** :
- **Configuration principale** : `http://localhost:8000/esbtp/frais/configure`
- **Configuration optionnelle** : `http://localhost:8000/esbtp/frais/optional-config`

**Statut** : ✅ **ACTIF - BIEN IMPLÉMENTÉ**

**Description** : Pages de configuration des frais permettant d'assigner les catégories aux classes et de configurer les frais optionnels.

Ces pages sont accessibles via la sidebar et sont les SEULES pages de configuration comptabilité utilisées en production.

---

## 📊 Mapping Complet des Routes

### Routes Actives par Controller

#### ESBTPFraisController (37 routes actives)

| Méthode HTTP | Route | Méthode Controller | Statut |
|--------------|-------|-------------------|--------|
| GET | /esbtp/frais | index() | ✅ ACTIF |
| GET | /esbtp/frais/configure | configure() | ✅ ACTIF (sidebar) |
| GET | /esbtp/frais/optional-config | optionalConfig() | ✅ ACTIF (sidebar) |
| POST | /esbtp/frais/save-assignment | saveAssignment() | ✅ ACTIF |
| GET | /esbtp/frais/get-categories | getCategories() | ✅ ACTIF |
| POST | /esbtp/frais/update-configuration | updateConfiguration() | ✅ ACTIF |
| POST | /esbtp/frais/{category}/toggle | toggleCategory() | ✅ ACTIF |
| POST | /esbtp/frais/{fraisCategory}/toggle-active | toggleActive() | ✅ ACTIF |
| POST | /esbtp/frais/reset-defaults | resetDefaults() | ✅ ACTIF |
| GET | /esbtp/frais/create | create() | ✅ ACTIF |
| POST | /esbtp/frais | store() | ✅ ACTIF |
| GET | /esbtp/frais/{frais} | show() | ✅ ACTIF |
| GET | /esbtp/frais/{frais}/edit | edit() | ✅ ACTIF |
| PUT | /esbtp/frais/{frais} | update() | ✅ ACTIF |
| DELETE | /esbtp/frais/{frais} | destroy() | ✅ ACTIF |
| GET | /esbtp/frais/class-details/{filiere}/{niveau} | getClassDetails() | ✅ ACTIF |
| GET | /esbtp/frais/category-variants/{category} | getCategoryVariants() | ✅ ACTIF |
| GET | /esbtp/frais/all-variants | getAllVariants() | ✅ ACTIF |
| POST | /esbtp/frais/variants | storeVariant() | ✅ ACTIF |
| PUT | /esbtp/frais/variants/{variant} | updateVariant() | ✅ ACTIF |
| DELETE | /esbtp/frais/variants/{variant} | destroyVariant() | ✅ ACTIF |
| PUT | /esbtp/frais/configurations/{configuration} | updateConfigurationInline() | ✅ ACTIF |
| POST | /esbtp/frais/configurations/{configuration}/toggle | toggleConfigurationStatus() | ✅ ACTIF |
| GET | /esbtp/frais/configurations/{configuration}/options | getConfigurationOptions() | ✅ ACTIF |
| PUT | /esbtp/frais/options/{option} | updateOption() | ✅ ACTIF |
| POST | /esbtp/frais/options/{option}/toggle | toggleOptionStatus() | ✅ ACTIF |
| DELETE | /esbtp/frais/options/{option} | destroyOption() | ✅ ACTIF |
| GET | /esbtp/frais/options/{option}/assignments | getOptionAssignments() | ✅ ACTIF |
| POST | /esbtp/frais/options/assignments | saveOptionAssignments() | ✅ ACTIF |
| DELETE | /esbtp/frais/assignments/{assignment} | removeAssignment() | ✅ ACTIF |
| DELETE | /esbtp/frais/options/{option}/assignments | clearOptionAssignments() | ✅ ACTIF |
| GET | /esbtp/frais/{category}/overdue-students | getStudentsWithOverduePayments() | ✅ ACTIF |
| POST | /esbtp/frais/{category}/schedule-reminders | scheduleAutomaticReminders() | ✅ ACTIF |
| GET | /frais/categories | getCategoriesForApi() | ✅ ACTIF (API) |

#### ESBTPPaiementController (36 routes actives)

| Méthode HTTP | Route | Méthode Controller | Statut |
|--------------|-------|-------------------|--------|
| GET | /esbtp/paiements | index() | ✅ ACTIF (sidebar) |
| GET | /esbtp/paiements/suivi-categories | suiviCategories() | ✅ ACTIF (sidebar) |
| GET | /esbtp/paiements/suivi-categories/refresh | suiviCategoriesRefresh() | ✅ ACTIF |
| GET | /esbtp/paiements/suivi-categories/load/{statut} | loadStudentsByStatut() | ✅ ACTIF |
| GET | /esbtp/paiements/export/excel | exportExcel() | ✅ ACTIF ⭐ |
| GET | /esbtp/paiements/export/csv | exportCsv() | ✅ ACTIF ⭐ |
| GET | /esbtp/paiements/export/pdf | exportPdf() | ✅ ACTIF ⭐ |
| GET | /esbtp/paiements/create | create() | ✅ ACTIF |
| POST | /esbtp/paiements | store() | ✅ ACTIF |
| GET | /esbtp/paiements/{paiement} | show() | ✅ ACTIF |
| GET | /esbtp/paiements/{paiement}/edit | edit() | ✅ ACTIF |
| PUT | /esbtp/paiements/{paiement} | update() | ✅ ACTIF |
| DELETE | /esbtp/paiements/{paiement} | destroy() | ✅ ACTIF |
| GET | /esbtp/paiements/{paiement}/preview | previewRecu() | ✅ ACTIF |
| GET | /esbtp/paiements/{paiement}/recu | genererRecu() | ✅ ACTIF |
| GET | /esbtp/paiements/etudiant/{etudiant} | paiementsEtudiant() | ✅ ACTIF |
| POST | /reliquats/pay | payReliquat() | ✅ ACTIF |
| POST | /esbtp/paiements/{paiement}/valider | valider() | ✅ ACTIF |
| POST | /esbtp/paiements/{paiement}/rejeter | rejeter() | ✅ ACTIF |
| POST | /esbtp/paiements/{paiement}/valider-rapide | validerRapide() | ✅ ACTIF |
| POST | /esbtp/paiements/bulk-valider | bulkValider() | ✅ ACTIF |
| POST | /esbtp/paiements/bulk-rejeter | bulkRejeter() | ✅ ACTIF |
| GET | /esbtp/paiements/refresh | refresh() | ✅ ACTIF |
| GET | /esbtp/paiements/check-updates | checkForUpdates() | ✅ ACTIF |
| GET | /esbtp/paiements/{paiement}/refresh-ligne | refreshLigne() | ✅ ACTIF |
| GET | /esbtp/paiements/test-filters | testFilters() | 🟡 À VÉRIFIER |

---

## 🗄️ Modèles de Données

### Modèles Actifs

#### ESBTPPaiement (380 lignes) ✅

**Fichier** : `app/Models/ESBTPPaiement.php`

**Statut** : ✅ **ACTIF - BIEN IMPLÉMENTÉ**

**Description** : Modèle central pour les paiements avec audit complet, génération automatique de reçus, et gestion des relations.

**Champs clés** :
```php
protected $fillable = [
    'inscription_id',           // Lien vers inscription
    'etudiant_id',             // Lien vers étudiant
    'annee_universitaire_id',  // Année universitaire
    'type_paiement',           // scolarite, inscription, frais_divers
    'categorie_id',            // Ancienne catégorie (legacy)
    'frais_category_id',       // ✅ Nouvelle catégorie flexible
    'montant',                 // Montant payé
    'reference_paiement',      // Référence unique
    'mode_paiement',           // especes, cheque, virement, mobile_money
    'numero_transaction',      // Numéro transaction bancaire/mobile
    'date_paiement',           // Date du paiement
    'date_echeance',           // Date limite
    'statut',                  // ⚠️ Ancien champ (à vérifier usage)
    'status',                  // ✅ Nouveau : en_attente, validé, rejeté
    'createur_id',             // Qui a créé
    'validateur_id',           // Qui a validé
    'date_validation',         // Quand validé
    'motif',                   // Raison du paiement
    'numero_recu',             // ✅ Auto-généré (RECYY-00001)
    'commentaire',             // Notes
    'reference_externe',       // Référence système externe
    'metadata',                // JSON metadata flexible
    'relance_id',              // Lien vers relance
    'reliquat_detail_id',      // Lien vers reliquat
];
```

**Relations** :
- `categorie()` - Ancienne catégorie (legacy)
- `fraisCategory()` - ✅ Nouvelle catégorie flexible
- `etudiant()` - Étudiant qui paie
- `anneeUniversitaire()` - Année universitaire
- `createur()` - Utilisateur créateur
- `validateur()` - Utilisateur validateur
- `inscription()` - Inscription associée

**Méthodes utiles** :
```php
public static function genererNumeroRecu($prefix = 'REC'); // Auto-génération reçu
public function scopeValides($query);                       // Filtre paiements validés
public function scopeEnAttente($query);                     // Filtre en attente
public function scopeRejetes($query);                       // Filtre rejetés
public function scopeScolarite($query);                     // Filtre scolarité
public function getStatusFormatteAttribute();               // Statut formaté
```

**Audit configuré** :
```php
protected $auditInclude = [
    'montant', 'reference_paiement', 'mode_paiement',
    'numero_transaction', 'date_paiement', 'statut',
    'validateur_id', 'date_validation', 'numero_recu',
    'reference_externe', 'metadata', 'relance_id'
];
```

---

#### ESBTPFraisCategory (~250 lignes estimées) ✅

**Fichier** : `app/Models/ESBTPFraisCategory.php`

**Statut** : ✅ **ACTIF - SYSTÈME FLEXIBLE**

**Description** : Catégories de frais flexibles (remplace les anciens frais hard-codés).

**Exemples de catégories** :
- Frais d'inscription
- Scolarité Semestre 1
- Scolarité Semestre 2
- Frais d'examen
- Frais de stage
- Transport (optionnel)
- Cantine (optionnel)

**Champs clés** :
- `name` - Nom de la catégorie
- `code` - Code unique
- `description` - Description
- `type` - obligatoire, optionnel
- `is_active` - Actif ou non
- `ordre` - Ordre d'affichage

**Relations** :
- `variants()` - Variantes de montants selon filière/niveau
- `configurations()` - Configurations par classe
- `subscriptions()` - Souscriptions étudiants
- `paiements()` - Paiements associés

---

#### ESBTPFraisVariant ✅

**Description** : Permet des montants différents selon filière/niveau.

**Exemple** :
```
Frais d'inscription :
- BTP L1 : 100,000 FCFA
- Génie Civil L1 : 150,000 FCFA
- Architecture L1 : 200,000 FCFA
```

---

#### ESBTPFraisSubscription ✅

**Description** : Enregistre qui doit payer quoi (souscriptions étudiants).

**Champs clés** :
- `inscription_id` - Inscription étudiant
- `frais_category_id` - Catégorie de frais
- `amount` - Montant dû
- `is_mandatory` - Obligatoire ou non
- `status` - pending, subscribed, cancelled

---

## 🔌 API & Intégrations

### API Externe LMS

**Endpoint** : `GET /frais/categories`

**Méthode Controller** : `getCategoriesForApi()` (ESBTPFraisController)

**Description** : Fournit la liste des catégories de frais au système LMS (Learning Management System) pour synchronisation et affichage des frais dans l'interface étudiante.

**Utilisation** : Cette API permet au LMS d'afficher les frais applicables à chaque étudiant selon sa classe et son niveau, avec les montants correspondants.

### Génération de Reçus PDF

**Service** : Génération automatique de reçus de paiement au format PDF

**Format des reçus** :
- Numéro unique auto-généré : `RECYY-XXXXX` (ex: REC25-00001)
- Informations étudiant : Nom, matricule, classe
- Détails paiement : Montant, catégorie, mode de paiement, date
- Template PDF professionnel avec logo établissement

**Routes concernées** :
- `GET /esbtp/paiements/{paiement}/preview` - Aperçu du reçu
- `GET /esbtp/paiements/{paiement}/recu` - Téléchargement PDF

### Exports de Données

**Formats supportés** : Excel (.xlsx), CSV, PDF

**Fonctionnalités** :
- Export liste paiements avec filtres appliqués
- Export suivi par catégorie
- Respect de tous les critères de filtrage actifs
- Statistiques incluses dans les exports

**Routes** :
- `GET /esbtp/paiements/export/excel`
- `GET /esbtp/paiements/export/csv`
- `GET /esbtp/paiements/export/pdf`

### Système de Relances Automatiques

**Description** : Identification automatique des étudiants en retard de paiement et programmation de relances.

**Routes** :
- `GET /esbtp/frais/{category}/overdue-students` - Liste étudiants en retard
- `POST /esbtp/frais/{category}/schedule-reminders` - Planifier relances

---

## 📝 Conclusion

Le module comptabilité KLASSCI est un système complet et flexible de gestion financière pour établissements d'enseignement. Il couvre l'intégralité du cycle de vie des frais et paiements :

1. **Configuration** : Création et paramétrage des catégories de frais avec variantes par filière/niveau
2. **Affectation** : Attribution automatique ou manuelle des frais aux classes et étudiants
3. **Collecte** : Enregistrement des paiements avec validation multi-niveau
4. **Suivi** : Tableaux de bord en temps réel et exports multiformats
5. **Reporting** : Génération automatique de reçus et rapports de suivi

**Points forts** :
- ✅ Système flexible permettant toute configuration de frais
- ✅ Validation rigoureuse avec workflow configurable
- ✅ Traçabilité complète avec audit trail
- ✅ Interface moderne avec mises à jour AJAX temps réel
- ✅ Exports multiformats pour reporting et archivage
- ✅ API pour intégration avec systèmes externes (LMS)

**Documentation technique complète** disponible dans le code source :
- Controllers : `app/Http/Controllers/ESBTP/`
- Modèles : `app/Models/`
- Vues : `resources/views/esbtp/frais/` et `resources/views/esbtp/paiements/`
- Routes : `routes/web.php`

---

*Document généré le 12 novembre 2025 - Version production KLASSCI v2*
