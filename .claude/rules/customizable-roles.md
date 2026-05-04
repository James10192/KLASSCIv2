# Rule: Customizable Roles — JAMAIS de rôle hardcodé en plus

## Quand s'active

Cette rule s'active automatiquement quand tu envisages :
- Créer un nouveau rôle hardcodé dans `config/permissions.php` (ex: `directeur_financier`, `auditeur`, `responsable_caisse`)
- Hardcoder un check `hasRole('xxx')` dans du code
- Suggérer dans un plan « créer un nouveau rôle X pour la feature Y »

## Règle absolue

**Tu n'inventes PAS de nouveau rôle.** Le set des rôles **canoniques** est figé :
`superAdmin`, `secretaire`, `comptable`, `caissier`, `coordinateur`, `enseignant`, `etudiant`, `serviceTechnique` (masqué).

Tout besoin métier nouveau (« il faut un directeur financier qui voit l'audit mais ne valide pas », « il faut un auditeur externe qui consulte sans modifier ») se traite **EXCLUSIVEMENT** via :

1. **Création d'une nouvelle PERMISSION** dans `config/permissions.php` clé `permissions`
2. **Assignation aux rôles existants** via `role_defaults` (defaults) OU laisser vide → l'école configure
3. **Création d'un rôle CUSTOM via l'UI** (`/esbtp/custom-roles` → ESBTPCustomRoleController) — chaque école configure ses propres rôles métier (ex: ESBTP Yakro crée « Directeur Financier », Hetec crée « Auditeur Interne »)

## Pourquoi cette rule existe

Marcel (3 mai 2026) — pendant le plan compta killer, j'avais suggéré « créer un rôle `directeur_financier` ». Marcel a corrigé :

> « Comme tu dois voir maintenant ce sont les custom roles, ou on modifie les rôles et nous-même on donne les permissions. Normalement c'est le superAdmin qui a cette permission mais un autre rôle peut l'avoir, c'est full configurable. Tu crées juste la fonctionnalité et pour les rôles, les permissions, les settings c'est l'école qui s'en charge tout simplement. »

KLASSCI étant multi-instance (ESBTP, Hetec, ISLG Rostan, etc.), chaque école a sa propre structure RH :
- Petite école → 1 directeur cumule TOUTES les responsabilités
- Grosse école → directeur opérationnel + directeur financier + auditeur interne séparés
- Cabinet d'audit externe → rôle custom temporaire avec accès lecture seule

Hardcoder un rôle = imposer une organisation. C'est anti-produit.

## Comment appliquer concrètement

### ❌ MAUVAIS — créer un rôle hardcodé

```php
// config/permissions.php
'roles' => [
    'directeur_financier' => [...],  // ❌ NON
],
'role_defaults' => [
    'directeur_financier' => [
        'comptabilite.audit.view',
        'paiements.validate.high_amount',
        // ...
    ],
],
```

```php
// Controller
if (auth()->user()->hasRole('directeur_financier')) {  // ❌ NON
    // ...
}
```

### ✅ BON — créer la permission + laisser l'école configurer

```php
// config/permissions.php
'permissions' => [
    'paiements.validate.high_amount' => [
        'label' => 'Valider les paiements de gros montant (≥ seuil instance)',
        'description' => 'Permet la double-validation requise pour les paiements > seuil défini dans Settings',
        'group' => 'Comptabilité',
        'icon' => 'fa-shield-alt',
    ],
    'comptabilite.audit.view' => [
        'label' => 'Voir le journal d\'audit comptable',
        'group' => 'Comptabilité',
        'icon' => 'fa-history',
    ],
],

'role_defaults' => [
    // Laisser VIDE pour ces perms → l'école attribue à qui elle veut.
    // OU pré-grant au superAdmin uniquement (couverture par Gate::before quoi qu'il arrive).
    'superAdmin' => [
        // pas besoin d'expliciter, Gate::before couvre tout
    ],
],
```

```php
// Controller
if (auth()->user()->can('paiements.validate.high_amount')) {  // ✅ OUI
    // ...
}
```

```blade
{{-- Vue --}}
@can('comptabilite.audit.view')  {{-- ✅ marche pour superAdmin + tout rôle custom qui a cette perm --}}
    <a href="{{ route('esbtp.audit.comptabilite') }}">Audit</a>
@endcan
```

L'école va sur `/esbtp/custom-roles` :
1. Crée un rôle « Directeur Financier ESBTP » (ou « Auditeur », ou ce qu'elle veut)
2. Coche les permissions `comptabilite.audit.view`, `paiements.validate.high_amount`, etc.
3. Assigne ce rôle à un user

→ Aucune ligne de code modifiée. Aucune migration. C'est l'école qui décide.

## Permissions à privilégier vs anti-patterns

| Besoin métier | ❌ Anti-pattern | ✅ Pattern correct |
|---|---|---|
| « Quelqu'un valide les gros paiements » | Rôle `directeur_financier` | Permission `paiements.validate.high_amount` + setting instance `comptabilite.high_amount_threshold` |
| « Auditeur externe consulte tout en lecture » | Rôle `auditeur` | Permissions `*.view` granulaires que l'école coche dans un rôle custom |
| « Caissier responsable de caisse a + de droits » | Rôle `responsable_caisse` | Permission `comptabilite.caisse.approve` que l'école attribue à qui elle veut |
| « Le directeur reçoit les notifications » | Rôle `directeur` | Permission `comptabilite.notifications.high_amount` + listener qui notifie tous les users avec cette perm |

## Pattern : seuils via Settings instance

Quand un rôle aurait été défini par un seuil (« le directeur valide > 5M »), c'est une permission + un setting :

```php
// Le check
$threshold = SettingsHelper::get('comptabilite.high_amount_threshold', 5_000_000);
if ($paiement->montant >= $threshold) {
    // Nécessite permission spéciale
    abort_unless($user->can('paiements.validate.high_amount'), 403);
}
```

L'école configure son seuil dans `/esbtp/settings/comptabilite`. Une école riche met 10M, une petite met 500k. **Aucune ligne de code à changer.**

## Audit avant tout commit

Avant de commit une feature qui touche aux rôles/permissions :
1. `grep -rn "hasRole(" app/` — chaque match doit être justifié (généralement seulement pour `superAdmin` ou `serviceTechnique`)
2. `grep -rn "hasRole" resources/views/` — préférer `@can()` partout
3. Vérifier qu'aucun nouveau rôle n'apparaît dans `config/permissions.php` clé `roles`
4. Vérifier que toute nouvelle permission est dans `permissions` avec un `label` français lisible

## Anti-patterns à BLOQUER en review

1. ❌ Ajout d'une entrée dans `config/permissions.php` clé `roles` (sauf cas exceptionnel justifié par Marcel)
2. ❌ `hasRole('xxx')` hardcodé dans Controller/Service/View pour un rôle non-superAdmin/serviceTechnique
3. ❌ Plan de feature qui dit « créer un nouveau rôle pour cela » → reformuler en « créer une nouvelle permission »
4. ❌ Permission qui n'a pas de label français vendable (ex: `compta.x.y` sans label)
5. ❌ Hardcoder un seuil (« > 5_000_000 ») au lieu de le sortir en setting
6. ❌ Migration qui crée un Role custom au déploiement (= hardcoder via la DB)

## Voir aussi

- `.claude/rules/permissions.md` — convention canonique nommage `domaine.action[.qualifier]`
- `app/Http/Controllers/ESBTPCustomRoleController.php` — UI custom roles (680 LOC)
- `app/Services/PermissionRegistry.php` — lecture du registry (278 LOC)
- `config/permissions.php` — registry centralisé (1330 lignes)
- Migration `2026_04_30_102718_add_metadata_to_roles_table.php` — colonnes `is_custom`, `label_fr`, etc.
- PR #327 — refactor custom-roles+widgets simplify (3 mai 2026)
