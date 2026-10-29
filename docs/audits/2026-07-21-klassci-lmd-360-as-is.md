# Audit KLASSCI LMD 360, état actuel

Date : 21 juillet 2026  
Référence : `origin/presentation` à `583a1ebd9d94a02063e0ccc0d74bb3bef0c21179`  
Instance observée : `https://presentation.klassci.com`  
Méthode : lecture du dépôt, API CLI en lecture seule, navigateur authentifié avec le skill `klassci-test-e2e`.

## Synthèse

KLASSCI possède déjà une chaîne LMD importante : Domaine, Mention, Parcours, UE, ECUE, notes, résultats, bulletins, sessions, rattrapage, examens, jury et un domaine récent `AcademicPilotage`. Le chantier doit réconcilier ces fonctions, pas les recréer.

Le risque principal n'est pas l'absence d'écrans. Il vient de plusieurs autorités concurrentes pour le référentiel, les règles, les décisions et les documents. La priorité est donc une frontière académique canonique avant le Credit Wallet.

## Cartographie

| Domaine | État | Qualité | Action | Preuve |
|---|---|---|---|---|
| Domaine, Mention, Parcours, UE, ECUE | Présent, dupliqué | Fragile | Réconcilier | Modèles LMD, pivot Parcours-UE et FK directe ECUE-UE |
| Résultats LMD classe et étudiant | Présent | Fragile | Étendre | `/esbtp/lmd/resultats/classe/2`, `/esbtp/lmd/resultats/etudiant/6`, HTTP 200 |
| Bulletins LMD | Présent, partiel | Critique | Réconcilier | `LMDBulletinService`, génération recalculée depuis les données courantes |
| AcademicPilotage | Présent, partiel | Acceptable | Étendre | 28 feuilles attendues, 259 alertes, couverture moyenne 16,86 % |
| Verrouillage des notes | Cassé | Critique | Remplacer la frontière de mutation | Observer contourné par `upsert` dans `ESBTPLMDNoteController` |
| Readiness bulletin | Cassé | Critique | Réconcilier | La readiness exige une métrique issue d'un bulletin déjà généré |
| JuryRoom | Présent, partiel | Critique | Étendre | `/esbtp/lmd/jurys/1`, quorum visible, aucune décision dans la cohorte observée |
| Signature jury | Présent, cassé | Critique | Remplacer le contrôle | L'utilisateur peut signer un membre sans identité correspondante |
| Décision finale | Dupliqué | Critique | Réconcilier | Décision jury, délibération legacy et champ du bulletin concurrents |
| PV | Présent, partiel | Fragile | Étendre | Numérotation et fichier non encapsulés dans une émission immuable |
| Documents officiels | Partiel | Critique | Étendre | Checksums AcademicPilotage existants, non reliés aux bulletins et PV |
| Credit Wallet | Absent | Critique | Différer puis créer | Aucun ledger, cumul calculé depuis les bulletins |
| Examens | Présent | Acceptable | Conserver et étendre | `/esbtp/examens/1`, HTTP 200, historique visible |
| Rattrapage | Présent, partiel | Fragile | Réconcilier | `/esbtp/lmd/rattrapage/1`, HTTP 200 |
| Permissions | Présent | Fragile | Réconcilier | Registry central, contrôles métier encore dispersés |
| Chatbot | Présent, cassé | Critique | Confiner immédiatement | Outils notes, paiements et étudiants exposés sans scope suffisant |
| Dashboard widget-based | Cassé | Critique | Corriger | `/dashboard/widgets`, HTTP 500, colonne `statut` inexistante |
| Notifications LMD | Partiel | Fragile | Étendre | Infrastructure présente, événements LMD incomplets |
| Tests BTS/LMD | Partiel | Fragile | Étendre | Aucun Browser test déterministe pour les 4 combinaisons obligatoires |

## Duplications et divergences prioritaires

1. ECUE vers UE est porté par une FK directe et un pivot.
2. Les résultats UE historiques et `ESBTPLMDResultatUE` se chevauchent.
3. Les clés de réglage LMD diffèrent entre bulletin, jury et AcademicPilotage.
4. Le bulletin stocke `credits_capitalises/credits_totaux`, tandis que le jury lit d'autres noms.
5. Trois représentations peuvent porter une décision de délibération différente.
6. Les populations de matières diffèrent entre saisie, métriques et bulletin.
7. Les documents AcademicPilotage sont hashés, mais les PDF officiels restent régénérables.

## Incidents observés sur `presentation`

- Le dashboard widget-based retourne HTTP 500 sur une colonne `statut` absente.
- Le titre HTML du résultat étudiant LMD contient du PHP Blade non interprété.
- Le journal contient une erreur récente `TypeSeance::tryFrom()` recevant déjà une enum dans la vue planning examens.
- Le journal contient une requête récente utilisant la colonne inexistante `esbtp_classe.id`.
- Les résultats LMD observés affichent des UE et crédits sans notes, mais utilisent `NAQ` au lieu d'un état explicite `non calculé`.
- Le chatbot s'ouvre, mais une question bénigne retourne un état erreur car `ANTHROPIC_API_KEY` n'est pas configurée.

## Baseline de performance navigateur

| Route | Réponse | DOMContentLoaded | Load | Transfert | Ressources |
|---|---:|---:|---:|---:|---:|
| Classe LMD | 2 847 ms | 3 510 ms | 3 558 ms | 582 Ko | 27 |
| Résultat étudiant LMD | 5 022 ms | 5 134 ms | 5 165 ms | 210 Ko | 26 |
| JuryRoom | 13 425 ms | 13 748 ms | 13 811 ms | 247 Ko | 25 |
| Examen | 10 557 ms | 11 004 ms | 11 054 ms | 253 Ko | 27 |
| Planning LMD | 2 395 ms | 3 255 ms | 3 305 ms | 386 Ko | 28 |

Les cinq routes dépassent le budget p95 normal de 2 secondes. JuryRoom et examen sont prioritaires pour l'analyse N+1 et la réduction du temps serveur.

## Matrice BTS/LMD

| Cas | Données déterministes | Tests Feature/Unit | Test navigateur |
|---|---|---|---|
| BTS pivot peuplé | Non garanti | Références partielles | Absent |
| BTS pivot vide | Non garanti | Références partielles | Absent |
| LMD parcours | Classes présentes | Partiel | Captures réelles, fixture non dédiée |
| LMD tronc commun | Données réelles visibles | Partiel | Captures réelles, fixture non dédiée |

## Contraintes d'exécution

- `KLASSCIv2` et `adminKlassci` sont deux dépôts autonomes.
- Le présent lot ne modifie que `KLASSCIv2`.
- Le checkout original contient des changements utilisateur et reste intact.
- Le worktree local ne possède pas encore `vendor` ni `node_modules`; PHP local 8.2 ne satisfait pas la plateforme Composer 8.3.
- Aucun `migrate:fresh`, effacement ou mutation de la base `presentation` n'a été exécuté.
