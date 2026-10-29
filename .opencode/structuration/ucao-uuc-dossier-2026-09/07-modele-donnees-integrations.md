# 07 — Modèle de données et intégrations

Inventaire d’abord. Ne créer que le nécessaire.

## 1. Concepts déjà là (ne pas dupliquer)

| Concept | Tables / modèles | Maître |
|---|---|---|
| Étudiant, inscription, année | `esbtp_etudiants`, `esbtp_inscriptions` | KLASSCI |
| Parcours, UE, ECUE, crédits | `esbtp_lmd_*`, `esbtp_unites_enseignement`, pivots | KLASSCI |
| Notes, évaluations | `esbtp_notes`, `esbtp_evaluations` | KLASSCI |
| Bulletin / résultats UE-ECUE | `esbtp_lmd_bulletins`, `..._resultat_*` | KLASSCI — **ajouter snapshot composition** |
| Jury, membres, décisions | `esbtp_lmd_jurys*` | KLASSCI |
| Wallet crédits | `esbtp_lmd_credit_wallet_entries` | KLASSCI |
| TPE déclaration | `esbtp_tpe_declarations` | KLASSCI |
| Frais, souscriptions, paiements, allocations | `esbtp_frais_*`, `esbtp_paiements` | KLASSCI |
| Échéancier snapshot | services `Echeancier*` | KLASSCI |
| Caisse session, reconcil | `esbtp_cash_sessions`, `reconciliation_sessions`, `cash_counts` | KLASSCI |
| Enseignant, régime, taux, émargement | `esbtp_teachers`, `esbtp_teacher_attendances` | KLASSCI (pédagogie) |
| Salaire enseignant | `esbtp_salaires` | KLASSCI **heures** |
| Document officiel | `official_documents` PV + relevé | KLASSCI |
| Audit | `audits` | KLASSCI |
| Tenant master | adminKlassci `tenants` | ADC |

**Ne pas réutiliser** : `esbtp_depenses` / `esbtp_fournisseurs` tels quels (schéma orphelin, KPI cassé). Nouveau schéma P2P propre, migration d’abandon des tables mortes.

## 2. Structures nouvelles nécessaires

### 2.1 Composante et périmètre

`composantes (id, code, nom, parent_id nullable)`  
Liaison `user_composante`, `parcours.composante_id`, `classe.composante_id`.  
Pas une cascade artificielle campus→composante→programme.

### 2.2 Agrément enseignant

`enseignant_agrements (teacher_id, emetteur, type, perimetre_json, valid_from, valid_to nullable, statut, pieces)`  
Événements de réexamen.  
`enseignant_contrats_kl` seulement si vacataire/prestataire **non** salariés ADC Paie.

### 2.3 Service certifié / file SP

`seance_service_fait (seance_id, teacher_id, duree_planifiee, duree_constatee nullable, source)`  
`prestation_dossiers (id, teacher_id, periode, etat, visa_sp_user_id, visa_at)`  
Lignes → séances. Unicité `(seance_id, teacher_id)` pour le paiement.

### 2.4 P2P

`fournisseurs` (identité, IFU, rib, statut)  
`besoins`, `consultations`, `offres`, `commandes` + `commande_lignes`  
`receptions` + lignes (qty, conforme)  
`factures_fournisseur` + lignes  
`paiement_propositions`, `ordres_paiement`  
États et dimensions séparées. Pièces polymorphiques `pieces_jointes (id, type, hash, objet_type, objet_id, version)`.

### 2.5 Stock / patrimoine

`articles` (nature: consommable|stock|immo|loue|confie)  
`emplacements`, `mouvements` (qty, serial/lot nullable, motif, user, ref_reception XOR ref_sortie)  
`actifs` (n° inventaire, detenteur, etat, garantie)  
`salles` **entité** remplaçant le string `seance.salle` (migration douce : string conservé + `salle_id` nullable).

### 2.6 Maintenance / véhicules

`incidents`, `ordres_travail`, `plans_preventifs`  
`vehicules`, `reservations_vehicule`, `carnet_entretien`  
`missions_logistiques` lié optionnel `missions_rh_ref` (id externe ADC Paie).

### 2.7 Identité commune personne

`personnes (id)` optionnel plus tard. Court terme : `teachers.employee_external_id` pour joindre ADC Paie **sans** fusionner les dossiers.

## 3. Contrats d’échange KLASSCI ↔ ADC Paie

**ADC Paie n’est dans aucun des deux dépôts.** Contrat **proposé**, pas une API observée.

| Donnée | Maître | Sens | Déclencheur | Id |
|---|---|---|---|---|
| Identité salarié (nom, naissance, IFU) | ADC Paie | Paie → KLASSCI lecture | embauche | `employee_id` stable |
| Contrat / avenant / salaire | ADC Paie | — | — | — |
| Pointage employé | ADC Paie | — | — | — |
| Fiche de poste, promotion, assurance | ADC Paie | — | — | — |
| Enseignant vacataire + heures certifiées | KLASSCI | KLASSCI → Paie (si on paie via Paie) | visa SP | `teacher_id` + `periode` |
| Masse salariale groupe | aujourd’hui : `esbtp_salaires` via `GroupPayrollProvider` | master lit tenant | dashboard groupe | **ne pas** confondre avec ADC Paie |

Schéma d’événement minimal (JSON, versionné) :

```
hours.certified.v1 { teacher_id, employee_external_id?, period, hours_by_type, amount_hint, currency: XOF, idempotency_key }
employee.sync.v1 { employee_id, status, valid_from }
ack.v1 { idempotency_key, status: accepted|rejected, errors[] }
```

Règles : idempotence par clé ; rejet **visible** dans KLASSCI (écran anomalies) ; relance sans doublon ; jamais passer à « synchronisé » sur timeout. Habilitation machine-to-machine (jeton instance, pas le mot de passe d’un DAF).

**Temporaire** : export CSV chiffré + import, même schéma, même idempotence. Un export généré ≠ intégration acceptée.

Changement de version : `Accept: application/vnd.adc.paie.v1+json`. Double écriture interdite.

## 4. KLASSCI ↔ livres comptables (si D-04 = externe)

Événements : `student.payment.validated`, `supplier.invoice.approved`, `supplier.payment.executed`, `stock.receipt`, `stock.issue`.  
Export SAARI déjà un germe (`saari_account_mapping`) — **mapping validé**, pas des n° de comptes copiés d’une école CI.

Rapprochement périodique des totaux, pas seulement l’envoi.

## 5. Fichiers, cache, jobs

Déjà : storage local par tenant path. À poser à la provision : `CACHE_PREFIX`, `SESSION_COOKIE` uniques (`{code}_session`). User MySQL **dédié** (corriger le partage `c2569688c_tenant`). Queues : rester `database` ou `sync` par instance — pas de Redis partagé sans préfixe.

## 6. Documents

`official_documents` à étendre : attestation de réussite, (plus tard) diplôme, supplément. Snapshot JSON + PDF + hash + QR **minimal**. Page publique : statut + n° + nom **si** politique le permet, pas le relevé entier.
