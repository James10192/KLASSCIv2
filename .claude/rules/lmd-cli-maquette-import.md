# Rule: Import de maquettes LMD via klassci-cli / API `/api/cli/lmd/import`

## Quand s'active

Quand tu :
- Intègres une **maquette LMD** (liste des matières d'une classe : UE + ECUE + crédits) depuis un PDF/Excel vers un tenant
- Appelles `POST /api/cli/lmd/import`, `/api/cli/lmd/link-ues`, `/api/cli/lmd/link-classes`, `/api/cli/lmd/cleanup`, `/api/cli/lmd/tree`
- Touches à `app/Services/LMD/LMDImportService.php`, `ParcoursUeSyncService`, `CLILMDSetupController`
- Peuples les matières d'un parcours Génie Civil sur **esbtp-abidjan** (chantier maquettes Mme Santana, juin 2026)

## Architecture du partage (CRUCIAL)

Une **UE et ses ECUE peuvent être partagées** entre plusieurs domaines / mentions / parcours / semestres (confirmé Marcel, juin 2026). Le partage est matérialisé par des **pivots many-to-many**, PAS par les FK directes (legacy) :

| Lien | FK directe (legacy) | Pivot many-to-many (partage) |
|---|---|---|
| UE → Parcours | `esbtp_unites_enseignement.parcours_id` | **`esbtp_lmd_parcours_ue`** (`ParcoursUeSyncService`) |
| Matière(ECUE) → UE | `esbtp_matieres.unite_enseignement_id` | **`esbtp_ue_matiere`** |
| Matière → Filière | (supprimée 2025-04) | **`esbtp_matiere_filiere`** |

`ESBTPUniteEnseignement::ecues()` lit **le pivot `esbtp_ue_matiere` d'abord**, et retombe sur le `hasMany` (FK directe) seulement si le pivot est vide.

## ⚠️ Limite de l'`import` : pivot UE↔parcours OUI, pivot ECUE↔UE NON

`LMDImportService::import()` :
- **UE↔Parcours** : appelle `parcoursUeSync->sync($parcours, $links, detachMissing:false)` → peuple le pivot `esbtp_lmd_parcours_ue` en **mode append** (jamais de detach). Une même UE (même code) importée pour 2 parcours est correctement partagée. ✅
- **ECUE↔UE** : `upsertECUE()` écrit **seulement** `esbtp_matieres.unite_enseignement_id` (FK simple). Il **ne touche pas** `esbtp_ue_matiere`.

**Piège** : si une même matière (même `code`, ex `BPM311` RDM) est importée sous **deux UE différentes** (ex PM de BU `BPM3` et PM de TP `TPPM3`), le 2ᵉ import **réécrit** `unite_enseignement_id` → la matière n'appartient plus qu'à la dernière UE. Comme l'import ne peuple pas `esbtp_ue_matiere`, `ecues()` retombe sur la FK directe → **la matière disparaît de la 1ʳᵉ UE**. Codes UE et ECUE sont **globalement uniques** (`where('code')`).

→ Pour qu'une ECUE soit réellement partagée entre 2 UE distinctes, il faut **peupler `esbtp_ue_matiere`** (les 2 `unite_enseignement_id`), ce que l'import ne fait pas nativement. Sinon : donner à chaque parcours sa propre ECUE (code distinct).

## Idempotence & sécurité

- `import` = upsert idempotent par `code` (domaine/mention/parcours/filière/UE/ECUE). Re-run = no-op si inchangé. **PAS de dry-run** sur `import` → la revue humaine du payload EST le dry-run.
- `cleanup` (soft-delete UE/ECUE/planif d'un parcours pour ré-import propre) = `dry_run:true` par défaut. UE avec ECUE portant des évaluations = protégée.
- `link-ues` / `link-classes` = `append` / `dry_run` par défaut (jamais de detach silencieux).
- Token requis : `cli:admin` (write), `cli:read` (tree).

## Schéma payload `import`

```jsonc
{
  "domaine":  {"name","code"},
  "mention":  {"name","code"},
  "parcours": {"name","code","credits_licence":180},
  "filiere":  {"name","code"},               // requis pour les planifications
  "niveaux":  [{"name":"Licence 1","year":1}], // match firstOrCreate(year+type='Licence')
  "ues": [{
    "code","name","type_ue","credit","niveau_year":1,"semestre":1,
    "is_optional":false,"ordre":0,
    "ecues":[{"code","name","credit_ecue","cm","td","tp","projet","tpe"}]
  }]
}
```
- `type_ue` absent → défaut `TypeUE::FONDAMENTALE`.
- Chaque semestre LMD UEMOA = **30 crédits** (somme des `credit` des UE). Valider AVANT d'envoyer.
- Codes maquette à nettoyer à la source : points parasites (`BPM311.` → `BPM311`), doublons, séquences sautées.

## Workflow recommandé (prod)

1. `GET /api/cli/lmd/tree` → récupérer codes domaine/mention/parcours/filière **existants** (réutiliser, ne pas dupliquer).
2. Transcrire la maquette → payload JSON ; **valider somme crédits = 30/semestre** + cohérence UE = Σ ECUE.
3. Décider du sort des ECUE réellement partagées entre UE (cf piège ci-dessus).
4. **Faire valider le payload** par le décideur (pas de dry-run API ; tenant prod = >2000 étudiants).
5. `POST /api/cli/lmd/import` ; vérifier `stats` retournées.
6. Re-`GET /tree` + contrôle visuel `/esbtp/lmd/ue` filtré sur le parcours.

## État chantier Génie Civil esbtp-abidjan (juin 2026)

- Domaine `ST` (Sciences et Technologies) · Mention `GC` (Génie Civil).
- Parcours `BU` (Bâtiment et Urbanisme, filière BU) et `TIR` (Travaux Publics, filière TP).
- Déjà intégrés (5 maquettes) : L1-BU-S2, L2-TP-S4, L3-TIR-S6, L1-BU(doublon écarté), L3-BU-S6.
- Reçus à intégrer : **S1 L1 BU** (`MAQUETTE SEM1 LBU 1.pdf`) → parcours BU ; **S1 L1 TP** (`MAQUETTE SEM1 LTP1.pdf`) → parcours TIR.
- Restants attendus de l'école : 7 maquettes-semestres (cf `RAPPORT-ESBTP-Abidjan-Maquettes-LMD.pdf`).

## Référentiel UEMOA / CAMES — crédits & tronc commun (deep research, juin 2026)

Sourcé pour trancher les divergences de crédits entre maquettes d'un même niveau/semestre :

- **Directive 03/2007/CM/UEMOA** : division en **semestres de 30 crédits** (Licence = 180, Master = 300, Doctorat = 480). Obligation **contraignante** du 30/semestre. [e-docucenter.uemoa.int](https://e-docucenter.uemoa.int/fr/directive-ndeg032007cmuemoa-portant-adoption-du-systeme-licence-master-doctorat-lmd-dans-les) · [dge.gouv.ci (PDF)](https://www.dge.gouv.ci/sites/default/files/tableau/TEXTES%20COMMUNAUTAIRES%20CLASSSIFIES/UEMOA/DIRECTIVE/Directive-%20n%C2%B0%2003-2007-cm-uemoa_portant_adoption_lmd.pdf)
- **Répartition par typologie d'UE** : UE Fondamentales + Transversales = **75–85 %** des 30 crédits du semestre ; optionnelles/complémentaires = 15–25 %. → check de validation utile sur une maquette.
- **Les crédits PAR UE ne sont PAS prescrits par UEMOA** : chaque établissement les fixe librement, tant que le semestre totalise 30. Donc des crédits différents pour la même matière entre 2 parcours **ne violent aucun standard** — ce n'est ni interdit ni imposé. Seule l'école tranche si c'est voulu ou une erreur de saisie.
- **Mutualisation L1 NON imposée** : aucune règle UEMOA n'oblige un tronc commun L1 identique entre parcours d'une même mention. C'est une **pratique** d'étendue institutionnelle. Réf. : [Manuel LMD CAMES](https://www.lecames.org/wp-content/uploads/2023/11/Manuel_LMD_CAMES.pdf).
- **ESBTP (Côte d'Ivoire)** : modèle annoncé « **tronc commun** puis 5 options (Bâtiment, Travaux Publics, Urbanisme, Topographie…) ». [esbtp-ci.net](https://www.esbtp-ci.net/) → cohérent avec un L1 largement mutualisé entre BU et TP.

**Règle de décision crédits divergents** : ne JAMAIS auto-réécrire les crédits d'une maquette contre un « standard UEMOA par UE » qui n'existe pas. Respecter la source (chaque semestre = 30), importer tel quel, et **exposer les quasi-doublons (même matière, code/crédit/coef différents entre parcours) dans une UI de réconciliation** que l'école pilote. Seul invariant à valider automatiquement : Σ crédits UE du semestre = 30 (et idéalement le ratio 75–85 % fondamental+transversal).

## Feature « Réconciliation des doublons UE/ECUE » (à construire)

Demande Marcel (juin 2026) : quand code / crédit / coefficient divergent pour la même matière entre parcours/mentions/domaines/semestres, garder les entités distinctes MAIS offrir un **bouton intelligent de détection + fusion** dans l'UI LMD :
- **Détection** : matcher les quasi-doublons par similarité de `name` (normalisé) à travers UE/ECUE, signaler les écarts de `code` / `credit` / `coefficient` / `niveau` / `semestre`.
- **Anticipation** : proposer la fusion vers une entité partagée (1 UE/ECUE liée à N parcours via pivots `esbtp_lmd_parcours_ue` / `esbtp_ue_matiere`) OU le maintien distinct, au choix.
- **Sécurité** : ne jamais fusionner une matière portant des évaluations/notes sans confirmation forte ; dry-run + aperçu de l'impact ; audit.
- **UEMOA-aware** : après fusion, re-valider Σ crédits = 30/semestre par parcours.

## Voir aussi

- `app/Services/LMD/LMDImportService.php` · `ParcoursUeSyncService` · `CLILMDSetupController`
- `.claude/rules/classe-lmd-filiere-as-mention.md` · `.claude/rules/lmd-bts-matieres-single-source.md`
- `RAPPORT-ESBTP-Abidjan-Maquettes-LMD.pdf` (note de suivi ADC)
