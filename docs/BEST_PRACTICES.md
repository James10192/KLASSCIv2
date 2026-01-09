# Best Practices 2025 - KLASSCI

**Dernière mise à jour**: 17 décembre 2024
**Stack**: Laravel 9.x/10.x | PHP 8.x (7.4/8.0/8.1/8.2) | MySQL 8.x
**Sources**: Recherches officielles Laravel, PHP, MySQL (Décembre 2024)

---

## Table des Matières

1. [Laravel 9/10 Best Practices](#1-laravel-910-best-practices)
2. [PHP 8.x Features & Best Practices](#2-php-8x-features--best-practices)
3. [MySQL 8.x Optimization](#3-mysql-8x-optimization)
4. [Frontend (Blade + Alpine.js)](#4-frontend-blade--alpinejs)
5. [Architecture SaaS Multi-Tenant](#5-architecture-saas-multi-tenant)
6. [Sécurité](#6-sécurité)
7. [Performance](#7-performance)
8. [Testing](#8-testing)
9. [Documentation Code](#9-documentation-code)
10. [Workflow Git](#10-workflow-git)

---

## 1. Laravel 9/10 Best Practices

### 1.1 Fat Models, Skinny Controllers

**Principe**: La logique métier doit résider dans les **Models** et **Services**, pas dans les Controllers.

**✅ FAIRE: Controller léger**
```php
// app/Http/Controllers/ESBTPInscriptionController.php
public function store(InscriptionRequest $request)
{
    $inscription = $this->inscriptionService->createInscription($request->validated());

    return redirect()
        ->route('esbtp.inscriptions.show', $inscription)
        ->with('success', 'Inscription créée avec succès');
}
```

**❌ NE PAS FAIRE: Controller avec logique métier**
```php
public function store(Request $request)
{
    // ❌ Validation dans controller
    $validated = $request->validate([...]);

    // ❌ Logique métier complexe dans controller
    $etudiant = ESBTPEtudiant::create([...]);
    $inscription = ESBTPInscription::create([...]);

    // ❌ Calcul de frais dans controller
    $montantTotal = 0;
    foreach ($fraisCategories as $cat) {
        $montantTotal += $cat->amount;
    }

    // ❌ Notifications dans controller
    Mail::to($etudiant->email)->send(new InscriptionCreated($inscription));

    return redirect()->back();
}
```

**Pattern recommandé**:
- **Controllers**: Réception requêtes, appel services, retour réponses
- **Services**: Logique métier, orchestration, transactions
- **Models**: Relations, scopes, accesseurs, mutateurs
- **Form Requests**: Validation

---

### 1.2 Service Layer Architecture (MVCS)

**Architecture recommandée**: Model-View-Controller-Service

**Structure du Service:**
```php
// app/Services/InscriptionWorkflowService.php
namespace App\Services;

use App\Models\ESBTPInscription;
use App\Models\ESBTPEtudiant;
use Illuminate\Support\Facades\DB;

class InscriptionWorkflowService
{
    public function createInscription(array $data): ESBTPInscription
    {
        return DB::transaction(function () use ($data) {
            // 1. Créer/récupérer étudiant
            $etudiant = $this->getOrCreateEtudiant($data['etudiant']);

            // 2. Créer inscription
            $inscription = ESBTPInscription::create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $data['classe_id'],
                'annee_academique' => $data['annee_academique'],
                'status' => 'en_attente',
            ]);

            // 3. Générer matricule
            $etudiant->matricule = $this->matriculeService->generate($etudiant);
            $etudiant->save();

            // 4. Créer souscriptions frais
            $this->createFraisSubscriptions($inscription, $data['frais']);

            // 5. Envoyer notification
            $this->notificationService->sendInscriptionCreated($inscription);

            return $inscription->fresh(['etudiant', 'classe']);
        });
    }

    private function getOrCreateEtudiant(array $data): ESBTPEtudiant
    {
        // Logique de détection doublons (fuzzy search)
        // ...
    }

    private function createFraisSubscriptions(ESBTPInscription $inscription, array $frais): void
    {
        // Logique de souscription frais
        // ...
    }
}
```

**Avantages:**
- Réutilisabilité (appel depuis controller, commande Artisan, job)
- Testabilité (tests unitaires sur services)
- Séparation des responsabilités
- Transactions groupées

---

### 1.3 Prevent N+1 Queries avec Eager Loading

**Problème**: Le N+1 query problem est l'une des principales causes de lenteur Laravel.

**✅ FAIRE: Eager Loading**
```php
// app/Http/Controllers/ESBTPClasseController.php
public function index()
{
    $classes = ESBTPClasse::with([
            'filiere',
            'niveau',
            'etudiants' => function ($query) {
                $query->whereHas('inscriptions', function ($q) {
                    $q->where('annee_academique', config('app.current_year'))
                      ->where('status', 'validée');
                });
            }
        ])
        ->withCount(['etudiants as etudiants_actifs_count' => function ($query) {
            $query->whereHas('inscriptions', function ($q) {
                $q->where('annee_academique', config('app.current_year'))
                  ->where('status', 'validée');
            });
        }])
        ->get();

    return view('esbtp.classes.index', compact('classes'));
}
```

**❌ NE PAS FAIRE: Lazy Loading dans boucle**
```php
public function index()
{
    $classes = ESBTPClasse::all(); // 1 requête

    // ❌ Dans la vue: N requêtes supplémentaires
    @foreach($classes as $classe)
        {{ $classe->filiere->nom }} <!-- +1 requête -->
        {{ $classe->niveau->nom }} <!-- +1 requête -->
        {{ $classe->etudiants->count() }} <!-- +1 requête -->
    @endforeach
}
```

**Forcer la détection en développement:**
```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if ($this->app->environment('local')) {
        Model::preventLazyLoading();
    }
}
```

**Résultat**: Exception levée si lazy loading détecté en dev.

---

### 1.4 Utiliser les Built-in Features Laravel

**Validation:**
```php
// ✅ Form Request Classes
php artisan make:request StoreInscriptionRequest

// app/Http/Requests/StoreInscriptionRequest.php
public function rules(): array
{
    return [
        'etudiant.nom' => 'required|string|max:100',
        'etudiant.prenoms' => 'required|string|max:150',
        'etudiant.email_personnel' => 'nullable|email|unique:esbtp_etudiants,email_personnel',
        'classe_id' => 'required|exists:esbtp_classes,id',
        'frais' => 'required|array|min:1',
        'frais.*.category_id' => 'required|exists:esbtp_frais_categories,id',
        'frais.*.amount' => 'required|numeric|min:0',
    ];
}

public function messages(): array
{
    return [
        'etudiant.nom.required' => 'Le nom de l\'étudiant est obligatoire',
        'frais.required' => 'Au moins une catégorie de frais doit être sélectionnée',
    ];
}
```

**Eloquent Collections:**
```php
// ✅ Utiliser les méthodes collections
$paiementsValides = $inscription->paiements
    ->where('status', 'validé')
    ->sum('montant');

$moyennesSupA10 = $etudiant->resultats
    ->where('moyenne', '>', 10)
    ->pluck('matiere.nom');

// ✅ Grouping
$paiementsParMode = $paiements->groupBy('mode_paiement')
    ->map(fn($group) => [
        'count' => $group->count(),
        'total' => $group->sum('montant'),
    ]);
```

**Query Scopes:**
```php
// app/Models/ESBTPInscription.php
public function scopeValidees($query)
{
    return $query->where('status', 'validée');
}

public function scopeAnneeEnCours($query)
{
    return $query->where('annee_academique', config('app.current_year'));
}

public function scopeParClasse($query, $classeId)
{
    return $query->where('classe_id', $classeId);
}

// Usage
$inscriptions = ESBTPInscription::validees()
    ->anneeEnCours()
    ->parClasse($classeId)
    ->with('etudiant', 'paiements')
    ->get();
```

**Resource Controllers:**
```php
// routes/web.php
Route::resource('esbtp.classes', ESBTPClasseController::class)
    ->names('esbtp.classes');

// Génère automatiquement:
// GET    /esbtp/classes           -> index
// GET    /esbtp/classes/create    -> create
// POST   /esbtp/classes           -> store
// GET    /esbtp/classes/{id}      -> show
// GET    /esbtp/classes/{id}/edit -> edit
// PUT    /esbtp/classes/{id}      -> update
// DELETE /esbtp/classes/{id}      -> destroy
```

---

### 1.5 Caching Stratégique

**Cache des requêtes lourdes:**
```php
// app/Services/DashboardStatsService.php
public function getStatsGlobales()
{
    return Cache::remember('stats_globales', now()->addMinutes(10), function () {
        return [
            'total_etudiants' => ESBTPEtudiant::whereHas('inscriptions', function ($q) {
                $q->anneeEnCours()->validees();
            })->count(),

            'total_classes' => ESBTPClasse::active()->count(),

            'montant_paiements_mois' => ESBTPPaiement::where('status', 'validé')
                ->whereMonth('date_paiement', now()->month)
                ->sum('montant'),

            'taux_presence_moyen' => $this->calculateTauxPresenceMoyen(),
        ];
    });
}
```

**Invalidation cache:**
```php
// app/Observers/InscriptionObserver.php
public function created(ESBTPInscription $inscription)
{
    Cache::forget('stats_globales');
    Cache::forget("classe_{$inscription->classe_id}_stats");
}

public function updated(ESBTPInscription $inscription)
{
    if ($inscription->wasChanged('status')) {
        Cache::forget('stats_globales');
    }
}
```

**Tags pour invalidation groupée:**
```php
// Avec driver Redis/Memcached
Cache::tags(['stats', 'dashboard'])->put('key', $value, $seconds);

// Invalider tout le groupe
Cache::tags(['stats'])->flush();
```

---

### 1.6 Jobs & Queues pour Tâches Longues

**Créer un job:**
```php
php artisan make:job GenerateBulletinPDF

// app/Jobs/GenerateBulletinPDF.php
class GenerateBulletinPDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ESBTPInscription $inscription,
        public string $periode
    ) {}

    public function handle(BulletinService $bulletinService)
    {
        $pdf = $bulletinService->generate($this->inscription, $this->periode);

        // Stocker PDF
        $path = "bulletins/{$this->inscription->id}/{$this->periode}.pdf";
        Storage::put($path, $pdf->output());

        // Notifier
        $this->inscription->etudiant->user->notify(
            new BulletinGenerated($this->inscription, $path)
        );
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('Échec génération bulletin', [
            'inscription_id' => $this->inscription->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**Dispatch du job:**
```php
// Controller
GenerateBulletinPDF::dispatch($inscription, $periode)
    ->onQueue('bulletins');

// Dispatch conditionnel
GenerateBulletinPDF::dispatchIf(
    $inscription->hasMoyennes(),
    $inscription,
    $periode
);

// Dispatch différé
GenerateBulletinPDF::dispatch($inscription, $periode)
    ->delay(now()->addMinutes(5));
```

**Configuration worker:**
```bash
# Démarrer worker
php artisan queue:work --queue=bulletins,emails,default --tries=3

# Horizon (recommandé pour production)
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

---

## 2. PHP 8.x Features & Best Practices

### 2.1 Readonly Properties (PHP 8.1+)

**Principe**: Immutabilité pour les propriétés définies une seule fois.

**✅ FAIRE: Readonly properties**
```php
// app/Services/MatriculeGenerator.php
class MatriculeGenerator
{
    public function __construct(
        private readonly string $prefix,
        private readonly string $etablissementCode,
        private readonly int $numeroDigits = 4,
    ) {}

    public function generate(ESBTPEtudiant $etudiant): string
    {
        // $this->prefix est garanti non modifiable
        $annee = now()->format('y');
        $numero = $this->getProchainNumero();

        return "{$this->prefix}{$this->etablissementCode}{$annee}-" .
               str_pad($numero, $this->numeroDigits, '0', STR_PAD_LEFT);
    }
}
```

**Avantages:**
- Protection contre modifications accidentelles
- Clarté d'intention (valeur fixe après __construct)
- Pas besoin de getters

---

### 2.2 Readonly Classes (PHP 8.2+)

**Toutes les propriétés sont readonly:**
```php
// app/DataTransferObjects/InscriptionData.php
readonly class InscriptionData
{
    public function __construct(
        public int $etudiantId,
        public int $classeId,
        public string $anneeAcademique,
        public array $fraisCategories,
        public ?string $observations = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            etudiantId: $request->integer('etudiant_id'),
            classeId: $request->integer('classe_id'),
            anneeAcademique: $request->string('annee_academique'),
            fraisCategories: $request->array('frais'),
            observations: $request->string('observations', null),
        );
    }
}

// Usage
$data = InscriptionData::fromRequest($request);
$inscription = $this->inscriptionService->create($data);
```

---

### 2.3 Enums (PHP 8.1+)

**Remplacer les constantes par Enums:**

**✅ FAIRE: Backed Enums**
```php
// app/Enums/InscriptionStatus.php
enum InscriptionStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDEE = 'validée';
    case REJETEE = 'rejetée';
    case ANNULEE = 'annulée';

    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE => 'Validée',
            self::REJETEE => 'Rejetée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warning',
            self::VALIDEE => 'success',
            self::REJETEE => 'danger',
            self::ANNULEE => 'secondary',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::EN_ATTENTE => in_array($newStatus, [self::VALIDEE, self::REJETEE]),
            self::VALIDEE => $newStatus === self::ANNULEE,
            default => false,
        };
    }
}

// app/Models/ESBTPInscription.php
protected $casts = [
    'status' => InscriptionStatus::class,
];

// Usage
$inscription->status = InscriptionStatus::VALIDEE;
$inscription->save();

// Dans Blade
<span class="badge bg-{{ $inscription->status->badgeColor() }}">
    {{ $inscription->status->label() }}
</span>

// Validation transition
if (!$currentStatus->canTransitionTo($newStatus)) {
    throw new InvalidStatusTransitionException();
}
```

**❌ NE PAS FAIRE: Constantes string**
```php
// ❌ Ancien pattern
class ESBTPInscription
{
    const STATUS_EN_ATTENTE = 'en_attente';
    const STATUS_VALIDEE = 'validée';

    // ❌ Erreur typo possible
    $inscription->status = 'validé'; // Pas détecté
}
```

---

### 2.4 Match Expressions

**Remplacer switch par match:**

**✅ FAIRE: Match expression**
```php
// app/Services/FraisCalculationService.php
public function calculateMontantTotal(ESBTPInscription $inscription): int
{
    $niveau = $inscription->classe->niveau;

    return match ($niveau->code) {
        'L1', 'L2' => 150000,
        'L3', 'M1' => 200000,
        'M2' => 250000,
        default => throw new InvalidArgumentException("Niveau inconnu: {$niveau->code}"),
    };
}

// Plus concis qu'un switch
public function getModePaiementIcon(string $mode): string
{
    return match ($mode) {
        'Espèces' => 'fa-money-bill',
        'Chèque' => 'fa-money-check',
        'Virement bancaire' => 'fa-university',
        'Mobile Money' => 'fa-mobile-alt',
        default => 'fa-question-circle',
    };
}
```

**Avantages match vs switch:**
- Retourne une valeur (expression vs statement)
- Comparaison stricte (`===` automatique)
- Pas de `break` nécessaire
- Exception si aucun cas ne match (sauf `default`)

---

### 2.5 Named Arguments

**Clarté des appels de fonction:**
```php
// ✅ Lisible
$bulletin = $bulletinService->generate(
    inscription: $inscription,
    periode: 'semestre1',
    includeProfesseurs: true,
    includeAbsences: false,
    format: 'pdf'
);

// ❌ Moins clair
$bulletin = $bulletinService->generate($inscription, 'semestre1', true, false, 'pdf');
```

**Skip des paramètres optionnels:**
```php
public function createPaiement(
    int $inscriptionId,
    int $montant,
    string $modePaiement,
    ?string $reference = null,
    ?string $observations = null,
    bool $sendNotification = true
) {}

// Utilisation
$paiement = $this->createPaiement(
    inscriptionId: $inscription->id,
    montant: 50000,
    modePaiement: 'Espèces',
    sendNotification: false // Skip reference et observations
);
```

---

### 2.6 Nullsafe Operator

**Éviter les null checks imbriqués:**
```php
// ✅ Nullsafe operator
$nomEnseignant = $evaluation?->enseignant?->nom;
$emailParent = $etudiant?->parents?->first()?->email;

// ❌ Ancien pattern
$nomEnseignant = null;
if ($evaluation && $evaluation->enseignant) {
    $nomEnseignant = $evaluation->enseignant->nom;
}
```

---

### 2.7 Type Declarations Strictes

**Activer le mode strict:**
```php
<?php
declare(strict_types=1);

namespace App\Services;

class BulletinService
{
    // ✅ Types stricts sur paramètres et retour
    public function calculateMoyenne(
        array $notes,
        array $coefficients
    ): float {
        $sommeNotes = 0;
        $sommeCoefficients = 0;

        foreach ($notes as $i => $note) {
            $sommeNotes += $note * $coefficients[$i];
            $sommeCoefficients += $coefficients[$i];
        }

        return $sommeCoefficients > 0
            ? $sommeNotes / $sommeCoefficients
            : 0.0;
    }
}
```

**Union Types (PHP 8.0+):**
```php
public function findByMatriculeOrId(string|int $identifier): ?ESBTPEtudiant
{
    if (is_int($identifier)) {
        return ESBTPEtudiant::find($identifier);
    }

    return ESBTPEtudiant::where('matricule', $identifier)->first();
}
```

---

## 3. MySQL 8.x Optimization

### 3.1 Indexing Strategies

**Indexes composites pour requêtes fréquentes:**

```php
// database/migrations/xxxx_optimize_inscriptions_table.php
Schema::table('esbtp_inscriptions', function (Blueprint $table) {
    // Index composite pour filtres dashboard
    $table->index(['annee_academique', 'status', 'classe_id'], 'idx_inscriptions_dashboard');

    // Index pour tri par date
    $table->index(['created_at', 'status']);

    // Index pour search
    $table->index(['matricule']);
});

Schema::table('esbtp_paiements', function (Blueprint $table) {
    // Index composite pour stats comptables
    $table->index(['inscription_id', 'status', 'date_paiement'], 'idx_paiements_stats');

    // Index pour agrégations
    $table->index(['frais_category_id', 'status', 'montant']);
});
```

**Règles indexing:**
- Index les colonnes utilisées dans `WHERE`, `JOIN`, `ORDER BY`
- Index composites: ordre = colonnes les plus sélectives en premier
- Éviter trop d'indexes (ralentit `INSERT`/`UPDATE`)
- Monitorer avec `EXPLAIN`

**Analyser performance requête:**
```php
// Dans Tinker ou test
DB::enableQueryLog();

$inscriptions = ESBTPInscription::where('annee_academique', '2024-2025')
    ->where('status', 'validée')
    ->orderBy('created_at', 'desc')
    ->get();

dd(DB::getQueryLog());

// Vérifier dans MySQL
EXPLAIN SELECT * FROM esbtp_inscriptions
WHERE annee_academique = '2024-2025'
  AND status = 'validée'
ORDER BY created_at DESC;
```

---

### 3.2 JSON Columns & Indexes

**Colonnes JSON pour données flexibles:**
```php
// Migration
Schema::table('esbtp_bulletins', function (Blueprint $table) {
    $table->json('professeurs')->nullable();
    $table->json('absences')->nullable();

    // Index sur chemin JSON (MySQL 8.0+)
    $table->rawIndex(
        "(CAST(professeurs->'$.matieres[*].enseignant_id' AS UNSIGNED ARRAY))",
        'idx_bulletins_enseignants'
    );
});

// Model cast
protected $casts = [
    'professeurs' => 'array',
    'absences' => 'array',
];

// Query JSON path
$bulletins = ESBTPBulletin::whereJsonContains('professeurs->matieres', [
    ['matiere_id' => 5, 'enseignant_id' => 12]
])->get();

// Générer colonne virtuelle pour performance
Schema::table('esbtp_bulletins', function (Blueprint $table) {
    $table->integer('nb_matieres')
          ->virtualAs("JSON_LENGTH(professeurs->'$.matieres')")
          ->index();
});
```

**Quand utiliser JSON:**
- ✅ Données schema flexible (professeurs par matière, configurations)
- ✅ Audit logs, metadata
- ❌ Données à requêter fréquemment (créer table dédiée)

---

### 3.3 Optimisation Requêtes Agrégation

**Utiliser MySQL 8 Window Functions:**
```sql
-- Rang étudiants par classe
SELECT
    e.id,
    e.nom,
    e.prenoms,
    r.moyenne,
    RANK() OVER (PARTITION BY i.classe_id ORDER BY r.moyenne DESC) as rang_classe
FROM esbtp_etudiants e
JOIN esbtp_inscriptions i ON e.id = i.etudiant_id
JOIN esbtp_resultats r ON r.etudiant_id = e.id
WHERE i.annee_academique = '2024-2025';
```

**Laravel Query Builder (MySQL 8.0.2+):**
```php
$rankedStudents = DB::table('esbtp_etudiants as e')
    ->join('esbtp_inscriptions as i', 'e.id', '=', 'i.etudiant_id')
    ->join('esbtp_resultats as r', 'r.etudiant_id', '=', 'e.id')
    ->select([
        'e.id',
        'e.nom',
        'e.prenoms',
        'r.moyenne',
        DB::raw('RANK() OVER (PARTITION BY i.classe_id ORDER BY r.moyenne DESC) as rang_classe')
    ])
    ->where('i.annee_academique', config('app.current_year'))
    ->get();
```

---

### 3.4 Connection Pooling

**Configuration production:**
```env
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=klassci_production
DB_USERNAME=klassci_user
DB_PASSWORD=secure_password

# Pool connections
DB_MAX_CONNECTIONS=100
DB_IDLE_TIMEOUT=60
```

**Config Laravel:**
```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => [
        PDO::ATTR_PERSISTENT => true, // Connection pooling
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ],
],
```

**Pourquoi `ATTR_PERSISTENT` ?**
- Réutilise connexions existantes au lieu d'en créer de nouvelles
- Réduit latence (~50ms économisés par requête)
- Attention: peut causer memory leaks si mal configuré

---

## 4. Frontend (Blade + Alpine.js)

### 4.1 Blade Components Réutilisables

**Créer composants:**
```bash
php artisan make:component StatCard
```

**Component class:**
```php
// app/View/Components/StatCard.php
namespace App\View\Components;

use Illuminate\View\Component;

class StatCard extends Component
{
    public function __construct(
        public string $title,
        public string $value,
        public string $icon,
        public string $color = 'primary',
        public ?string $subtitle = null,
        public ?string $trend = null,
    ) {}

    public function render()
    {
        return view('components.stat-card');
    }
}
```

**Template:**
```blade
{{-- resources/views/components/stat-card.blade.php --}}
<div class="card border-start border-4 border-{{ $color }} shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="fas {{ $icon }} fa-2x text-{{ $color }}"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="text-muted small text-uppercase fw-bold">{{ $title }}</div>
                <div class="h3 mb-0">{{ $value }}</div>
                @if($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @if($trend)
                <div class="badge bg-{{ $trend > 0 ? 'success' : 'danger' }}">
                    <i class="fas fa-arrow-{{ $trend > 0 ? 'up' : 'down' }}"></i>
                    {{ abs($trend) }}%
                </div>
            @endif
        </div>
    </div>
</div>
```

**Usage:**
```blade
<x-stat-card
    title="Total Étudiants"
    :value="$stats['total_etudiants']"
    icon="fa-users"
    color="primary"
    subtitle="Inscrits cette année"
    :trend="5.2"
/>
```

---

### 4.2 Alpine.js Best Practices

**Pattern: Dropdown avec état:**
```blade
<div x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open" class="btn btn-primary">
        Actions <i class="fas fa-chevron-down"></i>
    </button>

    <div x-show="open"
         x-transition
         class="dropdown-menu position-absolute"
         style="display: none;">
        <a href="#" class="dropdown-item">Modifier</a>
        <a href="#" class="dropdown-item">Supprimer</a>
    </div>
</div>
```

**Pattern: Tabs sans JavaScript vanilla:**
```blade
<div x-data="{ activeTab: 'infos' }">
    {{-- Tab Headers --}}
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a @click.prevent="activeTab = 'infos'"
               :class="{'active': activeTab === 'infos'}"
               class="nav-link"
               href="#">
                Informations
            </a>
        </li>
        <li class="nav-item">
            <a @click.prevent="activeTab = 'paiements'"
               :class="{'active': activeTab === 'paiements'}"
               class="nav-link"
               href="#">
                Paiements
            </a>
        </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content mt-3">
        <div x-show="activeTab === 'infos'" class="tab-pane">
            {{-- Contenu infos --}}
        </div>
        <div x-show="activeTab === 'paiements'" class="tab-pane">
            {{-- Contenu paiements --}}
        </div>
    </div>
</div>
```

**Pattern: Modal avec Alpine:**
```blade
<div x-data="{ showModal: false }">
    <button @click="showModal = true" class="btn btn-primary">
        Ouvrir Modal
    </button>

    <div x-show="showModal"
         x-transition.opacity
         class="modal-backdrop"
         @click="showModal = false">
        <div @click.stop class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Titre Modal</h5>
                    <button @click="showModal = false" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    Contenu
                </div>
            </div>
        </div>
    </div>
</div>
```

**AJAX avec Alpine + Fetch:**
```blade
<div x-data="{
    loading: false,
    results: [],
    async search(query) {
        this.loading = true;

        const response = await fetch(`/api/etudiants/search?q=${query}`);
        this.results = await response.json();

        this.loading = false;
    }
}">
    <input type="text"
           @input.debounce.500ms="search($event.target.value)"
           placeholder="Rechercher un étudiant...">

    <div x-show="loading">Chargement...</div>

    <ul>
        <template x-for="result in results" :key="result.id">
            <li x-text="result.nom + ' ' + result.prenoms"></li>
        </template>
    </ul>
</div>
```

---

### 4.3 DataTables Optimization

**Initialisation optimisée:**
```javascript
$(document).ready(function() {
    $('#etudiants-table').DataTable({
        processing: true,
        serverSide: true, // ✅ Server-side pour grandes tables
        ajax: '{{ route("esbtp.etudiants.datatable") }}',
        columns: [
            { data: 'matricule', name: 'matricule' },
            { data: 'nom', name: 'nom' },
            { data: 'prenoms', name: 'prenoms' },
            { data: 'classe.nom', name: 'classe.nom' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json'
        },
        pageLength: 25,
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
        order: [[0, 'desc']], // Tri par matricule desc
        drawCallback: function() {
            // Réactiver tooltips Bootstrap après render
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
});
```

**Backend DataTable:**
```php
// app/Http/Controllers/ESBTPEtudiantController.php
public function datatable(Request $request)
{
    $query = ESBTPEtudiant::with('classe')
        ->whereHas('inscriptions', function ($q) {
            $q->where('annee_academique', config('app.current_year'));
        });

    return DataTables::of($query)
        ->addColumn('actions', function ($etudiant) {
            return view('esbtp.etudiants.partials.actions', compact('etudiant'))->render();
        })
        ->editColumn('classe.nom', function ($etudiant) {
            return $etudiant->classe->nom ?? 'Non assigné';
        })
        ->rawColumns(['actions'])
        ->make(true);
}
```

---

## 5. Architecture SaaS Multi-Tenant

### 5.1 Database-per-Tenant Strategy

**KLASSCI utilise l'isolation complète par base de données.**

**Avantages:**
- ✅ Isolation totale des données (sécurité maximale)
- ✅ Scalabilité horizontale (1 BDD = 1 serveur si besoin)
- ✅ Backup/Restore par tenant
- ✅ Compliance RGPD facile

**Inconvénients:**
- ❌ Migrations complexes (exécuter sur chaque BDD)
- ❌ Coût serveur plus élevé

**Configuration Laravel Tenancy:**
```php
// config/tenancy.php
return [
    'tenant_database_prefix' => '',
    'tenant_database_suffix' => '',

    'database' => [
        'based_on' => env('DB_CONNECTION', 'mysql'),
        'suffix' => '',
        'prefix' => '',
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
    ],
];
```

**Commande provisioning:**
```bash
php artisan tenant:provision \
    --code=esbtp-cocody \
    --name="ESBTP Cocody" \
    --plan=pro \
    --email=admin@esbtp-cocody.ci
```

---

### 5.2 Tenant Identification

**Stratégie: Subdomain-based**

**Middleware tenant resolution:**
```php
// app/Http/Middleware/InitializeTenancyByDomain.php
public function handle(Request $request, Closure $next)
{
    $subdomain = explode('.', $request->getHost())[0];

    if ($subdomain === 'www' || $subdomain === config('app.domain')) {
        return $next($request); // Master domain
    }

    $tenant = Tenant::where('code', $subdomain)->firstOrFail();

    tenancy()->initialize($tenant);

    config(['app.name' => $tenant->name]);
    config(['app.current_year' => $tenant->annee_academique_active]);

    return $next($request);
}
```

**Routes tenant-aware:**
```php
// routes/tenant.php (chargé seulement si tenant identifié)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::resource('esbtp.classes', ESBTPClasseController::class);
    // ...
});
```

---

### 5.3 Shared vs Tenant Models

**Central Models (Master DB):**
```php
// app/Models/Tenant.php
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $fillable = [
        'id', 'code', 'name', 'email', 'plan', 'database',
        'domain', 'annee_academique_active', 'settings'
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function isPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }
}
```

**Tenant Models (Tenant DB):**
```php
// app/Models/ESBTPEtudiant.php - Automatiquement dans BDD tenant
class ESBTPEtudiant extends Model
{
    // Pas de config spéciale, utilise la connexion tenant active

    protected $table = 'esbtp_etudiants';

    public function inscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'etudiant_id');
    }
}
```

---

### 5.4 Migrations Tenant

**Structure migrations:**
```
database/migrations/
├── tenant/          # Migrations tenant (run sur chaque BDD)
│   ├── 2024_01_01_create_esbtp_classes_table.php
│   ├── 2024_01_02_create_esbtp_etudiants_table.php
│   └── ...
└── landlord/        # Migrations master (run 1x)
    ├── 2024_01_01_create_tenants_table.php
    └── ...
```

**Exécuter migrations:**
```bash
# Master DB
php artisan migrate --path=database/migrations/landlord

# Toutes les BDD tenant
php artisan tenants:migrate

# 1 seul tenant
php artisan tenants:migrate --tenants=esbtp-abidjan
```

---

## 6. Sécurité

### 6.1 OWASP Top 10 Protection

**1. SQL Injection → Eloquent ORM**
```php
// ✅ Eloquent protège automatiquement
$etudiants = ESBTPEtudiant::where('nom', $request->nom)->get();

// ✅ Bindings manuels si raw query nécessaire
$results = DB::select('SELECT * FROM esbtp_etudiants WHERE nom = ?', [$nom]);

// ❌ JAMAIS de concaténation directe
$results = DB::select("SELECT * FROM esbtp_etudiants WHERE nom = '$nom'");
```

**2. XSS → Blade Escaping**
```blade
{{-- ✅ Échappement automatique --}}
{{ $etudiant->nom }}

{{-- ✅ Pour HTML trusted --}}
{!! $bulletin->html_content !!}

{{-- ❌ Ne JAMAIS faire --}}
<?php echo $etudiant->nom; ?>
```

**3. CSRF → Tokens automatiques**
```blade
{{-- ✅ CSRF token dans tous les forms --}}
<form method="POST" action="{{ route('esbtp.paiements.store') }}">
    @csrf
    {{-- ... --}}
</form>

{{-- ✅ Pour DELETE/PUT --}}
<form method="POST">
    @csrf
    @method('DELETE')
</form>
```

**4. Broken Access Control → Policies**
```php
// app/Policies/InscriptionPolicy.php
class InscriptionPolicy
{
    public function view(User $user, ESBTPInscription $inscription): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('secretaire')
            || ($user->hasRole('etudiant') && $user->etudiant_id === $inscription->etudiant_id);
    }

    public function update(User $user, ESBTPInscription $inscription): bool
    {
        return $user->hasRole('admin') || $user->hasRole('secretaire');
    }

    public function delete(User $user, ESBTPInscription $inscription): bool
    {
        return $user->hasRole('admin');
    }
}

// Controller
public function update(Request $request, ESBTPInscription $inscription)
{
    $this->authorize('update', $inscription);

    // ...
}

// Blade
@can('update', $inscription)
    <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}">Modifier</a>
@endcan
```

---

### 6.2 Rate Limiting

**Throttle routes sensibles:**
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        // ...
    ],
];

// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/etudiants/search', [EtudiantController::class, 'search']);
});

// Login rate limit
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 tentatives par minute
```

**Custom rate limiter:**
```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('uploads', function (Request $request) {
        return $request->user()?->hasRole('admin')
            ? Limit::none()
            : Limit::perMinute(10)->by($request->user()->id);
    });
}
```

---

### 6.3 Mass Assignment Protection

**Toujours définir $fillable ou $guarded:**
```php
// app/Models/ESBTPEtudiant.php
class ESBTPEtudiant extends Model
{
    // ✅ Whitelist approach (recommandé)
    protected $fillable = [
        'nom', 'prenoms', 'email_personnel', 'telephone',
        'date_naissance', 'lieu_naissance', 'matricule'
    ];

    // ❌ JAMAIS faire
    // protected $guarded = [];

    // ✅ Blacklist approach si besoin
    protected $guarded = [
        'id', 'created_at', 'updated_at', 'deleted_at'
    ];
}

// Controller
public function store(Request $request)
{
    // ✅ Seulement les champs fillable sont assignés
    $etudiant = ESBTPEtudiant::create($request->validated());

    // ❌ Dangereux si $guarded = []
    // $etudiant = ESBTPEtudiant::create($request->all());
}
```

---

### 6.4 Sensitive Data Protection

**Hasher les mots de passe:**
```php
// app/Models/User.php
protected $hidden = [
    'password',
    'remember_token',
];

protected $casts = [
    'password' => 'hashed', // Laravel 10+ - hash automatique
];

// Controller
$user->password = $request->password; // Automatiquement hashé
```

**Chiffrer données sensibles:**
```php
// app/Models/ESBTPParent.php
protected $casts = [
    'telephone' => 'encrypted',
    'adresse' => 'encrypted',
];

// Stocké chiffré en BDD, déchiffré automatiquement
$parent = ESBTPParent::find(1);
echo $parent->telephone; // Déchiffré
```

---

## 7. Performance

### 7.1 Éviter N+1 Queries (Rappel Important)

**Pattern le plus fréquent:**
```php
// ❌ N+1 Query Problem
public function index()
{
    $classes = ESBTPClasse::all(); // 1 query

    return view('classes.index', compact('classes'));
}

// Blade: @foreach($classes as $classe)
//   {{ $classe->filiere->nom }}      // +1 query
//   {{ $classe->etudiants->count() }} // +1 query
// @endforeach

// ✅ Solution: Eager Loading
public function index()
{
    $classes = ESBTPClasse::with(['filiere', 'niveau'])
        ->withCount('etudiants')
        ->get(); // 2-3 queries total

    return view('classes.index', compact('classes'));
}
```

**Charger relations conditionnelles:**
```php
// Charger seulement si nécessaire
$inscriptions = ESBTPInscription::query()
    ->when($request->include_paiements, function ($q) {
        $q->with('paiements');
    })
    ->when($request->include_etudiant, function ($q) {
        $q->with('etudiant.parents');
    })
    ->get();
```

---

### 7.2 Database Query Optimization

**Sélectionner seulement les colonnes nécessaires:**
```php
// ❌ Charge tout
$etudiants = ESBTPEtudiant::all();

// ✅ Sélection spécifique
$etudiants = ESBTPEtudiant::select(['id', 'nom', 'prenoms', 'matricule'])
    ->get();

// ✅ Éviter SELECT * dans joins
$results = DB::table('esbtp_inscriptions as i')
    ->join('esbtp_etudiants as e', 'i.etudiant_id', '=', 'e.id')
    ->select([
        'i.id',
        'e.nom',
        'e.prenoms',
        'i.status'
    ])
    ->get();
```

**Chunk pour grandes datasets:**
```php
// ✅ Process par batch de 100
ESBTPEtudiant::chunk(100, function ($etudiants) {
    foreach ($etudiants as $etudiant) {
        // Process
    }
});

// ✅ Lazy collections pour streaming
ESBTPEtudiant::lazy()->each(function ($etudiant) {
    // Process 1 par 1 sans charger tout en mémoire
});
```

---

### 7.3 View Caching

**Compiler vues en production:**
```bash
# Pré-compiler toutes les vues Blade
php artisan view:cache

# Clear cache vues
php artisan view:clear
```

**Config optimisée production:**
```bash
# Optimiser autoload Composer
composer install --optimize-autoloader --no-dev

# Cache routes
php artisan route:cache

# Cache config
php artisan config:cache

# Cache events
php artisan event:cache
```

---

### 7.4 Redis Caching

**Configuration Redis:**
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Cache tags pour invalidation groupée:**
```php
// Stocker avec tags
Cache::tags(['stats', 'classes'])->put('classes_count', $count, 3600);

// Invalider tout un groupe
Cache::tags(['stats'])->flush();

// Multiple tags
Cache::tags(['stats', 'dashboard', 'year_2024'])->flush();
```

---

## 8. Testing

### 8.1 Feature Tests

**Tester un workflow inscription:**
```php
// tests/Feature/InscriptionWorkflowTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ESBTPClasse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InscriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_inscription()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $classe = ESBTPClasse::factory()->create();

        $response = $this->actingAs($admin)->post(route('esbtp.inscriptions.store'), [
            'etudiant' => [
                'nom' => 'DOE',
                'prenoms' => 'John',
                'email_personnel' => 'john@example.com',
            ],
            'classe_id' => $classe->id,
            'annee_academique' => '2024-2025',
            'frais' => [
                ['category_id' => 1, 'amount' => 150000],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('esbtp_inscriptions', [
            'classe_id' => $classe->id,
            'status' => 'en_attente',
        ]);

        $this->assertDatabaseHas('esbtp_etudiants', [
            'nom' => 'DOE',
            'prenoms' => 'John',
        ]);
    }

    public function test_etudiant_cannot_create_inscription()
    {
        $etudiant = User::factory()->create();
        $etudiant->assignRole('etudiant');

        $response = $this->actingAs($etudiant)
            ->post(route('esbtp.inscriptions.store'), []);

        $response->assertForbidden();
    }
}
```

---

### 8.2 Unit Tests

**Tester service métier:**
```php
// tests/Unit/MatriculeGeneratorTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MatriculeGenerator;
use App\Models\ESBTPEtudiant;

class MatriculeGeneratorTest extends TestCase
{
    public function test_generates_correct_format()
    {
        $generator = new MatriculeGenerator(
            prefix: 'M',
            etablissementCode: 'ESBTP',
            numeroDigits: 4
        );

        $etudiant = ESBTPEtudiant::factory()->make([
            'genre' => 'M',
        ]);

        $matricule = $generator->generate($etudiant);

        // Format: MESBTP25-0001
        $this->assertMatchesRegularExpression(
            '/^MESBTP\d{2}-\d{4}$/',
            $matricule
        );
    }

    public function test_fills_gaps_in_sequence()
    {
        // Créer étudiants avec matricules: 0001, 0002, 0004
        ESBTPEtudiant::factory()->create(['matricule' => 'MESBTP25-0001']);
        ESBTPEtudiant::factory()->create(['matricule' => 'MESBTP25-0002']);
        ESBTPEtudiant::factory()->create(['matricule' => 'MESBTP25-0004']);

        $generator = new MatriculeGenerator('M', 'ESBTP', 4);
        $etudiant = ESBTPEtudiant::factory()->make(['genre' => 'M']);

        $matricule = $generator->generate($etudiant);

        // Devrait retourner 0003 (gap filling)
        $this->assertEquals('MESBTP25-0003', $matricule);
    }
}
```

---

### 8.3 Database Testing

**Factories pour données test:**
```php
// database/factories/ESBTPEtudiantFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPEtudiantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => strtoupper($this->faker->lastName),
            'prenoms' => $this->faker->firstName,
            'email_personnel' => $this->faker->unique()->safeEmail,
            'telephone' => $this->faker->phoneNumber,
            'date_naissance' => $this->faker->dateTimeBetween('-25 years', '-18 years'),
            'lieu_naissance' => $this->faker->city,
            'genre' => $this->faker->randomElement(['M', 'F']),
            'nationalite' => 'Ivoirienne',
            'statut' => 'actif',
        ];
    }

    public function withMatricule(): static
    {
        return $this->state(fn (array $attributes) => [
            'matricule' => 'M' . config('app.etablissement_code') .
                          now()->format('y') . '-' .
                          str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);
    }
}

// Usage dans test
$etudiant = ESBTPEtudiant::factory()->withMatricule()->create();
```

---

### 8.4 Browser Testing (Laravel Dusk)

**Tester parcours utilisateur complet:**
```php
// tests/Browser/InscriptionFlowTest.php
namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InscriptionFlowTest extends DuskTestCase
{
    public function test_complete_inscription_flow()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create()->assignRole('secretaire'))
                    ->visit('/esbtp/inscriptions/create')
                    ->type('etudiant[nom]', 'KOUASSI')
                    ->type('etudiant[prenoms]', 'Yao')
                    ->select('classe_id', 1)
                    ->check('frais[0][selected]')
                    ->press('Créer Inscription')
                    ->assertPathIs('/esbtp/inscriptions/*')
                    ->assertSee('Inscription créée avec succès')
                    ->assertSee('KOUASSI Yao');
        });
    }
}
```

---

## 9. Documentation Code

### 9.1 PHPDoc Standards

**Documenter méthodes complexes:**
```php
/**
 * Calcule la moyenne pondérée d'un étudiant pour une période donnée
 *
 * Cette méthode récupère toutes les notes de l'étudiant pour la période,
 * applique les coefficients des matières, et calcule la moyenne générale.
 *
 * @param  ESBTPEtudiant  $etudiant  L'étudiant dont on calcule la moyenne
 * @param  string  $periode  La période (ex: 'semestre1', 'semestre2', '1,2')
 * @param  int|null  $classeId  ID de la classe (optionnel, utilise classe active si null)
 * @return array{moyenne: float, total_coefficient: int, matieres: array}
 *
 * @throws \InvalidArgumentException Si la période est invalide
 * @throws \RuntimeException Si aucune note trouvée
 *
 * @example
 * $result = $service->calculateMoyenne($etudiant, 'semestre1');
 * // ['moyenne' => 12.5, 'total_coefficient' => 30, 'matieres' => [...]]
 */
public function calculateMoyenne(
    ESBTPEtudiant $etudiant,
    string $periode,
    ?int $classeId = null
): array {
    // Implementation
}
```

**Documenter propriétés Model:**
```php
/**
 * Modèle Inscription ESBTP
 *
 * Représente une inscription d'un étudiant à une classe pour une année académique.
 * Une inscription peut avoir plusieurs statuts (en_attente, validée, rejetée, annulée).
 *
 * @property int $id
 * @property int $etudiant_id
 * @property int $classe_id
 * @property string $annee_academique Format: YYYY-YYYY (ex: 2024-2025)
 * @property InscriptionStatus $status Enum: en_attente|validée|rejetée|annulée
 * @property string|null $motif_rejet Raison du rejet si status = rejetée
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read ESBTPEtudiant $etudiant
 * @property-read ESBTPClasse $classe
 * @property-read Collection|ESBTPPaiement[] $paiements
 * @property-read Collection|ESBTPFraisSubscription[] $fraisSubscriptions
 *
 * @method static Builder validees()
 * @method static Builder anneeEnCours()
 * @method static Builder parClasse(int $classeId)
 */
class ESBTPInscription extends Model
{
    // ...
}
```

---

### 9.2 Inline Comments

**Commenter POURQUOI, pas QUOI:**
```php
// ❌ Mauvais: Commente l'évident
// Boucler sur les étudiants
foreach ($etudiants as $etudiant) {
    // Calculer la moyenne
    $moyenne = $this->calculateMoyenne($etudiant);
}

// ✅ Bon: Explique la logique métier
// Exclure les étudiants abandonnés pour le calcul de la moyenne de classe
// car ils faussent les statistiques (beaucoup de notes à 0)
$etudiants = $classe->etudiants()->where('statut', '!=', 'abandon')->get();

foreach ($etudiants as $etudiant) {
    $moyenne = $this->calculateMoyenne($etudiant);
}
```

**Marquer TODOs et FIXMEs:**
```php
// TODO: Implémenter cache Redis pour cette requête lourde
// FIXME: Bug si $periode contient espace (validation nécessaire)
// OPTIMIZE: Remplacer boucle par query groupée (N+1 problem)
// HACK: Workaround temporaire - voir ticket #234
```

---

## 10. Workflow Git

### 10.1 Conventional Commits

**Format:** `<type>(<scope>): <subject>`

**Types principaux:**
- `feat`: Nouvelle fonctionnalité
- `fix`: Correction bug
- `docs`: Documentation seulement
- `style`: Formatage code (pas de changement logique)
- `refactor`: Refactoring (pas de nouvelle feature ni bug fix)
- `perf`: Optimisation performance
- `test`: Ajout/modification tests
- `chore`: Tâches maintenance (build, config, dependencies)

**Exemples KLASSCI:**
```bash
# Feature
git commit -m "feat(inscriptions): ajouter détection doublons étudiants

- Implémente fuzzy search sur nom/prénom/date naissance
- Affiche modal confirmation si doublon potentiel détecté
- Permet force create si admin confirme

Closes #145"

# Fix
git commit -m "fix(paiements): corriger calcul montant restant

Le calcul incluait les paiements rejetés, ce qui faussait le solde.
Maintenant exclut status='rejeté' et deleted_at IS NOT NULL.

Fixes #167"

# Refactor
git commit -m "refactor(bulletins): extraire BulletinGenerationService

- Déplace logique génération PDF depuis controller vers service
- Réduit ESBTPBulletinController de 6852 à 4200 lignes
- Améliore testabilité (tests unitaires sur service)

No breaking changes"

# Docs
git commit -m "docs: ajouter README.md pour module comptabilité

Documente les 3 workflows principaux:
- Gestion frais
- Gestion paiements
- Génération rapports"

# Performance
git commit -m "perf(classes): optimiser requête liste classes

- Ajoute index composite (annee_academique, status, classe_id)
- Eager load filiere + niveau + count etudiants
- Réduit temps réponse de 850ms à 120ms (-85%)

Closes #189"
```

---

### 10.2 Branching Strategy

**Branches principales:**
- `main` / `master`: Production stable
- `develop`: Intégration features
- `staging`: Tests pré-production

**Feature branches:**
```bash
# Créer feature branch
git checkout -b feat/inscription-doublons-detection

# Work...
git add .
git commit -m "feat(inscriptions): ajouter détection doublons"

# Push et créer PR
git push origin feat/inscription-doublons-detection

# Après review et merge
git checkout develop
git pull origin develop
git branch -d feat/inscription-doublons-detection
```

**Hotfix branches:**
```bash
# Bug critique en production
git checkout main
git checkout -b hotfix/paiement-calculation-bug

# Fix
git commit -m "fix(paiements): corriger calcul montant restant"

# Merge dans main ET develop
git checkout main
git merge hotfix/paiement-calculation-bug
git tag v1.2.1

git checkout develop
git merge hotfix/paiement-calculation-bug

git branch -d hotfix/paiement-calculation-bug
```

---

### 10.3 Pull Request Best Practices

**Template PR:**
```markdown
## Description
Implémente la détection de doublons lors de la création d'inscription étudiant.

## Type de changement
- [x] Nouvelle fonctionnalité (feat)
- [ ] Correction bug (fix)
- [ ] Breaking change

## Tests effectués
- [x] Tests unitaires: `InscriptionWorkflowServiceTest`
- [x] Tests feature: `InscriptionDoublonDetectionTest`
- [x] Tests manuels:
  - Créer étudiant avec nom similaire existant
  - Vérifier modal confirmation apparaît
  - Forcer création si confirmé

## Checklist
- [x] Code suit PSR-12
- [x] Commentaires PHPDoc ajoutés
- [x] Migrations testées
- [x] Pas de N+1 queries introduites
- [x] Documentation mise à jour (`docs/workflows/INSCRIPTIONS.md`)
- [ ] Screenshots ajoutés (si UI modifiée)

## Screenshots
[Si applicable]

## Notes reviewer
⚠️ Attention: La méthode `fuzzyMatch()` utilise Levenshtein distance.
Performance OK pour <1000 étudiants, envisager index full-text si scaling.
```

---

## 📚 Sources Officielles

### Laravel
- Laravel 10 Documentation: https://laravel.com/docs/10.x
- Laravel Best Practices: https://github.com/alexeymezenin/laravel-best-practices
- Laracasts: https://laracasts.com/
- Laravel News: https://laravel-news.com/

### PHP
- PHP 8.2 Documentation: https://www.php.net/releases/8.2/en.php
- PHP: The Right Way: https://phptherightway.com/
- PHP Standards Recommendations (PSR): https://www.php-fig.org/psr/

### MySQL
- MySQL 8.0 Reference Manual: https://dev.mysql.com/doc/refman/8.0/en/
- MySQL Performance Blog: https://www.percona.com/blog/
- Use The Index, Luke!: https://use-the-index-luke.com/

### Multi-Tenancy
- Laravel Tenancy Package: https://tenancyforlaravel.com/
- Multi-Tenant Laravel: https://multitenant-laravel.com/

### Frontend
- Blade Templates: https://laravel.com/docs/10.x/blade
- Alpine.js: https://alpinejs.dev/
- Bootstrap 5.3: https://getbootstrap.com/docs/5.3/

### Testing
- PHPUnit Documentation: https://phpunit.de/documentation.html
- Laravel Dusk: https://laravel.com/docs/10.x/dusk
- Pest PHP: https://pestphp.com/

### Security
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- Laravel Security Best Practices: https://laravel.com/docs/10.x/security
- Spatie Laravel Permission: https://spatie.be/docs/laravel-permission/

### Performance
- Laravel Query Performance: https://laravel.com/docs/10.x/queries#debugging
- Redis Documentation: https://redis.io/documentation
- Laravel Horizon: https://laravel.com/docs/10.x/horizon

---

## 🔄 Changelog

### 17 décembre 2024
- Création document basé sur recherches Décembre 2024
- 10 sections principales:
  1. Laravel 9/10 (Fat Models, Service Layer, N+1, Built-in Features, Caching, Jobs)
  2. PHP 8.x (Readonly, Enums, Match, Named Args, Nullsafe, Types)
  3. MySQL 8.x (Indexing, JSON, Agrégations, Connection Pooling)
  4. Frontend (Blade Components, Alpine.js, DataTables)
  5. SaaS Multi-Tenant (Database-per-Tenant, Identification, Migrations)
  6. Sécurité (OWASP Top 10, Rate Limiting, Mass Assignment)
  7. Performance (N+1, Optimization, View Caching, Redis)
  8. Testing (Feature, Unit, Database, Browser)
  9. Documentation (PHPDoc, Inline Comments)
  10. Git Workflow (Conventional Commits, Branching, PRs)
- Adapté au contexte KLASSCI (SaaS Multi-Tenant éducatif)
- Exemples code réels tirés du codebase KLASSCI

---

*Maintenu par: Équipe KLASSCI Development*
*Stack: Laravel 9.x/10.x | PHP 7.4/8.0/8.1/8.2 | MySQL 8.x*
*Architecture: SaaS Multi-Tenant Database-per-Tenant*
