# Rule: Jury de délibération LMD UEMOA — workflow + PV légal

## Quand s'active

Cette rule s'active quand tu :
- Travailles sur les tables `esbtp_lmd_jurys`, `esbtp_lmd_jury_membres`, `esbtp_lmd_jury_decisions`
- Modifies `app/Services/JuryDeliberationService.php`
- Travailles sur `app/Http/Controllers/ESBTPLMDJuryController.php`
- Touches aux vues `resources/views/esbtp/lmd/jurys/*` (namespace `juy-*`)
- Implémentes le PV PDF template `resources/views/pdf/lmd-jury-pv.blade.php`

## Règle fondamentale

Un jury de délibération KLASSCI suit le workflow UEMOA officiel avec PV PDF légal archivé 5 ans (Côte d'Ivoire MENA).

## Workflow obligatoire

```
1. PRÉPARATION (status=preparation)
   - Composition jury : Président + Assesseurs + Secrétaire (+ Consultatifs optionnel)
   - Vérification quorum (settings tenant lmd_jury_quorum_min, lmd_jury_quorum_assesseurs_min)
   - Notification membres jury

2. EN COURS (status=en_cours)
   - Calcul auto décisions (JuryDeliberationService::calculerDecisionAuto)
   - Override jury individuel avec motif OBLIGATOIRE si ≠ décision auto
   - Vote consigné si non unanime
   - AJAX no-reload partout (rule ajax-no-reload-premium)

3. CLOS (status=clos)
   - Signatures membres recueillies (canvas HTML5 ou checkbox selon setting lmd_jury_signature_required)
   - PV PDF généré avec numéro séquentiel thread-safe (DB lock)
   - Décisions verrouillées (impossible de modifier après lock)

4. PUBLIÉ (status=publie)
   - Notifications étudiants
   - Bulletins LMD générés automatiquement (via LMDBulletinService)
   - Archivage légal 5 ans (setting lmd_pv_retention_years)

5. ARCHIVE (status=archive, automatique J+1 an)
```

## Décisions canoniques UEMOA

| Décision | Code DB | Critères |
|---|---|---|
| ADMIS | `admis` | Moyenne ≥ 10, tous crédits validés |
| ADMISSION_RATTRAPAGE | `admission_rattrapage` | Éligible 2e session sur ECUE non validés |
| AJOURNÉ | `ajourne` | 2e session échouée → repasse année |
| EXCLU | `exclu` | Exclusion académique (motif obligatoire) |
| ADMIS_SOUS_CONDITION | `admis_sous_condition` | Crédits manquants mais jury accepte (motif obligatoire) |
| DEFERE_AU_RECTORAT | `defere` | Cas exceptionnels (médical, force majeure) |

## Mentions canoniques (settings tenant configurables)

| Mention | Setting tenant | Default |
|---|---|---|
| Passable | `lmd_mention_p_threshold` | 10 |
| Assez Bien | `lmd_mention_ab_threshold` | 12 |
| Bien | `lmd_mention_b_threshold` | 14 |
| Très Bien | `lmd_mention_tb_threshold` | 16 |
| Excellent (rare) | hardcoded | 18 |

## Composition jury

- **Président** (1, obligatoire — généralement Responsable UE chef département)
- **Assesseurs** (N, enseignants UE de la session)
- **Secrétaire** (1, admin scolarité — saisit le PV)
- **Membres consultatifs** (optionnel)

Quorum minimum : setting tenant `lmd_jury_quorum_min` (default 2 = président + secrétaire).

## PV de délibération — règles immuables

### Numérotation séquentielle

Format : `PV-{ANNEE_UNIVERSITAIRE}-{TENANT_CODE}-{NUMERO_SEQ_4_DIGITS}`

Exemple : `PV-2025-2026-PRESENTATION-0042`

```php
public function reserverNumeroPv(int $anneeUniversitaireId): string
{
    return DB::transaction(function () use ($anneeUniversitaireId) {
        $lastNumero = ESBTPLMDJury::where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereNotNull('pv_numero')
            ->lockForUpdate()  // ⚠️ OBLIGATOIRE thread-safe
            ->max(DB::raw("CAST(SUBSTRING_INDEX(pv_numero, '-', -1) AS UNSIGNED)"));

        // ... format ...
    });
}
```

### Storage

```
storage/pv/{tenant_code}/{annee_universitaire}/{numero}.pdf
```

### Archivage légal

- **Rétention minimum** : 5 ans (Côte d'Ivoire MENA — setting `lmd_pv_retention_years`)
- **Soft delete uniquement** (`SoftDeletes` trait)
- **Audit log immutable** : Auditable trait avec whitelist complète (numero, status, decisions_summary, pv_url, pv_genere_at)

## Anti-patterns à bloquer en review

1. ❌ **Override décision sans motif** — DB constraint NOT NULL sur `motif_override` si `override_par_jury=true`
2. ❌ **Modification décision après lock PV** — audit log doit le bloquer, ajouter scope `notLocked()` sur queries d'update
3. ❌ **Numéro PV généré côté client** — race condition garantie en multi-user prod, utiliser DB lock
4. ❌ **Soft delete PV** — JAMAIS de delete réel, rétention légale 5 ans
5. ❌ **Hardcoder pondérations CC/Examen Terminal** — utiliser settings tenant `lmd_cc_weight` / `lmd_exam_weight`
6. ❌ **Page reload après une action jury** — rule `ajax-no-reload-premium.md`
7. ❌ **Signature digitale stockée sans IP/timestamp/audit log** — preuve légale insuffisante
8. ❌ **Calcul décision auto sans charger settings tenant** — diffère par école
9. ❌ **Permission `lmd.jury.deliberate` non vérifiée** sur les endpoints d'override
10. ❌ **Génération PV avant lock notes** — workflow doit être atomique

## API minimum JuryDeliberationService

```php
public function calculerDecisionAuto(ESBTPEtudiant $etudiant, ESBTPLMDJury $jury): array;
public function appliquerDecisionsAuto(ESBTPLMDJury $jury): int;
public function overrideDecision(
    ESBTPLMDJury $jury,
    ESBTPEtudiant $etudiant,
    string $nouvelleDecision,
    string $motif,
    ?string $voteResultat = null
): ESBTPLMDJuryDecision;
public function reserverNumeroPv(int $anneeUniversitaireId): string;
public function genererPvDeliberation(ESBTPLMDJury $jury): string;
public function publierDecisions(ESBTPLMDJury $jury): void;
public function verifierQuorum(ESBTPLMDJury $jury): array;
```

## Settings tenant obligatoires

```php
'lmd_jury_quorum_min' => 2,
'lmd_jury_quorum_assesseurs_min' => 1,
'lmd_jury_signature_required' => true,
'lmd_pv_retention_years' => 5,
'lmd_compensation_enabled' => true,
'lmd_compensation_min_note' => 0,
'lmd_intra_ue_compensation' => true,
'lmd_cc_weight' => 40,
'lmd_exam_weight' => 60,
'lmd_note_eliminatoire' => 0,
'lmd_mention_p_threshold' => 10,
'lmd_mention_ab_threshold' => 12,
'lmd_mention_b_threshold' => 14,
'lmd_mention_tb_threshold' => 16,
```

## Voir aussi

- Memory projet : `feedback_jury_uemoa_workflow.md`
- Rule projet : `ajax-no-reload-premium.md`
- Rule projet : `customizable-roles.md` (permissions jury via custom roles)
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md` (PR11-13)
- Skill : `klassci-jury-lifecycle`
- Directive 03/2007/CM/UEMOA LMD
- Manuel LMD CAMES 2023
