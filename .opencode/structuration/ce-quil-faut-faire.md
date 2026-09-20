# Ce qu’il faut faire — lu dans le dossier UCAO-UUC (artifact collé 15/09/2026)

Source : `.opencode/research/README.md` + texte Marcel collé en session.
L’URL Claude n’est pas fetchable. Ce fichier est le **contrat d’action**, pas l’historique des lots.

## Ce que l’établissement est (pas Wikipédia)

UCAO-UUC = **Cotonou**, quatre composantes dans **une** instance : EGEI, ESMEA, FSAE, FDE. ~28 parcours, ~170 maquettes. Rectorat Ouagadougou délivre le diplôme. Bénin +229, UTC+1, XOF.

ESMEA = École de Management et Économie Appliquée (pas un rôle PHP).

## Service informatique — livrer

1. Rôle canonique **Administrateur d’instance** ≠ `serviceTechnique` (ADC).
2. Scinder réglages / abonnement (lot partiellement fait : `paywall.manage` vs settings).
3. Explicateur de droits (« pourquoi Mme X ne voit pas ») ; **refuser** « voir comme cet utilisateur ».
4. Surface sur les diagnostics déjà écrits. Pas de santé disque cPanel, pas de terminal, pas de journal brut.

La scolarité UCAO fait **tout** y compris les jurys : avant ouverture, cocher les permissions jury sur leur rôle scolarité (défaut ivoirien = coordinateur). Configurable, à décider **avant** le premier jury.

## Service scolarité — ne pas reconstruire la chaîne LMD (~85 % déjà là)

Livrer / réparer, pas inventer :

| Faire | Ne pas faire avant ouverture |
|---|---|
| Déverrouillage tracé d’une décision de jury (~80 lignes) | Portail de réclamation + fenêtre réglementaire |
| Contrôle maquette 30 crédits / semestre (lecture seule) | Dater les pivots / dupliquer la maquette chaque année |
| Geler la composition **dans le bulletin** (recalcul d’une année passée = composition stockée) | Verrou sur `is_active` (démonté en revue) |
| Import notes : 3 correctifs **puis** portage LMD | Porter l’import cassé tel quel |
| Équivalence d’UE (crédits positifs), pas dispense d’UE | Dispense LMD qui crée un déficit de crédits |
| Unifier 3 moyennes annuelles ; moyenne de **parcours** + capitalisation | Jury assisté par IA (rejet : ancrage, art. 401, PV) |

TPE à l’école : setting, défaut actuel = non planifiable.

Agréments enseignants / fiches exécution-prestation-facturation / validation SP : **hors dossier artifact** (demande orale Marcel). RH enseignants ≠ ADC Paie. Échéance sur l’objet, alertes aux moments critiques, pas +3 ans.

## Fin de cycle — avant première promo sortante

Attestation de **réussite** (l’État la exige). Accréditation **en colonne sur le parcours** (homologation État ≠ accréditation CAMES). Soutenance comme **objet** (mémoire, encadreur, rapporteur) — le type de séance SOUTENANCE ne suffit pas. Supplément au diplôme = rentrée 2027, obligation UEMOA 2009, **toutes** les instances LMD.

## Emploi du temps — ne pas vendre la génération auto

Vendre : conflit uniforme, proposition de créneau libre, duplication de semaine (déjà là, mal nommée). Préalables d’un solveur : salles en référentiel, indispo **datée** des vacataires. Défaut horaire `2026-` dans l’avis parent : **ouvert**, cinq endroits, **ne pas corriger en passant**.

## Comptabilité / RH employés

Hors le cœur du dossier artifact. Demande orale : pousser le client/caisse ; ajouter stock + fournisseur + trésorerie + écritures + rapprochement **par permissions** ; ADC Paie (attestation, assurance, promotion, mission, fiche de poste, pointage IP). Pointage : **pas** dans l’artifact — seulement le brief oral (code sur PC pro, allowlist IP).

## Défauts encore ouverts (dossier)

- Trois moyennes annuelles, aucune de parcours
- Import de notes qui perd des colonnes
- Horaire `2026-` (liste, avis parent, CSV, planning)
- Maquette : élagage livré ; gel sur composition stockée **pas** encore
- Conflits EDT : écrits, **non fusionnés / non déployés** selon le dossier (vérifier HEAD presentation)
- Pondération CC/examen : pas de nature de note LMD

## Interdit (dossier)

Impersonation. Console infra. Génération EDT comme bouton magique. Jury IA décideur. `if ucao`. Rôle ADC donné au DSI client.
