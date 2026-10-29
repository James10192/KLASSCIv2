# 09 — Registre réglementaire et sécurité

Consultation : 15 septembre 2026.  
Légende applicabilité : **obligation confirmée** · **pratique documentée** · **inférence** · **à configurer** · **texte non ouvert**.

Une obligation applicable n’est pas un interrupteur admin.

| ID | Exigence | Source | Date texte / consult. | Pays | Applicabilité | Règle technique | Validation métier | Conservation | Risque restant |
|---|---|---|---|---|---|---|---|---|---|
| R1 | LMD UEMOA (crédits, capitalisation, documents) | Directive 03/2007 UEMOA — notice visée, **texte intégral non ouvert ici** | 2007 / 15-09-2026 | UEMOA | texte non ouvert | 30 cr/sem déjà un setting ; capitalisation wallet ; supplément diplôme **différé** | Scolarité UCAO + MESRS BJ | — | Ne pas citer d’article. Ne pas reconduire un décret contesté. |
| R2 | Accréditation / reconnaissance programmes | CAMES PRED + PAQ — [lecames.org](https://www.lecames.org) programmes PRED / AQ ; [cames.online](https://www.cames.online) = CCI/agrégation **enseignants-chercheurs** | consult. 15-09-2026 | régional | pratique documentée du **processus**, pas du statut UCAO | Colonnes homologation État / CAMES **séparées** sur le parcours | UCAO fournit les arrêtés | pièces | Aucune accréditation UCAO n’est prouvée ici. Agrégation CAMES ≠ agrément à enseigner local. |
| R3 | Droit comptable OHADA / SYSCOHADA | AUDCIF, ohada.org, adoption 26/01/2017, JO 15/02/2017, vig. 01/01/2018 comptes personnels | 2017 / 15-09-2026 | OHADA (BJ membre) | obligation **si** entité dans le champ AUDCIF | KLASSCI ne tient pas les livres aujourd’hui | DAF + expert-comptable : SYSCOHADA vs SYCEBNL vs autre | 10 ans **hypothèse**, à confirmer | Nature catholique **ne tranche pas**. SYCEBNL (communiqué SP 29/03/2023, vig. 01/01/2024, exceptions) : texte non relu ce jour (404 URL essayée). **D-04**. |
| R4 | Facturation normalisée / e-MECeF | DGI Bénin — `impots.bj` **compte suspendu** au fetch 15-09 ; parcours connu : SFE avec réf. d’approbation DGI | ? / 15-09-2026 | BJ | à configurer / inconnue | Ne pas « coder une API MECeF » | DAF : assujetti ? | — | Enseignement ≠ exonération automatique. |
| R5 | IFU fournisseur / retenues | fiscalité BJ | non ouverte | BJ | à configurer | champ IFU ; retenues selon **statut** du bénéficiaire, barème daté | DAF | — | Ne pas copier ITS/CNPS CI. |
| R6 | Social / CNSS BJ vs CNPS CI | — | — | BJ | à configurer | `paie.profil_pays=non_valide` tant que non certifié | DAF + RH | — | Code actuel 6,3 % CI. |
| R7 | Données personnelles / APDP | apdp.bj SPA JS, procédures non extraites ; archive « transferts / biométrie » à **revérifier** | consult. 15-09-2026 | BJ | texte non ouvert | minimisation ; pas de biométrie par défaut ; support avec identité réelle ; IA = sous-traitant | DPO / admin instance | à figer après APDP | Formalités (déclaration, transfert ADC CI↔BJ) **non confirmées**. |
| R8 | Auth pointage | NIST SP 800-63B-4 — URL 403 ce jour ; principe connu : IP ≠ facteur | 2024 / 15-09-2026 | technique, **pas** loi BJ | inférence technique | IP signal ; auth nominative + exceptions | RH | logs pointage ≠ preuves médicales | — |
| R9 | Autorisation applicative | [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html) | consult. 15-09-2026 | technique | pratique | least privilege, deny default, check **chaque** requête **et** ressource, pas seulement le rôle ; IDOR ; fichiers statiques ; tests | ADC | logs accès | `Gate::before` superAdmin viole least privilege |
| R10 | Isolation multi-tenant | OWASP Multi Tenant + constat code | — | technique | inférence | DB+files OK ; user MySQL partagé **à corriger** ; préfixes cache/session | ADC | backups par code (commande existe) | restauration croisée |
| R11 | Examens nationaux / MESRS BJ | gabarit relevé `MODELE_MESRS` dans le code | code 2026 | BJ | configurable | choix de gabarit | scolarité : est-ce le bon modèle pour UCAO ? | snapshots | D-07 émetteur du diplôme |
| R12 | Conservation documents académiques | — | — | BJ | à configurer | versions immuables déjà pour PV/relevé | scolarité | — | Durée légale non inventée |

## Décisions attendues (pas des constats)

- D-04 régime comptable UCAO.
- D-05 effets pédagogiques d’un impayé (ne pas inventer).
- Assujettissement e-MECeF.
- Formalités APDP + transfert CI (hébergement ADC).
- Statut CAMES / homologation de **chaque** parcours — pièces UCAO.

## IA déjà présente

Chatbot Claude : outils de **lecture** permissionnés. Gemini package non câblé. Groq intent non live.  
Interdit : arithmétique de référence, paiement, publication. Suggestion toujours révisable et utilisable si le fournisseur IA tombe.
