# Rule: Classe LMD — Convention `filiere_id` sert sémantiquement de Mention

## Quand s'active

Cette rule s'active quand tu travailles sur :
- `app/Models/ESBTPClasse.php` (et ses migrations)
- `app/Http/Controllers/ESBTPClasseController.php` méthodes `store()` / `update()`
- `app/Http/Requests/Classe/StoreClasseRequest.php` / `UpdateClasseRequest.php`
- `resources/views/esbtp/classes/partials/form.blade.php` (form LMD-aware)
- `resources/views/components/au-mention-picker.blade.php` (picker LMD)
- Toute requête qui filtre les classes par `filiere_id` en supposant qu'il s'agit
  uniquement d'une filière BTS

## Pourquoi cette rule existe

L'app KLASSCIv2 a démarré 100% BTS où `esbtp_classes.filiere_id` pointait toujours
sur `esbtp_filieres` (une filière BTS classique : Génie Civil, Informatique, etc.).

L'arrivée du système LMD (UEMOA) a ajouté trois nouvelles tables :
- `esbtp_lmd_domaines` — Sciences, Lettres, Droit…
- `esbtp_lmd_mentions` — Sciences de la Vie, Droit Privé… (appartient à un domaine)
- `esbtp_lmd_parcours` — Biologie Moléculaire, Droit des Affaires…
  (appartient à une mention, a un `filiere_id` qui pointe sur une filière BTS
  équivalente pour rétro-compat des planifications académiques)

Plutôt que d'ajouter une colonne `mention_id` à `esbtp_classes` (qui nécessitait
une migration multi-instance + backfill + gestion de nullability croisée), la
décision validée par Marcel (Option A, 14 mai 2026) est :

**En mode LMD, `esbtp_classes.filiere_id` sert sémantiquement de `mention_id`.**

Quand un Parcours est aussi sélectionné, le controller dérive automatiquement la
"vraie" `filiere_id` (filière BTS équivalente) depuis `parcours.filiere_id` :

```php
// ESBTPClasseController::store() L325-329
if (!empty($validatedData['parcours_id'])) {
    $parcours = ESBTPLMDParcours::findOrFail($validatedData['parcours_id']);
    $validatedData['filiere_id'] = $parcours->filiere_id;
}
```

→ Coté lecture (planifications, bulletins, notes), `filiere_id` reste cohérent
avec la jointure canonique `esbtp_planifications_academiques (filiere_id +
niveau_etude_id + semestre)` documentée dans la rule globale `klassci-classe-matieres.md`.

## Cas de figure

| Cas | systeme_academique | filiere_id stocké | parcours_id stocké | Interprétation |
|---|---|---|---|---|
| BTS classique | BTS | filière BTS (ex: Génie Civil) | NULL | Pattern legacy, inchangé |
| LMD tronc commun (mention) | LMD | **mention_id** (ex: Sciences de la Vie) | NULL | Classe ouverte L1 commune à toute la mention |
| LMD avec parcours | LMD | filière BTS dérivée (ex: Biologie) | parcours (ex: Bio Moléculaire) | Classe spécialisée |

Note : dans le 3ᵉ cas, `filiere_id` final est la filière dérivée du parcours,
PAS l'ID de la mention. Le formulaire envoie `filiere_id = mention_id` mais le
controller le remplace par `parcours.filiere_id` avant le save.

## Validation côté FormRequest

`StoreClasseRequest` + `UpdateClasseRequest` détectent le mode via :
```php
$niveau = ESBTPNiveauEtude::find($this->input('niveau_etude_id'));
$isLmd = in_array($niveau->type, ClasseManagementService::LMD_TYPES, true);
// LMD_TYPES = ['Licence', 'Master', 'Doctorat']
```

Règles appliquées :
- **BTS** : `filiere_id` required (pointe sur `esbtp_filieres`)
- **LMD sans parcours** : `filiere_id` required (pointe sur le slot mention)
- **LMD avec parcours** : `filiere_id` nullable (sera dérivé serveur-side)
- **LMD sans rien** : 422 avec message "Mention requise en mode LMD"
- **Cohérence mention/parcours** : si les 2 sont fournis et `parcours.mention_id != filiere_id`, 422

## Convention UI (form LMD-aware)

Le formulaire `esbtp/classes/partials/form.blade.php` affiche un seul champ
`name="filiere_id"` actif à la fois :
- Mode BTS : `<select name="filiere_id">` natif (options = filières BTS actives)
- Mode LMD : `<x-au-mention-picker name="filiere_id">` (premium picker grouped by Domaine)

Les deux fieldsets sont wrappés en `<fieldset :disabled>` pour que le browser
n'envoie qu'un seul `filiere_id` au submit (le `disabled` attribute exclut tous
les inputs descendants du form data).

## Anti-patterns à BLOQUER en review

1. ❌ Ajouter un champ `mention_id` direct à `esbtp_classes` sans plan migration
   multi-instance — la convention `filiere_id == mention_id en LMD` doit rester
2. ❌ Filtrer les classes par `filiere_id` en supposant uniquement filière BTS —
   en LMD, ça peut être une mention. Toujours `where systeme_academique` en plus
3. ❌ Hardcoder `LMD_TYPES = ['Licence', 'Master']` — utiliser
   `ClasseManagementService::LMD_TYPES` (source de vérité)
4. ❌ Submit le form avec 2 inputs `name="filiere_id"` actifs simultanément
   (un BTS + un LMD picker) — utiliser le pattern `<fieldset :disabled>`
5. ❌ Tester un fix LMD uniquement sur le modal AJAX sans tester `/esbtp/classes/create`
   page entière (et inversement)

## Voir aussi

- Rule globale : `~/.claude/rules/klassci-classe-matieres.md` — source canonique
  des matières via `esbtp_planifications_academiques`
- `app/Services/ClasseManagementService.php` — `determinerSystemeAcademique()` +
  `LMD_TYPES` constante
- `app/Models/ESBTPLMDParcours.php` — relation `filiere()` qui sert au derive
- Mémoire projet : `lmd-business-rules.md` — règles métier LMD complètes
- PR `feat/classes-lmd-aware-form` (mai 2026) — implémentation initiale Option A
