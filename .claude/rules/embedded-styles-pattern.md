# Rule: Embedded mode CSS — toujours `@push('styles')`, jamais `@section('styles')`

## Quand s'active

Cette rule s'active quand tu :
- Crées ou modifies une vue Blade qui peut être chargée en mode embedded (`?embed=1`)
- Travailles sur une vue qui utilise `@extends(request()->boolean('embed') ? 'layouts.embedded' : 'layouts.app')`
- Crées une nouvelle vue dans `seances-cours/`, `evaluations/`, `etudiants/embed/`, `inscriptions/embed/`, ou similaire

## Règle fondamentale

**Toute vue qui supporte `?embed=1` DOIT utiliser `@push('styles')`, JAMAIS `@section('styles')`.**

**Why:**
- `layouts.embedded.blade.php` rend les styles via `@stack('styles')` (line 248)
- `layouts.app.blade.php` rend les styles via `@stack('styles')` ET `@yield('styles')` (les deux)
- `@section('styles')` n'est JAMAIS lu par `@stack('styles')`
- Conséquence : en mode embedded, les styles `@section`-pushed sont **silencieusement droppés** → la vue rend sans CSS premium (régression "embedded sans style" Marcel)

## Pattern correct

### Vue qui supporte embedded

```blade
@extends(request()->boolean('embed') ? 'layouts.embedded' : 'layouts.app')

@section('title', 'Ajouter une séance - KLASSCI')

@push('styles')
<style>
    .sce-hero { ... }
    .sce-card { ... }
</style>
@endpush

@push('scripts')
<script>
    // ...
</script>
@endpush

@section('content')
    <!-- Vue body -->
@endsection
```

### Vue standalone-only (ne supporte pas embedded)

Pour ces vues, `@section('styles')` ET `@push('styles')` sont équivalents (les 2 layouts.app les rendent). Mais préférer `@push('styles')` pour cohérence.

## Fallback dans layouts.embedded

`layouts.embedded.blade.php` doit aussi rendre `@yield('styles')` en fallback rétrocompat double :

```blade
<!-- layouts/embedded.blade.php -->
<head>
    <!-- ... -->
    <style>
        /* ... base styles ... */
    </style>
    @yield('styles')
    @stack('styles')
</head>
```

Avec ce fallback, les anciennes vues qui utilisaient encore `@section('styles')` ne cassent plus en mode embedded.

## Anti-patterns à bloquer en review

1. ❌ `@section('styles')` dans une vue avec `@extends(request()->boolean('embed') ? ...)` :
```blade
{{-- INTERDIT : silencieusement dropé en embedded --}}
@extends(request()->boolean('embed') ? 'layouts.embedded' : 'layouts.app')

@section('styles')
<style>...</style>
@endsection
```

2. ❌ Vue de modal AJAX qui charge `?embed=1` sans tester en embedded préalable (rule `feature-delivery-methodology` phase 11)

3. ❌ Layout custom qui omet `@stack('styles')` ET `@yield('styles')` → impossible de pousser styles

4. ❌ CSS critique inline dans `@section('content')` au lieu de `@push('styles')` → contourne le pattern

## Audit avant commit

```bash
# Détecter les vues avec @extends('layouts.embedded') OU embed-conditional qui utilisent @section('styles')
grep -rEl "layouts\.embedded|boolean\('embed'\)" resources/views/ | \
    xargs grep -l "@section('styles')"
```

Si grep trouve match → BLOQUE commit, refactor vers `@push('styles')`.

## Sites historiques

Bug "embedded sans style" trouvé sur (PR4 fix obligatoire) :
- `resources/views/esbtp/seances-cours/create.blade.php:5` ✓ corrigé en PR4
- `resources/views/esbtp/evaluations/create.blade.php` ✓ corrigé en PR4

Sites validés OK (utilisent déjà `@push('styles')`) :
- `resources/views/esbtp/etudiants/embed/edit.blade.php`
- `resources/views/esbtp/inscriptions/embed/edit.blade.php`

## Voir aussi

- Memory projet : (pas de memory dédiée — info dans master plan)
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md` (PR4)
- Layout : `resources/views/layouts/embedded.blade.php`
- Layout : `resources/views/layouts/app.blade.php`
- Rule sœur : `blade-alpine-pitfalls.md`
