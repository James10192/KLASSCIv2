# Rule: Debugging Discipline — Pièges KLASSCI documentés

## Quand s'active

Cette rule s'active quand tu :
- Modifies un controller et vois que tes changements ne prennent pas effet
- Modifies un service et obtiens un 500 sans cause apparente
- Ajoutes du logging et il n'apparaît pas dans les logs
- Lances un visual check qui timeout
- Diagnostiques une page qui rend des données incohérentes
- Recommandes d'éditer un fichier sans avoir d'abord vérifié qu'il est bien sur le chemin actif

## Le principe

**Avant d'éditer un fichier, vérifie qu'il est sur le chemin actif.** Ne pars JAMAIS de l'hypothèse « ce nom de fichier ressemble au domaine donc c'est le bon ». KLASSCI a un historique d'évolution où plusieurs controllers/services nommés similairement coexistent. La vérification prend 30 secondes, faute coûte des heures.

---

## Pièges fondateurs (incidents réels avec heures perdues)

### Piège #1 — Controller/Route mismatch : 3h perdues (juin 2026)

**Symptôme** : J'ai édité `ESBTPEtudiantController::index()` pour fixer un filtre Réinscription groupée. Après 3 itérations de deploy + dev-browser tests, le filtre ne prenait jamais effet. Les logs `Log::error('DIAG_BULK_ELIGIBLE')` n'apparaissaient JAMAIS dans les logs malgré 10+ rechargements de page.

**Cause** : La route `/esbtp/etudiants` est servie par `ESBTPStudentController` (avec **S**), pas `ESBTPEtudiantController`. Définition explicite à `routes/web.php` ligne 2006 :
```php
Route::resource('esbtp/etudiants', ESBTPStudentController::class, ['as' => 'esbtp'])
```

Les deux controllers ont des noms quasi-identiques. Le mauvais avait une logique similaire avec `$etudiantsForBulk`, ce qui renforçait l'impression de bon fichier.

**Workflow correct AVANT d'éditer un controller** :
```bash
# 1. Identifier la route qui sert la page
grep -E "Route::.*'(esbtp/etudiants|/etudiants)'\b" routes/web.php

# 2. Ou via artisan
php artisan route:list --path=esbtp/etudiants

# 3. Confirmer le controller mappé AVANT toute édition
```

**Anti-pattern à BLOQUER** : éditer un fichier en se basant uniquement sur le nom (ESBTPEtudiantController vs ESBTPStudentController vs ESBTPStudentNewController).

**Signal d'alarme à respecter immédiatement** : si tes logs `Log::error('UNIQUE_MARKER')` n'apparaissent pas après deploy + cache:clear, **NE CONTINUE PAS à patcher**. Le fichier édité n'est PAS sur le chemin actif. Cherche le vrai controller AVANT toute autre action.

---

### Piège #2 — Service method visibility : 500 silencieux

**Symptôme** : `BulkReinscriptionService::preview()` retournait 500 sans message clair. Le front catch fallback à `step='select'`, donnant l'impression "la liste se recharge" sans erreur visible côté UX.

**Cause** : Appel à `ReeinscriptionService::calculerSoldeInscription()` qui était `private`. PHP throw `Error: Call to private method`.

**Règle** : Quand un service A doit appeler une méthode d'un service B, vérifie la visibilité (`public`/`private`/`protected`). Si la méthode est `private` mais doit être réutilisée, la rendre `public` avec un commentaire d'usage public.

**Workflow correct** : 
```bash
# AVANT d'écrire un appel cross-service
grep "function methodName" path/to/OtherService.php
# Vérifier la visibilité (public/private/protected)
```

---

### Piège #3 — Filename case sensitivity (Windows ↔ Linux)

**Symptôme** : Un commit avec `git add path/to/Api/CLI/File.php` ne tracke pas le fichier. Sur Linux yakro, la méthode ajoutée renvoie `Method does not exist`.

**Cause** : Windows filesystem est case-insensitive, Linux est case-sensitive. Le repo a `API` (caps) dans le path. Adding via `Api` (PascalCase) ne match aucun fichier git-tracked, donc le diff est vide.

**Vérification** :
```bash
git ls-files | grep -i controllername  # listing case-réel git-tracked
```

**Toujours** utiliser la case exacte du repo dans les commandes `git add`.

---

### Piège #4 — Log level production filter

**Symptôme** : `Log::info('marker')` n'apparaît pas dans les logs `level=info` même après hit de la page concernée.

**Cause** : Configuration prod peut filter INFO/DEBUG. `Log::error()` passe toujours.

**Pour debug live** : utilise `Log::error('DIAG_MARKER', [...])` temporairement. Cleanup après :
```php
// AVANT cleanup
\Log::error('DIAG_BULK_ELIGIBLE', ['count' => $x->count()]);

// APRÈS validation : remove ou converti en Log::info si vraiment utile en prod
```

---

### Piège #5 — dev-browser timeout 30s sur yakro

**Symptôme** : Scripts dev-browser timeout systématique à 30s sur yakro (serveur LWS lent).

**Stratégie** :
- **Chaîner** les opérations dans 1 seul `evaluate()` pour réduire round-trips
- **Réutiliser la session** : `browser.getPage('check')` réutilise le contexte → évite re-login + re-navigation
- **Vérifier l'état avant nav** : si la page est déjà sur la bonne URL, skip `page.goto()`
- **Avoid waitForLoadState('networkidle')** : peut bloquer indéfiniment, use `setTimeout` fixe
- **Test multi-étapes** : split en plusieurs scripts qui partagent la session, plutôt qu'un mega-script

---

### Piège #6 — Bulletin snapshot drift (esbtp_bulletins.moyenne_generale)

**Symptôme** : Marcel signale "Officiel 11.45 / Courant 12.15 / Delta +0.57" alors qu'il pense n'avoir rien changé.

**Cause** : `esbtp_bulletins.moyenne_generale` est un **snapshot figé** au moment de la génération. La page PDF preview recalcule live (utilise courant). Donc :
- Le snapshot DB peut être stale après modifs sur notes/évaluations
- Le PDF montre la valeur live (correcte)
- L'alerte UI affiche la stale snapshot vs courant

**Règle** : **JAMAIS** mettre à jour `moyenne_generale` automatiquement. C'est un snapshot officiel signé. Seule la régénération explicite par l'utilisateur le met à jour.

---

### Piège #7 — Notes denormalization stale après changement éval

**Symptôme** : Modifier la matière d'eval#622 fait apparaître ancienne matière dans page résultats étudiant (« Matière inconnue » + moyenne 10.50).

**Cause** : `esbtp_notes` a ses propres colonnes dénormalisées `matiere_id`, `classe_id`, `semestre`. Le hook `saving()` sync seulement quand on save la NOTE, pas quand on save l'éval parente. Les notes ne propagent pas auto.

**Solution** : 
1. `ESBTPEvaluationController::update()` capture old values via `getOriginal()` puis bulk update notes si classe/matiere/periode changé
2. Commande `php artisan evaluations:sync-notes [--evaluation=ID] [--clean-resultats]` pour réparer le legacy
3. `--clean-resultats` détecte aussi les `esbtp_resultats` orphelins (broken matiere_id OU pas de notes correspondantes)

---

### Piège #8 — whereDoesntHave + global scopes + soft delete

**Symptôme** : Filtre `whereDoesntHave('inscriptions', N)` semble laisser passer des étudiants qui ont une inscription N.

**Cause** : Cherche d'abord côté **route mismatch** (#1) AVANT de soupçonner Eloquent. Eloquent est généralement correct. Le 99% des cas où ça « ne filtre pas » est que le code édité n'est pas exécuté.

**Vérification rapide** : ajoute `Log::error('FILTER_DIAG', ['count' => $query->count()])` avant le get(). Si le log n'apparaît jamais, tu édites le mauvais fichier (cf #1).

---

### Piège #9 — Bootstrap data-bs-strategy IGNORÉ

**Symptôme** : Set `data-bs-strategy="fixed"` sur trigger dropdown, mais Popper utilise toujours `position: absolute`.

**Cause** : Bootstrap 5.3 N'A PAS d'option `strategy` dans son `Default` (vérifié dans le source `Dropdown.js`). L'attribut HTML est ignoré.

**Solution canonique** : monkey-patch `bootstrap.Dropdown.prototype._getPopperConfig` pour forcer strategy:'fixed' :
```js
const orig = bootstrap.Dropdown.prototype._getPopperConfig;
bootstrap.Dropdown.prototype._getPopperConfig = function() {
    const config = orig.apply(this, arguments);
    if (config) config.strategy = 'fixed';
    return config;
};
```

`window.Popper` n'est PAS exposé par bootstrap.bundle (Popper est interne). Le monkey-patch sur `Dropdown.prototype` est la VRAIE façon.

---

### Piège #10 — CSS containing block pour position:fixed

**Symptôme** : Dropdown menu apparaît à des centaines de pixels du trigger.

**Cause** : Un ancestor du `.dropdown-menu` a `transform`, `filter`, `backdrop-filter`, `perspective`, `contain: paint`, ou `will-change` non-`none`. Cela crée un *containing block* pour les descendants `position: fixed`.

**Voir** : `.claude/rules/universal-dropdowns.md` section « Piège critique #2 ».

**Anti-pattern** : `[card]:hover { transform: translateY(-2px) }` quand la card contient un dropdown.

---

### Piège #11 — Blade `@json([multiligne])`

**Symptôme** : `Unclosed '[' on line N does not match ')'` au runtime.

**Cause** : Le parser Blade match mal `@json([` multiligne. Doit utiliser variable intermédiaire.

**Pattern correct** :
```blade
@php
    $myData = [
        'key1' => $val1,
        'key2' => $val2,
    ];
@endphp
<div data-payload='@json($myData)'>
```

**Voir** : `.claude/rules/blade-pitfalls.md` Pitfall #4.

---

## Workflow systematic pour debug d'un bug "mes changements ne prennent pas effet"

Quand tu vois le symptôme « mes logs/changements n'apparaissent pas », exécute ce checklist DANS L'ORDRE :

1. **Verify route → controller mapping** :
   ```bash
   grep -E "Route::.*'$URL_PART'" routes/web.php
   php artisan route:list --path=$URL_PART | head -3
   ```

2. **Verify file is git-tracked + deployed** :
   ```bash
   git log --oneline -3 path/to/file.php
   git push --dry-run  # voir si commit ahead
   ```

3. **Verify cache:clear ran AFTER deploy** :
   ```bash
   klassci pull $tenant && klassci cache:clear $tenant
   ```
   Le `cache:clear` fait `view:clear + opcache reset`. Sans ça les changements PHP ne sont pas pris en compte.

4. **Verify log level production** : Use `Log::error()` pour debug, JAMAIS `Log::info()` car peut être filtré.

5. **Verify case sensitivity** : `git ls-files | grep -i $filename` pour voir la case réelle.

6. **Add unique marker** : `Log::error('MARKER_UUID_$(date +%s)', [...])` dans le code édité, puis grep les logs. Si marker absent → tu édites le mauvais fichier. STOP et cherche le vrai.

7. **Verify service method visibility** si appel cross-service :
   ```bash
   grep "function methodName" path/to/Service.php  # vérifier private/public
   ```

---

## Anti-patterns à BLOQUER en review

1. ❌ **Patcher 3+ fois le même file** sans avoir vérifié qu'il est sur le chemin actif
2. ❌ **Ignorer l'absence du log de debug** dans les outputs (signal #1 que tu édites le mauvais fichier)
3. ❌ **Hardcoder des moyenne_generale updates** sur `esbtp_bulletins` sans passage par régénération explicite
4. ❌ **Synchroniser denormalized columns** sur `esbtp_notes` sans aussi cleaner `esbtp_resultats` orphelins
5. ❌ **Compter sur `data-bs-strategy`** HTML attribute pour configurer Popper (ignored)
6. ❌ **Mettre `transform` sur :hover d'un parent de dropdown** (créé containing block)
7. ❌ **Utiliser `@json([multiligne])` direct** sans extract en variable
8. ❌ **Test dev-browser monolithe** qui timeout à 30s — split en étapes courtes
9. ❌ **Commit `git add path/Api/CLI/File.php`** sur Windows alors que le repo a `API/CLI/`
10. ❌ **Continuer à éditer** après avoir vu que tes logs n'apparaissent pas (= signal STOP IMMÉDIAT)

---

## Voir aussi

- `.claude/rules/blade-pitfalls.md` — pièges Blade silencieux
- `.claude/rules/universal-dropdowns.md` — Popper strategy + containing block
- `.claude/rules/feature-delivery-methodology.md` — méthodologie 13 phases
- `.claude/rules/multi-agent-git-safety.md` — discipline cross-branch
- `.claude/rules/exports-pdf-excel.md` — pattern unifié exports
- Memory projet : feedback_controller_method_name_collisions
