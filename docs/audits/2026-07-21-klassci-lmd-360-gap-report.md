# Rapport d'écart LMD 360 avant code

| Domaine | Existant | Écart | Risque | Décision | PR cible |
|---|---|---|---|---|---|
| Autorité pédagogique | Plusieurs builders et relations | Population UE/ECUE divergente | Critique | Snapshot canonique | Vertical 1 |
| Notes | Saisie et AcademicPilotage | Observer contourné | Critique | Frontière transactionnelle unique | Vertical 1 |
| Règles LMD | Réglages dispersés | Seuils et compensation divergents | Critique | Profil réglementaire versionné | Vertical 1 |
| Readiness | Service existant | Dépendance circulaire au bulletin | Critique | Calcul depuis feuilles et référentiel | Vertical 1 |
| Jury | JuryRoom existant | Signature usurpable, transitions faibles | Critique | Policy, auto-signature, machine d'états | Vertical 1 |
| Décision | Trois sources | Bulletin potentiellement divergent | Critique | Autorité unique et projections atomiques | Vertical 1 |
| Documents | PDF et checksums séparés | Pas d'émission immuable | Élevé | Registry documentaire versionné | Vertical 2 |
| Crédits | Cumuls depuis bulletins | Pas de provenance | Élevé | Ledger après snapshots | Vertical 3 |
| Chatbot | Outils opérationnels | Données hors scope | Critique | Désactivation sensible par défaut | Préliminaire Vertical 1 |
| Permissions | Registry | Scope métier dispersé | Élevé | Résolveur commun | Vertical 1 |
| Widgets | Page présente | HTTP 500 | Élevé | Corriger sans recréer le dashboard | Correctif immédiat |
| Tests | Unit/Feature partiels | Matrice navigateur absente | Élevé | Fixtures déterministes et E2E | Chaque vertical |

## Premier parcours vertical recommandé

`Classe LMD -> référentiel canonique -> feuilles de notes verrouillées -> readiness pré-jury -> décision JuryRoom authentifiée -> projection bulletin cohérente`.

Ce parcours précède le Credit Wallet, car un ledger alimenté par des décisions mutables ou divergentes serait impossible à auditer.

## Dix doubles saisies ou sources parallèles à supprimer

1. ECUE-UE par FK et pivot.
2. Résultat UE historique et résultat LMD UE.
3. Décision jury et délibération legacy.
4. Décision bulletin et décision jury.
5. Seuil de validation dans plusieurs clés.
6. Compensation dans plusieurs clés.
7. Population de matières dans trois services.
8. Crédits recalculés depuis plusieurs bulletins.
9. Affectations AcademicPilotage, responsables UE et membres jury.
10. PDF régénéré et document hashé séparé.

## Dix actions exigeant une review screen

1. Soumettre définitivement des notes.
2. Déverrouiller ou corriger une feuille validée.
3. Appliquer les décisions automatiques.
4. Enregistrer une override de jury.
5. Signer comme membre du jury.
6. Clore la séance.
7. Générer le PV officiel.
8. Publier les résultats.
9. Révoquer ou remplacer un document.
10. Importer un fichier Excel avec mutations.

## Hypothèses restant à tester humainement

- Compréhension de `UE`, `ECUE`, compensation et capitalisation.
- Capacité d'un enseignant mobile à reprendre une saisie interrompue.
- Lisibilité d'un cas exceptionnel projeté en salle de jury.
- Confiance dans la preuve de signature et la publication.
- Compréhension étudiante des causes de non-validation.
- Charge cognitive du rôle custom multi-fonctions.

