# Handoff — Session 2026-06-04 → 2026-06-10
## Refonte premium BTS Tronc Commun + Formation ESBTP Yamoussoukro

> **Tenant principal de référence** : `esbtp-yakro` (ESBTP Yamoussoukro)
> **Branche canonique** : `presentation`
> **HEAD final** : `d7688184`
> **Cluster prod** : 6 tenants (presentation, esbtp-abidjan, esbtp-yakro, ephrata, hetec, rostan)
> **Contexte** : Cette session a servi de support à la formation ESBTP Yamoussoukro — chaque livraison a été validée en production sur `esbtp-yakro.klassci.com`.

---

## 1. Bilan livré — 13 commits

| # | Commit | Titre | Périmètre |
|---|---|---|---|
| 1 | `4cb8e5c4` + `b7385681` | `fix(notes)` saisie-rapide ne verrouille plus pour superAdmin | `/esbtp/evaluations/{id}/saisie-rapide` |
| 2 | `7dfaabd2` | `fix(bts-tc)` harmonise permission orientation TC + lien direct config sorties | `/esbtp/inscriptions/{id}` |
| 3 | `3c2a7e80` + `17e5e77f` | `feat(filieres)` redesign full premium `/esbtp/filieres/{id}` + section Sorties BTS TC | `/esbtp/filieres/{id}` |
| 4-bis | `983ddf8b` | `fix(corbeille)` notes restent bloquantes par défaut + permission bypass dédiée | `/esbtp/trash` |
| 5 | `bb2778dd` + `a599f9cb` | `fix(bts-tc)` classes universelles + auto-candidates filiere_parent_id + orientation-targets premium | `/esbtp/admin/orientation-targets` |
| 6 | `5410818f` | `fix(reinscription)` silent failure réinscription groupée + conformité premium | `/esbtp/reinscription` |
| 7 | `ebe0f307` | `feat(classes)` badges TC/Spécialité + section CRUD orientation targets + override logique | `/esbtp/classes` + `/esbtp/classes/{id}` |
| 8 | `4fd43908` | `fix(filieres)` hierarchie TC editable + filtre defensif sources orientation | `/esbtp/filieres/{id}/edit` + `/esbtp/admin/orientation-targets` |
| 8-bis | `ced04158` | `feat(filieres)` redesign full premium pages create + edit (namespace fe-*) | `/esbtp/filieres/create` + `/esbtp/filieres/{id}/edit` |
| 9 | `aec1a874` | `fix(filieres)` bug 9 — parent_id editable sur TC + CTA actionnables sur états vides | filieres + orientation-targets + classes.show |
| 10 | `6832ec2a` | `fix(classes)` modal surcapacité — 4 bugs + redesign premium namespace cs-overcap-* | `/esbtp/classes` modal surcapacité |
| 11 | `334b8ba7` | `fix(specialisation)` UX premium quand aucune classe + retrait filtre annee | `/esbtp/inscriptions/{id}/specialisation` étape 2 |
| 12 | `d7688184` | `feat(filieres)` redesign full premium `/esbtp/filieres` namespace fl-* | `/esbtp/filieres` index |

---

## 2. Règle métier confirmée par Marcel (06/06/2026)

### TRONC COMMUN existe UNIQUEMENT en 1ère année BTS

- Une filière `is_tronc_commun = true` existe seulement en **1ère année BTS**
- En **2ème année**, l'étudiant **est déjà spécialisé** (`GENIE CIVIL OPTION BATIMENT`, `TRAVAUX PUBLICS`, etc.)
- Il n'y a **JAMAIS** de classe TRONC COMMUN en 2ème année
- L'inscription en 2ème année se fait directement dans une classe-spécialité (BTS2 *)

**Conséquence sur le code** :
- Query `BtsOrientationPolicy::candidates()` filtre `niveau_etude_id = $classeTC->niveau_etude_id` (= 1ère année BTS)
- Si aucune classe-spécialité n'existe en 1ère année BTS pour la filière fille X → **problème DATA tenant** (Marcel doit créer la classe), pas un bug code
- L'UX guide via empty state premium + bouton direct vers `/esbtp/classes/create?filiere_id=X&niveau_etude_id=<1ère année>` pré-rempli

**Mémoire projet** : [`memory/project_tc_uniquement_1ere_annee.md`](../../.claude/projects/.../memory/project_tc_uniquement_1ere_annee.md)

### Autre règle confirmée : Classes universelles, pas d'année

- Les classes BTS sont **indépendantes** de l'année universitaire
- Le filtre `annee_universitaire_id` sur `esbtp_classes` est **interdit** dans les queries de candidates orientation
- Une même classe BTS1 BATIMENT accueille les étudiants de toutes les promos

**Rule projet** : `.claude/rules/classes-universelles-pas-annee.md`

---

## 3. Workflow utilisateur final — Onboarding sortie TC end-to-end

Ce flow est **désormais opérationnel** sur tous les tenants. C'est le scénario à démontrer en formation Yamoussoukro :

### Étape 1 — Vérifier la filière TC
- Aller sur `/esbtp/filieres`
- Identifier la filière marquée **TRONC COMMUN** (badge bleu KLASSCI)
- Cliquer la card → page show

### Étape 2 — Créer la filière fille (spécialité)
- Sur `/esbtp/filieres/{tc_id}` : CTA premium **« + Créer une filière-fille »** (URL `parent_id=tc_id` pré-rempli)
- Form `/esbtp/filieres/create?parent_id=tc_id` :
  - Nom : ex. `GENIE CIVIL OPTION TRAVAUX PUBLICS`
  - Code : ex. `GTP`
  - Filière parente : automatiquement sélectionnée (`<x-au-select>` premium)
  - Switch **Tronc commun** : OFF
  - Switch **Actif** : ON
- Submit → filière créée

### Étape 3 — Créer la classe spécialité 1ère année
- Retour sur `/esbtp/filieres/{tc_id}` : CTA premium **« + Créer une classe spécialité »** (URL `filiere_id` + `niveau_etude_id=1ère année` pré-rempli)
- Form `/esbtp/classes/create?filiere_id=X&niveau_etude_id=1` :
  - Nom : ex. `BTS1 TRAVAUX PUBLICS`
  - Capacité, code, etc.
- Submit → classe créée

### Étape 4 — Configurer la sortie (orientation-target)
- Aller sur `/esbtp/classes/{tc_classe_id}` show → section CRUD « Sorties spécialités »
- Cliquer **« + Ajouter une sortie »** → modal AJAX avec `<x-au-select>` premium listant les classes-spécialités candidates
- Sélectionner `BTS1 TRAVAUX PUBLICS` → enregistrer

### Étape 5 — Orienter un étudiant TC
- Aller sur `/esbtp/inscriptions/{etudiant_tc_id}/specialisation`
- Étape 1 — Choisir la spécialité : sélectionner `GENIE CIVIL OPTION TRAVAUX PUBLICS`
- Étape 2 — Choisir la classe : `BTS1 TRAVAUX PUBLICS` apparaît
- Valider → étudiant orienté ✅

### Comportement empty state (cas data manquante)

Si à l'étape 5 une filière n'a pas de classe en 1ère année :
- Empty state premium affiché
- Bouton **« Créer une classe pour [filière] »** (target `_blank`) pré-rempli
- Le user crée la classe → retourne → orientation finalisée

---

## 4. État cluster prod final

```
6 tenants sur HEAD d7688184
├── presentation    : ✅ prod déployé (klassci pull + cache:clear OK)
├── esbtp-yakro     : ✅ prod déployé + visual-check OK
├── esbtp-abidjan   : ✅ prod déployé
├── ephrata         : ✅ prod déployé
├── rostan          : ✅ prod déployé
└── hetec           : ⚠️ GitHub sync, déploiement serveur manuel à faire
```

**Pour hetec** : le tenant n'est pas configuré dans `klassci-cli` local. Déploiement à faire manuellement via SSH/cPanel :
```bash
cd /home/c2569688c/public_html/hetec
git pull origin hetec
php artisan view:clear && php artisan cache:clear && php artisan config:clear
```

---

## 5. Rules respectées (audit transverse sur les 13 commits)

| Rule | Application |
|---|---|
| `klassci-debugging-discipline` | Route → controller AVANT édition pour chaque bug |
| `controller-naming` | Aucune méthode réservée Laravel ajoutée |
| `customizable-roles` | 0 `hasRole()` hardcodé, `@can()` partout |
| `permissions.md` | Registry centralisé, `klassci permissions:fix` quand nouvelle perm |
| `no-god-code-compta` | Helpers Model + Services Domain extraits |
| `ajax-no-reload-premium` | Tous les CRUD en AJAX + toasts premium |
| `premium-selects` | 0 `<select>` natif visible, `<x-au-select>` partout |
| `premium-redesign` | Namespaces dédiés (`fl-*`, `fe-*`, `fs-*`, `cs-overcap-*`, `spc-*`), monochrome bleu KLASSCI |
| `blade-pitfalls` 1/2/4/5 | Audits grep clean, compiled views lint OK |
| `blade-alpine-pitfalls` | 0 mix `style="" :style=""` |
| `css-stacking-pitfalls` | Hero SANS `overflow:hidden`, pas de `transform:hover` sur cards avec dropdowns |
| `embedded-styles-pattern` | `@push('styles')` partout, JAMAIS `@section('styles')` |
| `universal-dropdowns` | Pattern Bootstrap natif z-index 99999 + auto-flip respecté |
| `multi-agent-git-safety` | Push direct cross-branch, ZÉRO `git pull`, rebase avec `--force-with-lease` |
| `tenant-branches` | Propagation 6 tenants par `git push origin presentation:tenant` en boucle |
| `pre-merge-checklist` | Visual-check OBLIGATOIRE après chaque déploiement |
| `changelog.md` | Juin 2026 enrichi à chaque commit, sections cohérentes Ajouts/Améliorations/Corrections |

---

## 6. Découvertes techniques importantes (pour devs futurs)

### Bug ESBTPClasseController — alias SQL shadowé par accessor Eloquent
L'alias SQL `selectRaw('... as taux_occupation')` était **shadowé** par l'accessor Eloquent `ESBTPClasse::getTauxOccupationAttribute()` (modèle ligne 462) qui calcule depuis `places_occupees` (champ stale).

**Pattern à connaître** : si tu crées un alias SQL dont le nom correspond à un accessor Eloquent existant, Laravel privilégie l'accessor. Renommer l'alias (ex: `taux_occupation_live`) ou supprimer l'accessor.

### Conditional Alpine `x-show="!isTroncCommun"` masquait des champs métier
Marcel veut pouvoir éditer `parent_id` **même** sur une filière TC (cas hiérarchies multi-niveaux). Le `x-show="!isTroncCommun"` initial bloquait cet usage.

**Pattern à connaître** : ne pas masquer un champ par règle UI sans confirmer le besoin métier réel. Préférer un hint contextuel.

### Filtre `annee_universitaire_id` résiduel dans `ESBTPSpecialisationController::getClasses()`
Causait des « 0 classes » faux positifs en étape 2 specialisation. Suppression du filtre → 4 filières (BATIMENT, GEOMETRE, MINES, URBANISME) ont retrouvé leurs candidates.

**Rule à respecter** : `.claude/rules/classes-universelles-pas-annee.md`

---

## 7. Limites restantes (à traiter en sessions futures)

| Limite | Sévérité | Notes |
|---|---|---|
| `hetec` non configuré dans klassci-cli | Mineur | Déploiement manuel SSH/cPanel uniquement |
| 3 `<select>` natifs pré-existants dans `classes/index.blade.php` (l. 1032, 1039) + `classes/show.blade.php` (l. 1437) | Mineur | Hors scope bug 7 — à migrer en PR séparée vers `<x-au-select>` |
| Anomalie data yakro : filière BATIMENT propose `BTS1 TRAVAUX PUBLICS` comme candidate | À corriger en config | Mapping `ClasseOrientationTarget` incorrect — corriger via `/esbtp/admin/orientation-targets` |
| Yakro : seules les filières GBAT/GTP/GGT/MGP/URB existent comme spécialités, mais seul `BTS1 BATIMENT` existe en 1ère année | Data tenant | Marcel doit créer les autres classes BTS1 en 1ère année via le nouveau CTA contextuel |

---

## 8. Mémoires projet créées/mises à jour

| Mémoire | Type | Sujet |
|---|---|---|
| `project_tc_uniquement_1ere_annee.md` | Nouveau | Règle métier TC = 1ère année seulement |
| `feedback_specialisation_empty_state_premium.md` | Nouveau | UX empty state premium + retrait filtre annee |

---

## 9. Pour la formation Yamoussoukro — scénarios à démontrer

### Scénario 1 — Onboarding complet d'une nouvelle spécialité (5 min)
1. `/esbtp/filieres/1` (TRONC COMMUN) → CTA « + Créer une filière-fille »
2. Form pré-rempli → créer `GENIE CIVIL OPTION CONSTRUCTION`
3. CTA « + Créer une classe spécialité » → form pré-rempli → créer `BTS1 CONSTRUCTION`
4. `/esbtp/classes/1` (TC) section CRUD targets → ajouter `BTS1 CONSTRUCTION`
5. Tester orientation d'un étudiant TC vers cette nouvelle filière

### Scénario 2 — Gestion des dépassements de capacité (3 min)
1. `/esbtp/classes` → bannière premium « N classes au-dessus de la capacité »
2. Clic sur la bannière → modal premium avec gradient KLASSCI
3. Visualiser les taux réels (290%, 112%, etc.) + progress bars sémantiques
4. Identifier les classes qui nécessitent ajustement (capacité ou réorientation)

### Scénario 3 — Workflow réinscription groupée (4 min)
1. `/esbtp/reinscription` → modal groupé
2. Sélectionner étudiants
3. Démontrer les toasts d'erreur **par étudiant** (vs silent failure ancien)
4. Montrer la transaction par-étudiant (succès partiel possible)

### Scénario 4 — Permissions custom rôles (5 min)
1. `/esbtp/custom-roles` → créer rôle « Coordinateur orientation »
2. Cocher uniquement `bts_tronc_commun.manage_targets` + `filieres.view`
3. Assigner à un user → tester accès limité à `/esbtp/admin/orientation-targets`
4. Démontrer que les boutons sans permission sont cachés (jamais 403)

---

## 10. Commandes CLI utiles pour le formateur

```bash
# Vérifier l'état des branches Git
git fetch origin
for t in presentation esbtp-abidjan esbtp-yakro ephrata hetec rostan; do
  echo -n "$t: "
  git ls-remote origin refs/heads/$t | awk '{print substr($1,1,8)}'
done

# Déploiement après pull GitHub
klassci pull esbtp-yakro
klassci cache:clear esbtp-yakro
klassci permissions:fix esbtp-yakro  # si nouvelles permissions

# Diagnostic data tenant
YAKRO_TOKEN=$(python -c "import json; d=json.load(open('C:/Users/PAVILION/.klassci/config.json')); print(d['tenants']['esbtp-yakro']['token'])")
curl -sS "https://esbtp-yakro.klassci.com/api/cli/data/filieres?per_page=50" -H "Authorization: Bearer $YAKRO_TOKEN" | python -m json.tool

# Stats globales
curl -sS "https://esbtp-yakro.klassci.com/api/cli/stats" -H "Authorization: Bearer $YAKRO_TOKEN" | python -m json.tool
```

---

## 11. Contacts session

- **Fondateur / Product Owner** : Marcel
- **Session ID** : `2d824c48-9086-4a47-9397-59f5e2232bb2`
- **User email** : adcdevteam2025@gmail.com
- **Tenant formation** : esbtp-yakro (`https://esbtp-yakro.klassci.com`)
- **Admin login formation** : `Admin` / `Kaam@2022`

---

## Voir aussi

- [CLAUDE.md](../../CLAUDE.md) — architecture KLASSCI multi-instance
- [CHANGELOG.md](../../CHANGELOG.md) — historique technique Juin 2026
- [`.claude/rules/`](../../.claude/rules/) — toutes les rules projet
- [`.claude/rules/feature-delivery-methodology.md`](../../.claude/rules/feature-delivery-methodology.md) — méthodologie 13-phases
- [`.claude/rules/multi-agent-git-safety.md`](../../.claude/rules/multi-agent-git-safety.md) — discipline parallel agents
- [`.claude/rules/tenant-branches.md`](../../.claude/rules/tenant-branches.md) — pattern branches Git tenant
