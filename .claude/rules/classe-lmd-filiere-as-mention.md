# Rule: Classe LMD — `filiere_id` désigne une filière d'ancrage, jamais une mention

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

Plutôt que d'ajouter une colonne `mention_id` à `esbtp_classes`, la décision de
mai 2026 (« Option A ») fut d'écrire l'id de la mention **dans** `filiere_id`.

**Cette convention a été retirée en septembre 2026 : elle ne tenait que par un
hasard de numérotation.** Écrire un id de mention dans une colonne qui porte une
clé étrangère vers `esbtp_filieres` ne fonctionne que tant que les deux suites
d'identifiants coïncident. USAT compte huit mentions pour cinq filières : ses
trois mentions d'agronomie n'avaient aucune classe possible — erreur serveur avec
parcours (le parcours n'ayant pas de filière, la valeur dérivée était nulle sur
une colonne NOT NULL), refus de validation sans parcours.

## La règle actuelle

**`esbtp_classes.filiere_id` désigne TOUJOURS une filière qui existe.**

Le formulaire, lui, envoie bien un id de mention dans ce champ (le sélecteur de
mention y est posé faute de colonne dédiée). C'est le contrôleur qui convertit,
par `ESBTPClasseController::ancrerSurUneFiliereReelle()`, lequel s'appuie sur
`App\Services\LMD\FiliereMiroirLmd` :

- parcours fourni → `pourParcours()` : sa filière si elle existe, sinon un reflet
  créé à son nom et à son code, et le parcours y est rattaché ;
- mention seule (tronc commun) → `pourMention()` : le reflet de la mention.

Un **reflet** est une filière marquée par `lmd_mention_id` ou `lmd_parcours_id`.
Ces colonnes sont la seule façon de le reconnaître : sur une instance mixte, un
parcours LMD pointe **légitimement** vers une vraie filière BTS équivalente (la
rétro-compat des planifications), donc « un parcours pointe vers moi » ne veut pas
dire « je suis un reflet ». Le scope `ESBTPFiliere::horsMiroirLmd()` les écarte
des écrans où une personne choisit une filière BTS.

→ Côté lecture (planifications, bulletins, notes), rien ne change : `filiere_id`
reste cohérent avec la jointure canonique `esbtp_planifications_academiques
(filiere_id + niveau_etude_id + semestre)` documentée dans `klassci-classe-matieres.md`.

## Pourquoi pas simplement rendre `filiere_id` nullable

C'est la première idée, et elle est mauvaise pour deux raisons — dont aucune
n'est « ça planterait partout ». Relevé sur la branche : la très grande majorité
des lectures de `filiere_id` tolèrent déjà le nul (`??`, `optional()`, `?->`, ou
un `if` englobant), et plusieurs sites ont même été écrits en l'anticipant
(`ESBTPClasseController:1134` et `:1161`, `ClassPlanningService:54`,
`BtsBulletinSubjectResolver:38`). Les vraies casses se comptent sur une main.

1. **Ça ne résout rien.** Sans colonne `mention_id` sur `esbtp_classes`, le
   rattachement à la mention n'a toujours nulle part où vivre. C'est plus de
   travail que le reflet, pas moins.
2. **Le coût n'est pas le plantage, c'est la perte silencieuse.** Une quarantaine
   de filtres de la forme `where('filiere_id', $classe->filiere_id)` deviendraient
   `= NULL` : zéro ligne, aucune erreur (`BulletinService`, `ESBTPInscriptionService`,
   `ExamenSchedulingService`, `EcheancierAdminService`, le listing `/esbtp/classes`…).
   Et `ESBTPFiliereController:380`, le garde qui refuse de supprimer une filière
   portant encore des classes, deviendrait faux.

Un bulletin vide ne se remarque pas comme un écran d'erreur.

## Cas de figure

| Cas | systeme_academique | `filiere_id` stocké | `parcours_id` | Interprétation |
|---|---|---|---|---|
| BTS classique | BTS | filière BTS | NULL | Inchangé |
| LMD tronc commun | LMD | reflet de la **mention** | NULL | Classe ouverte à toute la mention |
| LMD avec parcours | LMD | filière du parcours, ou son reflet | parcours | Classe spécialisée |
| LMD **avant sept. 2026** | LMD | peut porter un id de mention | NULL | Donnée héritée, tolérée en lecture |

La dernière ligne explique les replis présents dans le code : le formulaire
d'édition retente l'ancienne interprétation quand la filière n'est pas un reflet,
et la validation LMD accepte l'une ou l'autre table. Ne les retirez pas sans avoir
relevé les données réelles de chaque instance.

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

1. ❌ Écrire dans `esbtp_classes.filiere_id` un id qui n'est pas celui d'une
   filière (id de mention, id de parcours). Passer par
   `ESBTPClasseController::ancrerSurUneFiliereReelle()` ou `FiliereMiroirLmd`.
2. ❌ Dériver `filiere_id` de `parcours->filiere_id` sans vérifier qu'elle existe :
   cette colonne est **nullable**, et trois parcours d'USAT l'avaient nulle. C'est
   exactement ce qui rendait la création de classe impossible.
3. ❌ Reconnaître un reflet à « un parcours pointe vers cette filière ». Sur les
   instances mixtes c'est vrai de vraies filières BTS. Seules les colonnes
   `lmd_mention_id` / `lmd_parcours_id` le disent — ou `estMiroirLmd()`.
4. ❌ Filtrer les classes par `filiere_id` en supposant uniquement filière BTS —
   en LMD c'est un reflet. Toujours `where systeme_academique` en plus.
5. ❌ Hardcoder `LMD_TYPES = ['Licence', 'Master']` — utiliser
   `ClasseManagementService::LMD_TYPES` (source de vérité)
6. ❌ Submit le form avec 2 inputs `name="filiere_id"` actifs simultanément
   (un BTS + un LMD picker) — utiliser le pattern `<fieldset :disabled>`
7. ❌ Tester un fix LMD uniquement sur le modal AJAX sans tester `/esbtp/classes/create`
   page entière (et inversement)

## Voir aussi

- Rule globale : `~/.claude/rules/klassci-classe-matieres.md` — source canonique
  des matières via `esbtp_planifications_academiques`
- `app/Services/ClasseManagementService.php` — `determinerSystemeAcademique()` +
  `LMD_TYPES` constante
- `app/Models/ESBTPLMDParcours.php` — relation `filiere()` qui sert au derive
- Mémoire projet : `lmd-business-rules.md` — règles métier LMD complètes
- PR `feat/classes-lmd-aware-form` (mai 2026) — implémentation initiale Option A
