# 11 — Backlog par lots verticaux

Fourchettes : **construction** (dev+tests), hors reprise de données, validation métier, formation. Hypothèse : 1 dev connaissant KLASSCI, Laravel 9, pas de migration de framework. 1 pt ≈ ½–1 j. Fourchette volontairement large.

Rollback = feature flag / permissions non cochées / revert git instance, **sauf** fuseau et documents émis.

## Lot 0 — Ne pas casser (P0, avant tout code UCAO)

| | |
|---|---|
| Objectif | Rebase `ucao-uuc-audit-services-oi2uyn` **sur** `presentation` ; Celtiis ; ne pas écraser hotfixs |
| Touché | EDT, SoD, LMD moyenne, import notes, `CompositionDuBulletin`, `ModePaiement` |
| Recette | tests des deux côtés au vert ; thermo-review |
| Effort | 5–12 j (conflits EDT + LMD) |
| Rollback | ne pas merger si rouge |

## Lot 1 — Instance Bénin vivable (P0 rentrée)

| | |
|---|---|
| Objectif | Fuseau, téléphone, Celtiis, admin instance, scinder settings/paywall, diagnostic accès, pas d’impersonation |
| Touché | `permissions.php`, `AuthServiceProvider` (retirer Gate::before **progressif**), settings, adminKlassci provision (déjà origin/main), `ModePaiement` |
| Migration | seeder rôle `administrateurInstance` ; **pas** d’auto-migration des superAdmin CI |
| Recette | §14 A-admin, H1 |
| Mise en service | cocher le rôle pour le DSI UCAO ; CI inchangé |
| Effort | 8–15 j |
| Risque CI | Gate::before — feature flag par instance |

## Lot 2 — Composantes + maquettes + gel (P0)

| | |
|---|---|
| Objectif | 4 composantes, isolation listes/exports, import maquette prévisualisé, composition **gelée** dans bulletin, 1 moyenne annuelle |
| Touché | `LMDBulletinService`, `AgregatDeLaPeriode`, `LmdPvAnnuelAssembler`, `CompositionDuBulletin` (branche audit), scopes Eloquent |
| Recette | deux composantes, zéro/absence, UE partagée crédits différents |
| Effort | 12–25 j |
| Rollback | flag `lmd.freeze_composition` |

## Lot 3 — TPE sur site (P0)

| | |
|---|---|
| Objectif | Setting B.3 ; séances TPE si autorisé ; conflits ; pas de conversion paie |
| Touché | `ESBTPSeanceCoursController` (lever le dur), EDT, TPE journal |
| Effort | 5–10 j |

## Lot 4 — Agréments + file SP + heures constatées (P0 paiement enseignant)

| | |
|---|---|
| Objectif | Objet agrément, contrôles affectation/date/paiement, dossiers prestation, visa SP, durée constatée vs planifiée, profil paie `non_valide` |
| Touché | `ESBTPTeacher`, `TeacherHoursService`, `PayrollComputationService`, nouveaux modèles, permissions |
| Recette | expiration n’efface pas la dette ; remplaçant payé ; BJ n’hérite pas CNPS |
| Effort | 15–30 j |
| Formation | scolarité + SP |

## Lot 5 — P2P opérationnel (P1 achats)

| | |
|---|---|
| Objectif | Besoin → commande → réception → facture → proposition paiement, 3-way, SoD, **sans** GL |
| Touché | **nouveau** domaine ; abandon tables mortes ; pas `CalculerKPIsJob` sur `ESBTPDepense` |
| Recette | réception partielle ; IBAN post-visa ; retry indéterminé |
| Effort | 25–45 j |
| Rollback | permissions `achats.*` off |

## Lot 6 — Stock + salles entité (P1)

| | |
|---|---|
| Objectif | Articles, mouvements, réception unique, `salle_id`, conflit salle |
| Touché | EDT `salle`, nouveaux modèles |
| Effort | 12–22 j |
| Recette | facture ne double pas le stock ; salle fermée remonte au planning |

## Lot 7 — Trésorerie banque + Celtiis déjà là (P1–P2)

| | |
|---|---|
| Objectif | Import relevé, dédup, propositions, état indéterminé |
| Effort | 10–20 j |

## Lot 8 — Patrimoine, maintenance, véhicules, missions (P2)

| | |
|---|---|
| Objectif | F1–F4 |
| Effort | 20–40 j |
| Lien | lots 5–6 |

## Lot 9 — Pont ADC Paie (P1 contrat, P2 implémentation)

| | |
|---|---|
| Objectif | Contrat 07 ; écran anomalies ; CSV temporaire |
| Effort | 8–15 j **plus** côté Paie **inconnu** |
| Non inclus | pointage biométrique, fiches de poste dans KLASSCI |

## Lot 10 — Fin de cycle (P1 dès reprise possible)

| | |
|---|---|
| Objectif | Attestation de réussite, soutenance objet, colonne homologation, équivalence UE |
| Effort | 12–25 j |
| Diplôme / supplément | lots suivants (P1 diplôme, P3 supplément 2027) |

## Lot 11 — Natures de notes + CC/examen consommé (P1)

| | |
|---|---|
| Objectif | Brancher B.5 **avec** version ; imprimer alors seulement |
| Effort | 8–15 j |
| Danger | bulletins déjà publiés |

## Lot 12 — Livres officiels (P2, **après D-04**)

Soit export vers expert-comptable / logiciel, soit GL interne (fortement déconseillé en premier). Effort GL interne : 40–80 j **et** un comptable OHADA dans l’équipe. Export : 8–15 j.

## Hors construction (à budgéter ailleurs)

Reprise ~170 maquettes + étudiants + soldes : 10–30 j **métier** UCAO + 5–10 j outil.  
Formation par rôle : 1–2 j / rôle.  
Validation DAF barèmes BJ : calendrier UCAO, pas ADC.

## Exclusions visibles (pas oubliées)

| Besoin | Raison de différer | Transitoire | Réexamen |
|---|---|---|---|
| Portail réclamations | pas bloquant ouverture | mail scolarité | 1er jury |
| Solveur EDT | préalables salles+indispo | duplication semaine | après lot 6 |
| Jury LLM | ancrage / art. 401 / PV | — | jamais décideur |
| Supplément diplôme | 2027 | — | rentrée 2027 toutes LMD |
| e-MECeF | formalités DGI | factures PDF | si assujetti |
| GL SYSCOHADA dans KLASSCI | double livre | export | D-04 |
| Impersonation | audit | diagnostics | jamais |
