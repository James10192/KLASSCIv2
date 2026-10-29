# Sidebar — Permissions & Anti-doublon

## Quand s'active

Cette rule s'active quand tu modifies `resources/views/layouts/app.blade.php`, ou quand tu ajoutes un item au menu sidebar.

## Architecture sidebar

La sidebar a **deux niveaux de sections** pour certains modules :
1. **Section plate** (caissier) : items directs sous un `menu-category`
2. **Section complète** (admin) : `menu-category` + accordion `menu-accordion` avec sous-items

Le superAdmin a TOUTES les permissions via `Gate::before()`. Si les deux sections sont visibles sans guard, il voit des doublons.

## Pattern anti-doublon (OBLIGATOIRE)

Quand une section plate coexiste avec une section complète pour le même domaine :

```blade
{{-- Section plate : uniquement pour les users SANS accès au module complet --}}
@if(!auth()->user()->can('module.XXX.access'))
    <div class="menu-category">Nom Section</div>
    {{-- items plats --}}
@endif

{{-- Section complète : visible si module activé --}}
@can('module.XXX.access')
@can('specific.access')
    <div class="menu-category">Gestion XXX</div>
    {{-- accordion avec sous-items --}}
@endcan
@endcan
```

## Cas existants déjà fixés

| Section plate | Guard anti-doublon | Section complète |
|---|---|---|
| Consultation (étudiants) | `@if(!can('module.etudiants.access'))` | Étudiants & Inscriptions |
| Comptabilité (paiements) | `@if(!can('module.comptabilite.access'))` | Gestion financière |

## Règles

1. **JAMAIS `@can()` pour Service Technique** — toujours `@role('serviceTechnique')` car superAdmin a toutes les permissions
2. **JAMAIS ajouter un item dans les DEUX sections** — choisir la section plate OU complète
3. **Toujours vérifier** si l'item existe déjà dans une autre section avant d'ajouter
4. **Tester avec superAdmin** après modification — c'est le rôle qui révèle les doublons
5. **Module toggles** : `@can('module.XXX.access')` = couche abonnement instance, `@can('specific_perm')` = permission métier

## Structure des modules toggles

Source : `config/permissions.php` (lue via `App\Services\PermissionRegistry`).

```
module.enseignants.access
module.notes_evaluations.access
module.emploi_temps.access
module.presences.access
module.lmd.access
module.academique.access         (filières, classes, niveaux, cycles, matières)
module.etudiants.access          (étudiants, inscriptions, réinscriptions)
module.comptabilite.access       (frais, paiements, relances, dashboard comptable)
module.caisse.access             (rôle caissier — pré-inscription + encaissement)
module.communication.access      (annonces, messages)
module.technical_support.access  (Service Technique uniquement)
```

Pour ajouter un module : éditer `config/permissions.php` et déployer via `php bin/deploy/fix_permissions.php`. Voir [.claude/rules/permissions.md](permissions.md).
