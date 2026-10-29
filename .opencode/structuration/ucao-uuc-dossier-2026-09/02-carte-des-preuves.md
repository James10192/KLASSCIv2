# 02 — Carte des preuves

Observation : 15 septembre 2026. Aucun test applicatif n’a été **exécuté** dans cette passe (MySQL local refusé). Les tests cités existent comme fichiers.

## 1. Dépôts

| | KLASSCIv2 | adminKlassci |
|---|---|---|
| Remote | `https://github.com/James10192/KLASSCIv2.git` | `https://github.com/James10192/adminKlassci.git` |
| Chemin local | `...\DEV\KLASSCIv2` | `...\DEV\klassci-master` |
| Branche de travail locale | `fix/revue-thermo-14-09` | `main` **en retard** |
| SHA observé (worktree) | `eafab30b1098022110de56415c5438c9190b70cf` 15/09 18:25 UTC | `676362296b1d7031e3d43559dbf8ffdb7a0c7dfb` 23/04/2026 |
| SHA canon distant | `origin/presentation` = `eafab30b1` | `origin/main` = `b4bff89821ce9cf565f6f9542a6cf4e293119bc4` 14/09 23:03 UTC |
| Tip distant | `fix(frais): une baisse de tarif pose un ecart prevu…` | `feat(provisionnement): poser le fuseau… (#106)` |
| Laravel lock | **v9.52.22** | **v12.33.0** |
| PHP | `^8.3` | (Laravel 12) |
| Autres | Spatie permission ^5, Owen-it ^13, DomPDF ^2, Gemini pkg ^2 (chatbot **Claude** en live) | Filament **v3.3.43** |

`James10192/KLASSCI` : hors périmètre (repo vide au repérage ; non rouvert ici).

**Écart documentaire** : `AGENTS.md` annonce Laravel 12.x pour KLASSCI. Le lock est 9.52.22, le require `"^9.0|^10.0"`. **Ne pas lancer une migration de framework** pour UCAO.

Le clone local adminKlassci **n’inclut pas** le fuseau. La preuve du fuseau est `git show origin/main:app/Console/Commands/TenantProvision.php` (`--timezone=`, `APP_TIMEZONE`, `DB_TIMEZONE`).

## 2. Branches d’audit

| Branche | vs canon | Constat |
|---|---|---|
| `origin/claude/ucao-uuc-audit-services-oi2uyn` (KLASSCIv2) | 33 avance / 9 retard vs `presentation` (tip `e369ad7bf` 15/09 07:33) | Existe. Contient EDT unifié, SoD 3 états, moyenne annuelle, import notes, `CompositionDuBulletin`, Celtiis dans `ModePaiement`. **Non fusionné, non déployé.** Annonces du markdown 14–15/09 ≠ production. |
| `origin/ucao-benin` | 0/0 vs `presentation` | Snapshot identique à `presentation` au SHA `eafab30b1`. |
| `origin/claude/ucao-uuc-audit-services-oi2uyn` (adminKlassci) | tip `9992312` 14/09 21:01 | Fuseau. **Dépassé** : même objet mergé dans `main` #106 (`b4bff89`). |

Les 9 commits de `presentation` absents de l’audit branch incluent les hotfixs séances LMD, reçus mobiles, régén frais à la baisse (cette session). Fusion = rebase, pas fast-forward.

## 3. Lectures de départ — revalidation

| Point | Annonce 15/09 (prompt) | Constat actuel |
|---|---|---|
| Laravel 12 vs lock 9.52.22 | à vérifier | **Confirmé** lock 9.52.22. |
| TPE `ESBTPTpeDeclaration` + stratégies | existent | **Confirmé.** Routes `esbtp.tpe-journal.*`. Binding `AppServiceProvider` ~104. Planification TPE **hard-block** `ESBTPSeanceCoursController` ~427. Tests unitaires seulement, pas HTTP. |
| `TeacherHoursService` | estimation = durée planifiée | **Confirmé** L162–166. Paie s’en sert. |
| `PayrollComputationService` | défauts CI | **Confirmé** L48–59 ITS ; CNPS 6,3 %. `TeacherRegime` non lu. |
| `LmdAcademicRuleProfile` CC/examen | ne pilotent pas | **Confirmé** L53–71 + UI settings ~3582 + omission PV. |
| Reconciliation | sessions / caisse | **Confirmé** : comptage par **mode** vs paiements validés. Pas de relevé bancaire. Pas fournisseur. |
| `permissions.php` / `sod.php` | présents | Permissions : source de vérité. `sod.php` = **jury LMD seulement**. Finance SoD = autre mécanisme (settings + `SeparationOfDutiesGuard`). |
| `TenantProvision` | DB + dir + branche + fuseau | Fuseau **sur origin/main**, pas sur le worktree local avril. Utilisateur MySQL **partagé**. SSL/sous-domaine simulés. |

## 4. Parcours inspectés (non exhaustif, suffisant pour classer)

Scolarité LMD : `routes/web.php` ~3111–3376 ; `ESBTPLMDUEController`, `ESBTPLMDNoteController`, `ESBTPLMDBulletinController`, `ESBTPLMDJuryController`, `LMDBulletinService`, `JuryDeliberationService`, `OfficialDocumentService`, `LmdCreditWalletService`, `CompositionUe`.

Finance : `ESBTPFraisController`, `ESBTPPaiementController`, `AvoirService`, `Echeancier*`, `ESBTPReconciliationController`, `ESBTPCashSessionController`, `ESBTPSalaireController`, `ESBTPJournalCaisseController`. Tables `esbtp_depenses` / `esbtp_fournisseurs` **sans** modèles Eloquent vivants.

Admin : `config/permissions.php:33-125`, `AuthServiceProvider` Gate::before, `PaywallMiddleware`, `ESBTPAuditController`, `PhoneNormalizer`, `TelephoneSettingsService`.

EDT : `ESBTPSeanceCours::estEnConflitAvec`, `salle` string, `Classroom` non branché.

IA : `ClaudeAgentService` tools lecture ; `GeminiAgentService` non câblé.

adminKlassci origin/main : `TenantProvision`, `TenantBackup`, `GroupPayrollProvider` (agrège `esbtp_salaires` des tenants — **paie KLASSCI enseignants**, pas ADC Paie).

## 5. Vérifications exécutées / non exécutées

| Vérification | Résultat |
|---|---|
| `git rev-parse` / `git log` / `git diff --stat` deux repos | Exécuté |
| Lecture fichiers + grep symboles | Exécuté |
| `git show origin/main:TenantProvision.php` | Exécuté |
| PHPUnit | **Non exécuté** (SQLSTATE 2002 local) |
| Instance `ucao-benin` en production | **Non inspectée** (pas d’accès tenant) |
| ADC Paie code / API | **Non retrouvé** dans les deux dépôts |
| Sites UCAO | Injoignables / « déploiement en cours » |
| Artifact Claude `7Qg7FD5C2itssDyzjedHFy` | Login wall. Contenu local : `ce-quil-faut-faire.md` seulement |

## 6. Sources métier

- Entretiens Marcel 15/09 (organigramme, TPE sur site, agréments, SP, achats, ADC Paie, pointage IP) — **pratique rapportée**.
- Dossier markdown 14–15/09 (artifact) — **source à réconcilier**, pas preuve de déploiement.
- Runbook `docs/runbooks/ucao-benin-mise-en-service.md` — opérationnel, partiellement en avance sur le clone local adminKlassci.

## 7. Rapprochement avec le dossier 14–15 septembre

| Affirmation ancienne | Verdict 15/09 soir |
|---|---|
| Chaîne LMD ~85 % déjà là | **Conservée** comme ordre de grandeur informel. Chaîne structure→notes→jury→relevé **présente**. Manquent gel, équivalence, attestation de réussite, 1 moyenne. |
| Composition gelée dans le bulletin | **Livrée sur branche d’audit** (`CompositionDuBulletin.php` dans le diff). **Absente de `presentation`.** |
| Import notes colonnes perdues | Correctif sur branche d’audit. `presentation` : à vérifier lot par lot. |
| Conflits EDT écrits non fusionnés | **Toujours vrai** pour `presentation`. |
| SoD OHADA 3 états | Branche d’audit. `presentation` : SoD jury + reconcil/paie séparés. |
| Celtiis Cash manquant | Toujours manquant sur `presentation` (`ModePaiement`). Branche d’audit l’ajoute. |
| Fuseau provisionné | **Vrai sur origin/main adminKlassci**, faux sur clone local avril. |
| Couverture / charge / conformité juridique du markdown | **Non preuves.** Ignorées. |
| « Aucun système chez UCAO » | Non repris. |
| DSI = serviceTechnique | **Corrigé** (organigramme 15/09). |

## 8. Limites de cette passe

- Pas d’accès à la base `ucao-benin` ni aux fichiers cPanel.
- Pas le texte intégral de la directive UEMOA 03/2007 (page d’accueil seulement visée, non ouverte ici).
- APDP : SPA JS, pas de texte de procédure récupéré.
- DGI Bénin `impots.bj` : compte suspendu au fetch.
- SYCEBNL : communiqué 29/03/2023 non retéléchargé (404 sur l’URL essayée) ; AUDCIF/SYSCOHADA confirmé sur ohada.org (adoption 26/01/2017).
- « SP » : attributions exactes **à confirmer** (D-01).
