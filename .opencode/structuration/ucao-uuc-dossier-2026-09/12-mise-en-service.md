# 12 — Dossier de mise en service

UCAO est une **université existante**. Prévoir diplômés, soldes, enseignants, stocks dès J0. Pas « on ouvrira le diplôme dans 3 ans par défaut ».

## 1. Pilote

1. Instance `ucao-benin` (branche git déjà alignée `presentation` au 15/09).
2. Provision **depuis adminKlassci origin/main** : `--timezone=Africa/Porto-Novo`.
3. Réglages téléphone 229 / 01 **avant** candidatures (runbook).
4. Composantes EGEI, ESMEA, FSAE, FDE.
5. Admin instance = DSI (pas ST).
6. Permissions jury sur le rôle scolarité **avant** le 1er jury.
7. Profil paie `BJ/non_valide` jusqu’à signature DAF.
8. Achats/stock : permissions UCAO only.

CI : aucune de ces permissions nouvelles cochées par défaut.

## 2. Reprise de données

| Objet | Source probable | Rapprochement | Responsable validation |
|---|---|---|---|
| Maquettes ~170 | Excel / Word / autre logiciel **non prouvé** | crédits/sem, codes UE | dir. adjoint ESMEA + scolarité |
| Étudiants / inscriptions | idem | unicité matricule, téléphone 10c | scolarité |
| Notes / décisions déjà jurées | papier / PDF | **snapshot**, pas recalcul live | jury / scolarité |
| Soldes étudiants | caisse actuelle | AR KLASSCI vs caisse physique | caissier + comptable |
| Fournisseurs / dettes | compta actuelle | D-04 | DAF |
| Enseignants / agréments | dossiers papier | dates, périmètre | scolarité |
| Stock / patrimoine | inventaire physique | écarts motivés | magasin |
| Salariés | ADC Paie | identifiants | RH |

Règle : un objet repris = `imported_at`, `source_ref`, `validated_by`. Import partiel visible. Codes historiques **non réutilisés** à l’aveugle.

## 3. Critères d’ouverture (go / no-go)

**Go rentrée** si : fuseau, téléphone, admin instance, composantes, maquettes de la cohorte entrante importées et prévisualisées, caisse, TPE setting posé, isolation inter-instance testée (H1).

**No-go paiement enseignant** si : profil `non_valide` encore, ou heures = planifié **sans** libellé estimation, ou file SP absente alors que UCAO l’exige.

**No-go achat** si : 3-way absent ou stock doublé à la facture.

**No-go document officiel** si : moyenne annuelle encore triple formule.

## 4. Formation par rôle (cible)

| Rôle | Durée | Contenu |
|---|---|---|
| Admin instance | 0,5 j | réglages, diagnostic, comptes, audit masqué |
| Scolarité | 1 j | maquettes, notes, jury, TPE |
| Dir. adjoint ESMEA | 0,5 j | programmation, maquettes |
| SP | 0,5 j | file visas |
| Caissier | 0,5 j | déjà KLASSCI |
| Achats / stock | 1 j | lots 5–6 |
| Trésorerie | 0,5 j | propositions, rapprochement |
| Enseignant | 0,5 j | émargement, notes, TPE |

## 5. Supervision et restauration

- `tenant:backup` adminKlassci (DB + storage) **avant** chaque import de masse.
- Health-check. Files visibles à l’admin instance (lot 1), pas le disque cPanel.
- Restauration : une instance, jamais un dump dans une autre (user MySQL — lot H1).
- Réversibilité fonctionnelle : décocher permissions. Non réversible : fuseau, documents émis, paiements validés (avoir seulement).

## 6. Runbook déjà écrit

`docs/runbooks/ucao-benin-mise-en-service.md` — téléphones, fuseau, candidatures déjà déposées. **À exécuter**, pas à réécrire. Compléter : Celtiis (lot 0/1), admin instance (lot 1).
