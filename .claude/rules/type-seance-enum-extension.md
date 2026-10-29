# Rule: TypeSeance enum — extension contrôlée

## Quand s'active

Cette rule s'active quand tu :
- Travailles sur `app/Enums/TypeSeance.php`
- Ajoutes un nouveau type de séance pour BTS ou LMD
- Modifies `mapToType()`, `plannableCases()`, ou `selectOptions()`
- Vois des valeurs `'CM' | 'TD' | 'TP' | 'EXAMEN'` etc. hardcodées dans des vues Blade, JS Alpine, ou backend validators

## Règle fondamentale

**Tout nouveau type de séance DOIT être ajouté comme case de l'enum `App\Enums\TypeSeance` avant d'être utilisé dans le code.**

JAMAIS hardcoder une valeur de type_seance en string littérale ni dans Blade, ni dans JS Alpine, ni dans backend.

## Cases canoniques

```php
namespace App\Enums;

enum TypeSeance: string
{
    case CM         = 'CM';        // Cours Magistral
    case TD         = 'TD';        // Travaux Dirigés
    case TP         = 'TP';        // Travaux Pratiques
    case PROJET     = 'PROJET';    // Projet
    case TPE        = 'TPE';       // Travail Personnel Étudiant (non plannable EDT)
    case EXAMEN     = 'EXAMEN';    // Examen terminal session normale
    case PARTIEL    = 'PARTIEL';   // Examen partiel CC (mi-semestre)
    case RATTRAPAGE = 'RATTRAPAGE'; // Examen session rattrapage
    case SOUTENANCE = 'SOUTENANCE'; // Soutenance mémoire/thèse
    case AUTRE      = 'AUTRE';
}
```

## API obligatoire

L'enum DOIT exposer ces méthodes :

```php
public function label(): string;                   // Label utilisateur
public static function values(): array;            // Pour Rule::in()
public static function selectOptions(): array;     // ['VALUE' => 'Label']
public static function fromLegacy(?string $raw): self;  // Migration data
public static function plannableCases(): array;    // Cases utilisables en EDT
public function mapToType(): ?string;              // Map vers seance_cours.type legacy
public function isVolumeTracked(): bool;           // CM/TD/TP comptent dans volume horaire
public function isEvaluation(): bool;              // EXAMEN/PARTIEL/RATTRAPAGE
public function badgeStyle(): array;               // Style premium [bg, color, border, icon]
public function badgeIcon(): string;               // FontAwesome class
public function applicableTo(): string;            // 'BTS' | 'LMD' | 'BOTH'
```

## mapToType() — règle critique

Le mapping vers `seance_cours.type` legacy (course/homework/break/lunch) :

```php
public function mapToType(): ?string
{
    return match ($this) {
        self::CM, self::TD, self::TP, self::PROJET, self::AUTRE => 'course',
        self::TPE => null,  // TPE non plannable
        // ⚠️ TOUS les types évaluation retournent null (pas 'homework' qui est sémantiquement faux)
        // Le filtrage examens utilise type_seance IN ('EXAMEN','PARTIEL','RATTRAPAGE') direct
        self::EXAMEN, self::PARTIEL, self::RATTRAPAGE, self::SOUTENANCE => null,
    };
}
```

**Why:** En Iteration 3 critic round 2 du chantier emploi-temps, finding : `EXAMEN → 'homework'` est sémantiquement faux. Le mapping legacy est uniquement pour compat code historique. Le filtrage examens utilise `where('type_seance', 'EXAMEN')` direct, pas le mapping legacy.

## Anti-patterns à bloquer en review

1. ❌ Hardcoder en Blade :
```blade
{{-- INTERDIT --}}
@if($seance->type_seance === 'CM')
    Cours magistral
@elseif($seance->type_seance === 'TD')
    ...
@endif

{{-- CORRECT --}}
{{ \App\Enums\TypeSeance::tryFrom($seance->type_seance)?->label() }}
```

2. ❌ Hardcoder en JS Alpine :
```js
// INTERDIT
const types = ['CM', 'TD', 'TP'];

// CORRECT
const types = @json(\App\Enums\TypeSeance::plannableCases() | array_map(fn ($t) => $t->value));
```

3. ❌ Hardcoder en backend validator :
```php
// INTERDIT
'type_seance' => 'in:CM,TD,TP,EXAMEN',

// CORRECT
'type_seance' => ['nullable', Rule::enum(\App\Enums\TypeSeance::class)],
// OU
'type_seance' => ['nullable', Rule::in(\App\Enums\TypeSeance::values())],
```

4. ❌ Ajouter une case sans `label()`, `mapToType()`, `badgeStyle()` correspondants

5. ❌ Modifier `mapToType()` pour retourner `'homework'` sur un type évaluation (sémantiquement faux)

6. ❌ Faire un mapping discriminé `BTS vs LMD` côté backend hardcodé — créer une méthode `applicableTo()` sur l'enum :

```php
public function applicableTo(): string
{
    return match ($this) {
        self::PROJET, self::TPE, self::EXAMEN, self::PARTIEL, self::RATTRAPAGE, self::SOUTENANCE => 'LMD',
        // CM, TD, TP, AUTRE → applicable aux 2 systèmes
        default => 'BOTH',
    };
}
```

## Audit avant commit

```bash
# Détecter les hardcodes de type_seance en string littérale
grep -rEn "'(CM|TD|TP|EXAMEN|PARTIEL|RATTRAPAGE|SOUTENANCE|TPE|PROJET)'" \
    app/Http/Controllers/ \
    resources/views/ \
    | grep -v "TypeSeance::" | grep -v "App\\\\Enums" | head -20
```

Si grep trouve hardcodes → remplacer par référence enum.

## Voir aussi

- `app/Enums/TypeSeance.php`
- Memory projet : `feedback_alpine_reactive_type_seance.md`
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md` (PR5 + PR6)
- Rule sœur : `lmd-bts-matieres-single-source.md`
