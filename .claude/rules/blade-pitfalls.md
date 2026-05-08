# Blade pitfalls — bugs silencieux qui crashent au runtime

## Quand s'active

Cette rule s'active dès que tu :
- Modifies un fichier `.blade.php` (vue, partial, layout, composant)
- Crées une nouvelle vue
- Vois une erreur Laravel `ParseError` / `unexpected token` mentionnant un fichier `.blade.php`
- Diagnostiques un crash 500 sur une page qui rend une vue Blade

## Pourquoi cette rule existe

Le compiler Blade Laravel a deux footguns SILENCIEUX qui ne sont PAS détectés par `php artisan view:cache` (qui compile sans erreur), mais qui crashent PHP au runtime quand la vue est effectivement rendue. Ces deux bugs ont déjà fait perdre du temps en mai 2026 :

1. **Bug `@can` dans un commentaire JS** (incident PR #361, 2026-05-07) — 500 sur `/esbtp/inscriptions/create` parce que `// gating @can côté Blade` dans un commentaire JS était parsé comme directive `@can(...)`.

2. **Bug `@php(expr)` shortform mangé par `@php...@endphp` block regex** (incident hotfix PR ~#370, 2026-05-08) — 500 sur 4 PDFs (liste-appel, liste-complete, etudiants/export, classes/export) parce que `@php($_logo = ...)` shortform était englouti par le regex de bloc qui mangeait jusqu'au prochain `@endphp`.

Les deux bugs sont silencieux à la compilation, et seuls les utilisateurs qui hit la page/PDF les déclenchent.

## Pitfall #1 — `@php(expr)` shortform mangé par `@php...@endphp` block regex

### Le piège

Laravel utilise le regex `(?<!@)@php(.*?)@endphp/s` pour capturer les blocs `@php ... @endphp` AVANT toute autre compilation (`BladeCompiler::storePhpBlocks`). **Le regex ne distingue PAS** :
- La **shortform** `@php(expr)` (parens, sans `@endphp` propre)
- La **block form** `@php ... @endphp`

Si ton fichier contient `@php(expr)` ET un `@php ... @endphp` plus loin → le regex capture `@php(` du shortform comme ouverture de bloc et engloutit tout jusqu'au prochain `@endphp`. Le contenu intermédiaire (autres `@if`, `@foreach`, HTML, etc.) est laissé textuel et **jamais compilé**. Les `@endif`, `@endforeach` plus bas, eux, sont compilés normalement → orphelins → ParseError PHP.

### Symptôme

Erreur PHP au runtime du type :
```
syntax error, unexpected token "endforeach", expecting end of file
syntax error, unexpected token "endif", expecting end of file
```
…dans un fichier `storage/framework/views/<hash>.php`.

### ❌ INTERDIT

```blade
{{-- Fichier contenant @php(...) shortform ET @php...@endphp block plus loin --}}
@php($_logo = \App\Helpers\SettingsHelper::resolveLogoBase64())
...
@php $autreVar = ...; @endphp  {{-- ce @endphp mange tout depuis @php( --}}
```

### ✅ FIX

Toujours utiliser la **block form sur une seule ligne** :

```blade
@php $_logo = \App\Helpers\SettingsHelper::resolveLogoBase64(); @endphp
```

C'est aussi compact que la shortform et **immune au regex bug**.

### Détection au quotidien

```bash
# Si présent ET il y a @endphp plus loin → suspect immédiat
grep -n "@php(" path/to/file.blade.php
```

## Pitfall #2 — Directives `@xxx` dans les commentaires JS/HTML/string

### Le piège

Blade scanne **tout le fichier** pour les directives `@xxx`, **y compris à l'intérieur** de :
- Commentaires JavaScript (`// ...`)
- Commentaires HTML (`<!-- ... -->`)
- Strings JS (`"texte avec @if"`)
- Attributs HTML (`title="@can(...)"`)

Le moteur ne parse PAS le langage hôte — c'est juste un regex pass.

### Symptôme

Erreur Blade :
```
syntax error, unexpected end of file, expecting "elseif" or "else" or "endif"
```
Le grep ferait un compte balanced de `@if`/`@endif` qui semble OK, parce que le faux `@if` dans le commentaire ne se voit pas immédiatement.

### ❌ INTERDIT

```blade
<script>
    // gating @can côté Blade  {{-- Blade voit @can comme directive ! --}}
    // pour le cas @if($x) on fait Y
</script>
```

### ✅ FIX (au choix)

**Option 1** — Reformuler pour éviter le mot-directive :
```blade
<script>
    // gating Blade côté serveur
</script>
```

**Option 2** — Échapper avec `@@xxx` (Blade émet `@xxx` littéral à la sortie) :
```blade
<script>
    // see @@can directive in the partial
</script>
```

### Détection au quotidien

```bash
# Cherche toute directive Blade dans un commentaire JS ou HTML
grep -nE "//.*@(can|if|foreach|endif|else|elseif|endcan|endforeach|push|once|section)" file.blade.php
grep -nE "<!--.*@(can|if|foreach|endif|else|elseif|endcan|endforeach|push|once|section)" file.blade.php
```

## Audit obligatoire avant de pousser un .blade.php modifié

```bash
# 1. Comptage des directives — chaque paire doit être équilibrée
grep -oE "@(if|endif|foreach|endforeach|php|endphp|can|endcan|cannot|endcannot|push|endpush|once|endonce|section|endsection|forelse|endforelse|isset|endisset)\b" file.blade.php | sort | uniq -c

# 2. Détection des shortforms @php(  dans un fichier avec @endphp plus loin
grep -n "@php(" file.blade.php
# Si présent ET il y a @endphp dans le même fichier → convertir en block form

# 3. Détection des directives dans commentaires
grep -nE "//.*@(can|if|foreach|endif|else)" file.blade.php
grep -nE "<!--.*@(can|if|foreach|endif|else)" file.blade.php

# 4. Force lint du compiled output (CATCH les 2 bugs ci-dessus)
rm storage/framework/views/*.php
php artisan view:cache
for f in storage/framework/views/*.php; do php -l "$f" 2>&1 | grep -v "No syntax errors"; done
# Si l'output mentionne "Parse error" → un bug silencieux est resté
```

**Important** : `php artisan view:cache` SEUL ne suffit pas — il compile sans erreur même quand les bugs ci-dessus sont présents. Il faut **php -l** sur les compiled views pour détecter le mauvais output.

## Anti-patterns à BLOQUER en review

1. ❌ `@php(expr)` shortform dans un fichier qui contient AUSSI `@php ... @endphp` block plus loin
2. ❌ `@can`, `@if`, `@foreach`, `@endif`, etc. littéraux dans un commentaire JS (`// @can(...)`) ou HTML (`<!-- @if(...) -->`)
3. ❌ Tester un Blade modifié uniquement avec `php artisan view:cache` sans `php -l` du compiled output
4. ❌ Mélanger les deux formes `@php(...)` et `@php...@endphp` dans le même fichier (toujours préférer la block form pour la cohérence)

## Voir aussi

- `feedback_blade_directive_in_comments.md` (mémoire — incident PR #361 mai 2026)
- `feedback_blade_php_shortform_eats_block.md` (mémoire — incident hotfix mai 2026)
- `.claude/skills/dompdf-expert/SKILL.md` section 12.5 (mêmes bugs documentés côté skill PDF, car les PDFs sont les victimes les plus fréquentes via le pattern `@php($_logo = ...)`)
- Code Laravel concerné : `vendor/laravel/framework/src/Illuminate/View/Compilers/BladeCompiler.php::storePhpBlocks` (ligne ~389 de la version Laravel 12)
