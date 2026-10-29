# Plan Système de Gestion des Dépenses - 100% Configurable par l'utilisateur

**Date**: 20 octobre 2025
**Statut**: 📝 **En attente de validation utilisateur**

---

## 🎯 Philosophie du système

**ZÉRO hardcoding. TOUT est configurable par l'utilisateur.**

L'utilisateur crée les catégories, sous-catégories et types qu'il souhaite. Le système ne présume RIEN.

**Exemples de ce que l'utilisateur PEUT créer (mais n'est pas obligé)** :
- Catégorie "Salaires" avec sous-catégorie "Honoraires cours magistraux"
- Catégorie "Charges externes" avec sous-catégorie "Loyers"
- Catégorie "Achats" avec sous-catégorie "Fournitures de bureau"
- Ou n'importe quelle autre structure selon SES besoins

**Inspiration : Logiciels comptables leaders (QuickBooks, Xero, Sage Intacct)**
- Chart of Accounts (Plan comptable) 100% personnalisable
- Catégories hiérarchiques infinies
- Types/fréquences configurables
- Aucune structure imposée

---

## 🏗️ Architecture proposée

### Tables à créer/modifier

#### 1. Table `esbtp_categories_depenses` (EXISTANTE - Structure OK)

**Déjà en place** :
```php
- id
- nom
- code
- description
- parent_id (pour hiérarchie)
- est_actif
- created_at, updated_at, deleted_at
```

**Utilisation** :
- L'utilisateur crée la structure hiérarchique qu'il veut
- Aucune limitation, aucune catégorie pré-créée
- Exemples possibles :
  - `SALAIRES` > `HONORAIRES` > `COURS_MAGISTRAUX`
  - `CHARGES` > `LOYERS`
  - `ACHATS` > `FOURNITURES`
  - Ou toute autre structure

---

#### 2. Table `esbtp_types_recurrence` (NOUVELLE)

**But** : Définir les fréquences de dépenses

```php
Schema::create('esbtp_types_recurrence', function (Blueprint $table) {
    $table->id();
    $table->string('nom')->unique(); // ex: "Unique", "Mensuelle", "Trimestrielle"
    $table->string('code')->unique(); // ex: "UNIQUE", "MONTHLY", "QUARTERLY"
    $table->text('description')->nullable();
    $table->enum('periodicite', ['unique', 'quotidienne', 'hebdomadaire', 'mensuelle', 'trimestrielle', 'semestrielle', 'annuelle'])
          ->default('unique');
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['is_active', 'periodicite']);
});
```

**Liberté utilisateur** :
- Créer ses propres types : "Bi-mensuel", "Hebdomadaire paie", etc.
- Modifier/supprimer à volonté
- Activer/désactiver selon besoins

---

#### 3. Table `esbtp_depenses` (EXISTANTE - Ajouts)

**Champs existants** (conservés) :
```php
- id
- categorie_id (→ esbtp_categories_depenses)
- reference
- libelle
- description
- montant
- date_depense
- mode_paiement
- numero_transaction
- fournisseur_id
- statut (brouillon, en attente, validée, annulée)
- createur_id
- validateur_id
- date_validation
- path_justificatif
- notes_internes
- numero_bon
- statut_workflow
- workflow_data
- approved_by
- date_approbation
- bon_sortie_id
- created_at, updated_at, deleted_at
```

**Nouveaux champs à ajouter** :

```php
Schema::table('esbtp_depenses', function (Blueprint $table) {
    // Lien type de récurrence
    $table->foreignId('type_recurrence_id')
          ->nullable()
          ->after('categorie_id')
          ->constrained('esbtp_types_recurrence')
          ->nullOnDelete()
          ->comment('Type de récurrence si applicable');

    // Bénéficiaire polymorphique (optionnel)
    $table->nullableMorphs('beneficiaire');
    $table->string('beneficiaire_role')->nullable()
          ->comment('enseignant, fournisseur, personnel, etc.');

    // Métadonnées supplémentaires (optionnel)
    $table->json('metadata')->nullable()
          ->comment('Données flexibles selon type de dépense');

    $table->index(['beneficiaire_type', 'beneficiaire_id']);
    $table->index('type_recurrence_id');
});
```

**Pourquoi metadata JSON ?**
- Flexibilité totale pour stocker des données spécifiques
- Exemples :
  - Honoraires : `{"heures_effectuees": 15.5, "taux_horaire": 5000, "detail_seances": [...]}`
  - Loyer : `{"periode_location": "Octobre 2025", "surface_m2": 200}`
  - Fournitures : `{"quantite": 50, "prix_unitaire": 2000}`

---

#### 4. Table `esbtp_teachers` - Ajout taux_horaire (MODIFICATION)

```php
Schema::table('esbtp_teachers', function (Blueprint $table) {
    $table->decimal('taux_horaire', 10, 2)->default(0)
          ->after('teaching_hours_due')
          ->comment('Taux horaire de base en FCFA/heure (optionnel)');

    $table->index('taux_horaire');
});
```

**Note** : C'est juste un champ optionnel. Aucune logique hardcodée autour.

---

## 🎨 Interface utilisateur - 100% flexible

### Page `/esbtp/depenses/configuration`

**Onglet 1 : Catégories**
- Arbre hiérarchique des catégories
- CRUD complet : Créer, Modifier, Supprimer
- Drag & drop pour réorganiser
- Activation/désactivation rapide
- Aucune catégorie pré-créée → L'utilisateur démarre avec une ardoise vierge

**Onglet 2 : Types de récurrence**
- Liste des types de récurrence
- CRUD complet
- Exemples fournis (en seed optionnel) : Unique, Mensuelle, Annuelle
- Mais l'utilisateur peut créer "Bi-hebdomadaire", "Chaque trimestre", etc.

**Onglet 3 : Règles et workflows** (optionnel futur)
- Règles d'approbation personnalisées
- Seuils de validation
- Notifications automatiques

---

### Page `/esbtp/depenses`

**Filtres dynamiques** :
- Par catégorie (liste des catégories créées par l'utilisateur)
- Par type de récurrence (liste des types créés par l'utilisateur)
- Par bénéficiaire (si renseigné)
- Par statut
- Par période

**Actions groupées** :
- Valider plusieurs dépenses
- Exporter (Excel, PDF)
- Dupliquer (pour créer une dépense similaire)

**Bouton "Nouvelle dépense"** :
- Formulaire flexible :
  - Catégorie (liste déroulante des catégories créées)
  - Type de récurrence (optionnel)
  - Bénéficiaire (optionnel - autocomplete polymorphique)
  - Montant
  - Date
  - Métadonnées personnalisées (formulaire dynamique basé sur la catégorie)

---

### Page `/esbtp/depenses/{id}` - Détails

**Sections** :
- Informations générales
- Bénéficiaire (si renseigné) avec lien vers profil
- Métadonnées (affichage dynamique du JSON)
- Timeline workflow
- Justificatifs uploadés

---

## 🔧 Logique métier - Exemples d'utilisation

### Exemple 1 : Honoraires enseignants (si l'utilisateur le souhaite)

**Étape 1 : Configuration** (faite par l'utilisateur)

```
1. Créer catégorie "Salaires et honoraires"
2. Créer sous-catégorie "Honoraires cours magistraux"
3. Créer type de récurrence "Mensuelle"
```

**Étape 2 : Créer une dépense honoraire**

```php
ESBTPDepense::create([
    'categorie_id' => $honorairesCoursMagCategoryId,
    'type_recurrence_id' => $mensuellTypeId,
    'reference' => 'HON-2025-10-001',
    'libelle' => 'Honoraires octobre 2025 - Prof. KOUASSI',
    'montant' => 75000,
    'date_depense' => '2025-10-31',
    'beneficiaire_type' => 'App\Models\ESBTPTeacher',
    'beneficiaire_id' => 12,
    'beneficiaire_role' => 'enseignant',
    'metadata' => [
        'heures_effectuees' => 15.0,
        'taux_horaire' => 5000,
        'periode' => 'Octobre 2025',
        'detail_seances' => [...]
    ],
    'statut' => 'brouillon',
]);
```

**Étape 3 : Service de calcul automatique (OPTIONNEL)**

Si l'utilisateur veut automatiser le calcul, on peut créer un service :

```php
// app/Services/HonoraireCalculator.php (optionnel)
class HonoraireCalculator
{
    public function calculer(ESBTPTeacher $teacher, Carbon $dateDebut, Carbon $dateFin)
    {
        // Logique de calcul basée sur attendances
        $heures = $this->getHeuresPayables($teacher, $dateDebut, $dateFin);
        $montant = $heures * $teacher->taux_horaire;

        return [
            'heures' => $heures,
            'montant' => $montant,
            'metadata' => [...]
        ];
    }
}
```

**Mais l'utilisateur peut aussi créer la dépense manuellement sans service !**

---

### Exemple 2 : Loyer mensuel

**Configuration utilisateur** :

```
1. Créer catégorie "Charges externes"
2. Créer sous-catégorie "Loyers"
3. Créer type de récurrence "Mensuelle"
```

**Créer dépense** :

```php
ESBTPDepense::create([
    'categorie_id' => $loyerCategoryId,
    'type_recurrence_id' => $mensuelleTypeId,
    'reference' => 'LOY-2025-10-001',
    'libelle' => 'Loyer octobre 2025 - Bâtiment principal',
    'montant' => 500000,
    'date_depense' => '2025-10-05',
    'fournisseur_id' => 5, // Propriétaire dans table fournisseurs
    'metadata' => [
        'periode_location' => 'Octobre 2025',
        'surface_m2' => 200,
        'adresse' => 'Avenue XYZ, Abidjan'
    ],
    'statut' => 'brouillon',
]);
```

---

### Exemple 3 : Achat fournitures (dépense unique)

**Configuration utilisateur** :

```
1. Créer catégorie "Achats"
2. Créer sous-catégorie "Fournitures de bureau"
3. Créer type de récurrence "Unique"
```

**Créer dépense** :

```php
ESBTPDepense::create([
    'categorie_id' => $fournituresCategoryId,
    'type_recurrence_id' => $uniqueTypeId,
    'reference' => 'ACH-2025-10-042',
    'libelle' => 'Achat ramettes papier A4',
    'montant' => 50000,
    'date_depense' => '2025-10-18',
    'fournisseur_id' => 8,
    'metadata' => [
        'quantite' => 50,
        'prix_unitaire' => 1000,
        'reference_fournisseur' => 'PAPIER-A4-500'
    ],
    'statut' => 'brouillon',
]);
```

---

## 🚀 Fonctionnalités avancées (optionnelles)

### 1. Assistant de configuration

**Page** : `/esbtp/depenses/assistant`

**But** : Aider l'utilisateur à créer sa structure initiale

**Workflow** :
1. "Bienvenue dans la configuration des dépenses"
2. "Nous allons créer votre structure de catégories"
3. Proposer templates prédéfinis (optionnels) :
   - Template "Établissement scolaire" (Salaires, Charges, Achats)
   - Template "PME classique" (Personnel, Opérations, Investissements)
   - Template "Association" (Fonctionnement, Projets, Subventions)
   - **OU** "Partir d'une ardoise vierge"
4. Si template choisi → Pré-créer catégories suggérées (modifiables)
5. Si ardoise vierge → Mode création libre

---

### 2. Génération automatique dépenses récurrentes

**Commande** : `php artisan depenses:generate-recurrentes`

**Logique** :
- Parcourt les dépenses avec `type_recurrence.periodicite != 'unique'`
- Vérifie si la prochaine occurrence doit être générée
- Créé une nouvelle dépense identique (copie metadata, montant, etc.)
- Statut = brouillon
- Notifie l'admin

**Configuration** (dans UI) :
- Activer/désactiver auto-génération par dépense
- Définir la date de prochaine génération

---

### 3. Rapports comptables personnalisés

**Page** : `/esbtp/rapports/depenses`

**Rapports disponibles** :
- Par catégorie (graphique camembert)
- Par type de récurrence (barre)
- Évolution temporelle (ligne)
- Top 10 dépenses
- Comparatif budgété vs réel (si budget défini)

**Export** :
- PDF formaté
- Excel avec tableaux croisés dynamiques
- CSV pour comptabilité externe

---

## 📊 Seeders (optionnels)

### TypesRecurrenceSeeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPTypeRecurrence;

class TypesRecurrenceSeeder extends Seeder
{
    public function run()
    {
        $types = [
            [
                'nom' => 'Unique',
                'code' => 'UNIQUE',
                'description' => 'Dépense ponctuelle, non récurrente',
                'periodicite' => 'unique',
                'sort_order' => 1,
            ],
            [
                'nom' => 'Mensuelle',
                'code' => 'MONTHLY',
                'description' => 'Dépense qui se répète chaque mois',
                'periodicite' => 'mensuelle',
                'sort_order' => 2,
            ],
            [
                'nom' => 'Trimestrielle',
                'code' => 'QUARTERLY',
                'description' => 'Dépense qui se répète chaque trimestre',
                'periodicite' => 'trimestrielle',
                'sort_order' => 3,
            ],
            [
                'nom' => 'Semestrielle',
                'code' => 'SEMI_ANNUAL',
                'description' => 'Dépense qui se répète chaque semestre',
                'periodicite' => 'semestrielle',
                'sort_order' => 4,
            ],
            [
                'nom' => 'Annuelle',
                'code' => 'ANNUAL',
                'description' => 'Dépense qui se répète chaque année',
                'periodicite' => 'annuelle',
                'sort_order' => 5,
            ],
        ];

        foreach ($types as $type) {
            ESBTPTypeRecurrence::create($type);
        }
    }
}
```

**Note** : Ce seeder est OPTIONNEL. L'utilisateur peut créer ses propres types via l'interface.

---

### CategoriesDepensesTemplateSeeder (OPTIONNEL)

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPCategorieDepense;

class CategoriesDepensesTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * ATTENTION: Ce seeder est OPTIONNEL et seulement pour démonstration
     * L'utilisateur devrait créer ses propres catégories via l'interface
     */
    public function run()
    {
        $this->command->warn('Ce seeder crée des catégories de démonstration.');
        $this->command->warn('L\'utilisateur devrait créer ses propres catégories via l\'interface.');

        if (!$this->command->confirm('Voulez-vous créer les catégories de démonstration ?', false)) {
            $this->command->info('Seeder annulé. Utilisez l\'interface pour créer vos catégories.');
            return;
        }

        // Template "Établissement scolaire"
        $salaires = ESBTPCategorieDepense::create([
            'nom' => 'Salaires et honoraires',
            'code' => 'SAL',
            'description' => 'Rémunérations du personnel',
        ]);

        $charges = ESBTPCategorieDepense::create([
            'nom' => 'Charges externes',
            'code' => 'CHARGES',
            'description' => 'Charges d\'exploitation externes',
        ]);

        $achats = ESBTPCategorieDepense::create([
            'nom' => 'Achats',
            'code' => 'ACH',
            'description' => 'Achats de biens et fournitures',
        ]);

        // Sous-catégories (exemples)
        ESBTPCategorieDepense::create([
            'nom' => 'Honoraires enseignants',
            'code' => 'SAL_HON',
            'parent_id' => $salaires->id,
        ]);

        ESBTPCategorieDepense::create([
            'nom' => 'Loyers',
            'code' => 'CHARGES_LOY',
            'parent_id' => $charges->id,
        ]);

        ESBTPCategorieDepense::create([
            'nom' => 'Fournitures de bureau',
            'code' => 'ACH_FOUR',
            'parent_id' => $achats->id,
        ]);
    }
}
```

---

## 🔒 Sécurité

### Permissions

```php
'depenses' => [
    // Existantes
    'view_depenses',
    'create_depenses',
    'validate_depenses',
    'pay_depenses',
    'delete_depenses',

    // Nouvelles
    'configure_depenses',       // Admin - Gérer catégories et types
    'generate_recurrentes',     // Admin - Générer dépenses récurrentes
    'view_reports',             // Coordinateur, Admin - Voir rapports
],
```

### Audit (déjà en place)

```php
// ESBTPDepense utilise déjà Auditable trait
protected $auditInclude = [
    'montant',
    'reference',
    'libelle',
    'statut',
    'categorie_id',
    'type_recurrence_id', // Nouveau
    'beneficiaire_type',  // Nouveau
    'beneficiaire_id',    // Nouveau
    // ...
];
```

---

## 📦 Plan d'implémentation

### Phase 1 : Database (1h)

1. ✅ Migration : `create_esbtp_types_recurrence_table`
2. ✅ Migration : `add_fields_to_esbtp_depenses` (type_recurrence_id, beneficiaire polymorphic, metadata)
3. ✅ Migration : `add_taux_horaire_to_esbtp_teachers`

### Phase 2 : Models (30min)

4. ✅ Model : `ESBTPTypeRecurrence`
5. ✅ Update : `ESBTPDepense` (relations typeRecurrence, beneficiaire)
6. ✅ Update : `ESBTPTeacher` (add taux_horaire fillable)
7. ✅ Update : `ESBTPCategorieDepense` (aucun changement nécessaire)

### Phase 3 : Services optionnels (1h)

8. ⏳ Service : `HonoraireCalculator` (OPTIONNEL - seulement si l'utilisateur veut automatiser)
9. ⏳ Command : `GenerateDepensesRecurrentes` (pour dépenses récurrentes automatiques)

### Phase 4 : Controllers (2h)

10. ✅ Controller : `ESBTPTypeRecurrenceController` (CRUD types récurrence)
11. ✅ Update : `ESBTPDepenseController` (ajout filtres, metadata dynamique)
12. ✅ Controller : `ESBTPCategorieDepenseController` (CRUD catégories - peut-être déjà existant ?)

### Phase 5 : Views (3h)

13. ✅ Views : `depenses/configuration.blade.php` (tabs catégories + types + rules)
14. ✅ Update : `depenses/index.blade.php` (filtres dynamiques, bouton "Nouvelle dépense")
15. ✅ Update : `depenses/create.blade.php` (formulaire flexible avec metadata)
16. ✅ Update : `depenses/show.blade.php` (affichage beneficiaire + metadata)
17. ✅ Views : `depenses/assistant.blade.php` (OPTIONNEL - assistant configuration initiale)
18. ✅ Update : `teachers/show.blade.php` (section optionnelle honoraires si utilisé)

### Phase 6 : Seeders & Documentation (1h)

19. ✅ Seeder : `TypesRecurrenceSeeder` (optionnel)
20. ✅ Seeder : `CategoriesDepensesTemplateSeeder` (optionnel avec confirmation)
21. ✅ Documentation : CLAUDE.md

**Estimation totale** : 8-9 heures (1 jour)

---

## ✅ Points clés à valider

1. **Structure 100% flexible** - L'utilisateur crée TOUT (catégories, types, etc.) ?
2. **Beneficiaire polymorphique** - Permet de lier dépense → enseignant/fournisseur/autre ?
3. **Metadata JSON** - Permet de stocker des données spécifiques à chaque type de dépense ?
4. **Seeders optionnels** - Fournir des exemples mais ne rien imposer ?
5. **Assistant de configuration** - Aider l'utilisateur sans le forcer ?
6. **Services optionnels** - Créer HonoraireCalculator SEULEMENT si l'utilisateur veut automatiser ?

---

## 🎯 Résumé de la liberté utilisateur

### Ce que l'utilisateur PEUT faire (mais n'est pas obligé) :

✅ Créer une catégorie "Salaires" avec sous-catégorie "Honoraires"
✅ Créer une catégorie "Rémunérations" avec sous-catégorie "Cours"
✅ Créer une catégorie "Personnel" avec sous-catégorie "Enseignants"
✅ Ou n'importe quelle autre structure

✅ Créer un type de récurrence "Mensuelle"
✅ Créer un type "Bi-hebdomadaire"
✅ Créer un type "Chaque trimestre"

✅ Lier une dépense à un enseignant (beneficiaire)
✅ Lier une dépense à un fournisseur
✅ Ne lier à personne (dépense générale)

✅ Stocker heures_effectuees dans metadata
✅ Stocker surface_m2 dans metadata
✅ Stocker n'importe quoi dans metadata

✅ Utiliser HonoraireCalculator pour auto-calculer
✅ Créer manuellement sans service

### Ce que le système NE FAIT PAS :

❌ Imposer une catégorie "Honoraires"
❌ Forcer une structure de catégories
❌ Obliger à utiliser les types de récurrence
❌ Présumer que l'utilisateur veut gérer des honoraires
❌ Hardcoder quoi que ce soit

---

**Vous êtes d'accord avec cette approche ?** 🚀
