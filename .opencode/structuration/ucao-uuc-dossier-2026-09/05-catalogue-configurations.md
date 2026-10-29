# 05 — Catalogue des configurations

Chaque règle : type, niveau, héritage, qui, effet, ancien, contrôle.  
Valeurs **absente / 0 / false / N/A / héritée** ne se confondent pas.  
Une clé en base non lue par le métier = dette, pas une fonctionnalité (ex. `lmd_cc_weight` aujourd’hui).

Profils initiaux : **CI-BTS** (défaut historique), **CI-LMD**, **BJ-LMD** (UCAO). Jamais `if tenant_code`.

## A. Invariants (pas de setting)

Isolation inter-instances · pas d’impersonation · écriture déséquilibrée refusée **si** on tient un journal en partie double · document émis non altéré · pas de LLM sur note/jury/paie/paiement · refuse par défaut.

## B. Règles (extrait actionnable)

### B.1 Fuseau

| | |
|---|---|
| Paramètre | `APP_TIMEZONE` IANA + `DB_TIMEZONE` offset |
| Niveau | instance (env, **avant** données) |
| Héritage | défaut UTC ; CI `Africa/Abidjan` ≡ UTC+0 |
| Qui | ADC à la provision (`--timezone`). Pas le CLI distant. |
| Effet | tous horodatages |
| Ancien | ne pas migrer une instance vivante |
| Contrôle | refuse DST ; aperçu offset |

UCAO : `Africa/Porto-Novo` / `+01:00`.

### B.2 Téléphone

| | |
|---|---|
| Paramètre | `telephone_indicatif_pays` + `telephone_prefixes_mobiles` |
| Niveau | instance |
| Héritage | 225 + préfixes CI. **Les deux ensemble.** |
| Qui | admin instance |
| Effet | normalisation, unicité candidature |
| Ancien | runbook §4 : l’école tranche, pas une requête magique |
| Contrôle | 229 sans préfixe BJ = refus d’enregistrement |

UCAO : `229` + `01`.

### B.3 TPE planifiable

| | |
|---|---|
| Paramètre | enum `tpe.mode` : `non_planifiable` \| `seance_encadree` \| `autonome_sur_site` \| `depot_preuve` \| `hybride` |
| Niveau | instance (surcharge possible programme — **pas** au départ) |
| Héritage | `non_planifiable` (comportement actuel dur) |
| Qui | admin instance |
| Effet | autorise `TypeSeance::TPE` dans l’EDT **seulement** si ≠ non_planifiable |
| Ancien | déclarations journal inchangées |
| Contrôle | aperçu « N séances TPE deviendraient créables » |

Ce que chaque mode **prouve / ne prouve pas** : voir [06](06-workflows.md) TPE.

### B.4 Période d’évaluation

| | |
|---|---|
| Paramètre | calendrier de programme : semestre \| année \| session \| bloc \| plage datée |
| Niveau | **programme** (pas instance globale) |
| Héritage | LMD défaut semestre ; BTS année/semestre déjà distinct |
| Qui | directeur des études |
| Effet | rattachement des évaluations ; jury `semestre` nullable reste valide |
| Ancien | bulletins déjà émis intacts |
| Contrôle | on ne demande pas « S1 ou S2 ? » à chaque examen |

### B.5 Pondération CC / examen

| | |
|---|---|
| Paramètre | `lmd_cc_weight` / `lmd_exam_weight` **plus** natures d’évaluation |
| Niveau | programme (héritage instance) |
| Héritage | 40/60 stockés, **non appliqués** |
| Qui | pédagogie ; changement versionné |
| Effet | **seulement après** lot natures + branchement `calculerMoyenneECUE` |
| Ancien | PV/relevés déjà émis sans cette règle (déjà omise) |
| Contrôle | interdiction d’imprimer 40/60 tant que non consommée (déjà le cas) |

### B.6 Seuil 30 crédits / semestre

| | |
|---|---|
| Paramètre | `lmd_credits_per_semester` |
| Niveau | programme / instance |
| Héritage | 30 |
| Qui | pédagogie |
| Effet | plafond à l’édition de maquette (déjà) |
| Ancien | maquettes déjà sauvées : écran lecture seule d’audit (lot) |
| Contrôle | import : erreur par ligne si dépassement |

### B.7 Agrément — déclencheurs de réexamen

| | |
|---|---|
| Paramètre | `agrement.declencheurs[]` : nouvelle_mission, changement_grade, nouvelle_matiere, interruption_jours, anomalie |
| Niveau | instance |
| Héritage | liste vide = **pas** de relance auto (≠ validité perpétuelle) |
| Qui | scolarité / admin |
| Effet | alerte ; n’efface pas la dette d’un service déjà fait |
| Ancien | — |
| Contrôle | pas de `+3 years` en dur |

Expiration **sur l’objet** : `valid_from`, `valid_to` nullable (= sans terme, pas « infini légal »).

### B.8 Profil fiscal / social paie enseignants

| | |
|---|---|
| Paramètre | `paie.profil_pays` : `CI` \| `BJ` \| `non_valide` + barèmes datés |
| Niveau | instance |
| Héritage | aujourd’hui CI **silencieux** |
| Qui | DAF ; validation « profil certifié le … par … » |
| Effet | `non_valide` ⇒ calcul **indicatif**, bouton payer refusé |
| Ancien | bulletins CI inchangés |
| Contrôle | test : instance BJ n’hérite pas 6,3 % CNPS |

### B.9 Circuits d’achat

| | |
|---|---|
| Paramètre | table `approval_circuits` : nature, montant_min/max, étapes, quorum |
| Niveau | instance (surcharge entité) |
| Héritage | circuit « petit montant » 1 visa ; « au-delà » DAF+président **hypothèse UCAO** |
| Qui | admin + DAF |
| Effet | dossiers en cours **restent** sur l’ancienne version de circuit |
| Ancien | — |
| Contrôle | aperçu N dossiers impactés ; pas de déplacement silencieux |

### B.10 DocumentPrintGuard / impayé

| | |
|---|---|
| Paramètre | déjà : solde + approbation responsable |
| Niveau | instance / type de document |
| Héritage | existant |
| Qui | scolarité + DAF |
| Effet | certificat / bulletin |
| Ancien | — |
| Contrôle | **Ne pas** inventer un blocage notes/examens/diplômes. Diplôme = décision séparée (D-05). |

### B.11 Gabarit relevé

| | |
|---|---|
| Paramètre | déjà `mesrs` vs klassci (`LmdTranscriptSnapshotBuilder`) |
| Niveau | instance |
| UCAO | `mesrs` **hypothèse** — confirmer l’autorité émettrice (D-07) |

## C. Deux profils sur le même produit

### Profil ESBTP Yakro (CI, BTS dominant, Élite)

- Fuseau Abidjan / UTC. Téléphone 225.
- TPE `non_planifiable`.
- Pas de composantes isolées.
- `comptable` = caisse + client ; achats/stock **non cochés**.
- Paie profil `CI` certifié (historique).
- Jury : coordinateur (défaut actuel).
- Celtiis absent, modes CI conservés.

### Profil UCAO-UUC (BJ, LMD, 4 composantes) — hypothèses marquées

- Fuseau Porto-Novo. Téléphone 229 / 01.
- TPE `seance_encadree` ou `hybride` (**hypothèse entretien**).
- Composantes EGEI, ESMEA, FSAE, FDE ; isolation dossiers.
- Scolarité : permissions jury cochées.
- Customs : stock, dépenses, trésorerie.
- Paie `BJ` **non_valide** jusqu’à DAF UCAO + juriste.
- Admin instance = DSI client.
- Relevé MESRS (**hypothèse**).
- Celtiis Cash activé en plus des modes existants.

Les deux tournent sur le **même** code `presentation`.
