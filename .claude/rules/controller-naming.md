# Rule: Controller Naming — Méthodes Laravel réservées

## Quand s'active

Cette rule s'active quand tu crées un nouveau controller (Eloquent + Blade ou API) ou ajoutes une méthode action, surtout quand le nom métier coïncide avec un verbe Laravel courant (validate, approve, fail, authorize, etc.).

## Pourquoi cette rule existe

Marcel 16 mai 2026 (incident `/esbtp/tpe-validation`) : **FatalError HTTP 500** :

> `Declaration of ESBTPTpeValidationController::validate(Request, ESBTPTpeDeclaration): RedirectResponse must be compatible with Controller::validate(Request, array $rules, array $messages = [], array $customAttributes = [])`

L'agent W4 avait nommé une méthode controller `validate()` pour valider une déclaration TPE métier. PHP refuse l'override avec **signature incompatible** avec la méthode parent `Illuminate\Routing\Controller::validate()`.

## Méthodes interdites (réservées Laravel parents)

| Méthode | Classe parent Laravel | Signature attendue |
|---|---|---|
| `validate()` | `Illuminate\Routing\Controller` | `validate(Request, array $rules, array $messages = [], array $customAttributes = [])` |
| `failed()` | `FormRequest` | `failed(Validator $validator)` |
| `redirectTo()` | `Authenticate` middleware | `redirectTo(Request): ?string` |
| `authorize()` | `FormRequest` / `Controller` | `authorize(): bool` |
| `__construct()` | classe parent | privé Laravel |
| `__call()` | classe parent magique PHP | — |

## Alternatives canoniques métier

Pour les actions controller, utiliser des verbes alternatifs au métier :

| ❌ INTERDIT (réservé) | ✅ ALTERNATIVES métier |
|---|---|
| `validate()` | `approve()`, `confirm()`, `accept()`, `verify()` |
| `failed()` | `reject()`, `decline()`, `markFailed()` |
| `authorize()` | `authorizeAction()`, `grant()`, `permit()` |
| `redirectTo()` | `goTo()`, `forwardTo()`, `navigateTo()` |

**Note importante** : Le **route name** peut rester `validate` (`->name('xxx.validate')`) pour stabilité URL et lisibilité métier. Seule la **méthode du controller** est renommée :

```php
// routes/web.php
Route::patch('esbtp/tpe-validation/{declaration}/validate', [
    ESBTPTpeValidationController::class, 'approve'   // ← méthode renommée
])->name('esbtp.tpe-validation.validate');           // ← name préservé

// Dans la vue Blade
<form action="{{ route('esbtp.tpe-validation.validate', $declaration) }}" method="POST">
```

## Audit grep AVANT chaque commit

Liste de check obligatoire pre-merge sur tout nouveau controller :

```bash
grep -nE "public function (validate|failed|authorize|redirectTo|__call|__construct)\b" app/Http/Controllers/*Controller.php
```

Chaque match doit être justifié :
- **Soit** override volontaire avec signature parent strictement compatible
- **Soit** rename obligatoire avant push

## Anti-patterns à BLOQUER en review

1. ❌ Méthode controller `public function validate(Request $request, ...)` avec args métier custom
2. ❌ Méthode `failed()` qui n'est PAS le hook FormRequest `failed(Validator)`
3. ❌ `authorize()` dans Controller (réserver pour FormRequest)
4. ❌ `__construct()` non-empty dans controller sans `parent::__construct()`
5. ❌ Tester localement en dev sans hit explicite de la route → FatalError ne se voit qu'en prod au runtime

## Test pre-merge obligatoire

Pour tout nouveau controller avec une méthode action :
1. `php -l app/Http/Controllers/MyController.php` (lint syntaxe)
2. `php artisan route:list --name=xxx` (vérifier la route résolue)
3. **Test runtime** : hit l'URL au moins 1× via curl ou navigateur. FatalError n'apparaît qu'au boot du controller.

## Voir aussi

- Mémoire `feedback_controller_method_name_collisions.md` (incident fondateur 16/05/2026, commit `d082c743`)
- [`.claude/rules/feature-delivery-methodology.md`](feature-delivery-methodology.md) — pre-merge checklist KLASSCI
- [`.claude/rules/no-god-code.md`](no-god-code.md) — controller < 200 LOC, méthodes courtes
