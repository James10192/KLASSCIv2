# 10 — Angles morts

Gravité G / probabilité P / détectabilité D : H, M, B. Priorité = G×P avec D faible = plus urgent.

| ID | Mécanisme | Conséquence | Exemple | Preuve / incertitude | Invariant | Résolution | Prio |
|---|---|---|---|---|---|---|---|
| M1 | `teacher_id` = `user_id` mal joint | Payer le remplaçant au titulaire | Remplacement saisi sur la séance, paie lit l’emploi du temps type | Code : séance a un enseignant ; heures sur attendance | Payer `seance.teacher_id` du jour | Lot heures | H |
| M2 | Rôles multiples + Gate::before | Auto-visa | SuperAdmin UCAO publie son PV | `AuthServiceProvider` | Pas de `*` client | A2 | H |
| M3 | Signataire parti, dossier orphelin | Blocage rentrée | SP quitte avant visas | Non codé | Délégation + réassignation **en son nom** | socle approbation | H |
| M4 | Règle CC 40/60 changée entre saisie et bulletin | Moyennes différentes documents | Settings sauvés, calcul ignore encore, puis un jour consomme | `LmdAcademicRuleProfile` | Version de règle sur le bulletin | B3+B4 | H |
| M5 | Délégation expirée encore dans le token session | Visa post-mortem | Session ouverte 12 h | Incertain | Check délégation **à la requête** | socle | H |
| M6 | IBAN changé après visa | Argent au mauvais compte | Préparateur édite fournisseur | Tables mortes aujourd’hui | Invalidation + SoD | P2P | H |
| M7 | Année académique ≠ exercice ≠ mois de paie | Double rattachement | Paiement juillet sur exercice clos | `period_locked` existe (recettes) | Trois calendriers nommés | D-04 | M |
| M8 | Dates rétroactives séance / paiement | Recette dans période lockée | Caissier backdate | partiel paiements | Lock + motif + droit distinct | existant à étendre | M |
| M9 | UE partagée, crédits différents par parcours | Bulletin faux | CompositionUe le permet — gel absent | `credit` sur pivot parcours | Snapshot composition | B3 | H |
| M10 | Transfert étudiant / changement programme | Perte crédits ou double compte | Wallet vs maquette live | wallet existe | Équivalence + version d’entrée | B6 | H |
| M11 | TPE déclaré + séance TPE | Heures ×2 puis payées | UCAO sur site + journal | TPE hors paie **aujourd’hui** | Interdire conversion auto ; unicité | B12 | H |
| M12 | QR/Wi-Fi = validation | Crédit fantôme | Étudiant scanne et part | Non codé ainsi | B.3 preuves | B13 | M |
| M13 | Preuve TPE après clôture | Recalcul bulletin | — | — | Fenêtre ; pas de silent rewrite | B3 | M |
| M14 | Fractionner un achat sous le seuil | Contournement président | 9 × 900k | Non codé | Détection même fournisseur/objet/30j **alerte**, pas blocage auto aveugle | P2P | H |
| M15 | Urgence permanente | Circuit court devenu défaut | Flag jamais retiré | — | Rapport des urgences ; expiration du flag | B.9 | M |
| M16 | Réception partielle + facture totale | Surstock ou surpaiement | ERPNext documente le piège | Inspiration, pas loi | 3-way lignes ; stock **à la réception seulement** | P2P | H |
| M17 | Facture dupliquée / webhook rejoué | Double décaissement | — | — | idempotency_key + état `indetermine` | 6.2 | H |
| M18 | Avoir non affecté / trop-perçu avalé | Écart invisible | régén frais baisse — **fixé** `eafab30b1` | preuve session | `ecart_prevu` + crédit | C1 | M (en cours) |
| M19 | Inventaire rétroactif revalorise le passé | Bilan faux | — | — | Ajustement daté, pas rewrite | stock | M |
| M20 | Matériel prêté / perdu / garantie | Double sortie | — | — | Statuts exclusifs | patrimoine | M |
| M21 | Brouillon PDF après « validé » | Preuve ≠ fichier | Génération PDF fail | OfficialDocument déjà snapshot | Ne marquer émis qu’après PDF+hash | docs | H |
| M22 | QR public trop bavard | Fuite notes | page vérif | PV QR existe | Minimum : n°, statut | docs | M |
| M23 | Compte parent = étudiant (produit actuel) | Parent voit tout, y compris majeur | rôle parent **supprimé** | AGENTS.md | Droits parent **à revoir** pour majeurs UCAO | D-08 | M |
| M24 | Support sans identité réelle | Audit menteur | impersonation | volontairement absente | rester | A7 | H |
| M25 | Import partiel maquette / codes réutilisés | Cursus mélangés | LMD import idempotent | tests collision code | prévisualisation | B2 | M |
| M26 | Salle OT fermé, EDT pas à jour | Cours dans une salle fermée | salle string | | événement planning | F2/G9 | H |
| M27 | Mission RH ≠ résa véhicule | Deux vérités | — | | lien, pas fusion | D4 | M |
| M28 | Option inerte (CC weight) | École croit 40/60 | UI + seeder | **prouvé** | ne pas afficher comme actif | B4 | H |
| M29 | Profil BJ hérite CI | Retenue illégale | défauts Payroll | **prouvé** | `non_valide` bloque payer | B.8 | H |
| M30 | User MySQL partagé | Lecture croisée tenants | TenantProvision `:88` | **prouvé** origin | user par DB | H1 | H |
| M31 | Fusion audit branch écrase hotfixs presentation | Régression séances/frais | 9 commits en retard | git | rebase | — | H |
| M32 | KPI `ESBTPDepense` inexistant | Job plante | `CalculerKPIsJob` | **prouvé** | ne pas réactiver tables mortes | P2P | M |
| M33 | Chatbot sensitive tools | Fuite si mal config | `CHATBOT_SENSITIVE_TOOLS` | config | défaut off | G11 | M |

## Ce qui peut casser les clients **déjà** là

- Fusion maladroite de l’audit branch (EDT, SoD, LMD).
- User MySQL / cache partagé.
- Activer achats/stock **par défaut** sur Yakro (permissions).
- Consommer `lmd_cc_weight` sans versionner les bulletins déjà publiés.
- Changer le fuseau d’une instance CI vivante.
- Remplacer `superAdmin` Gate::before sans migration des comptes direction.

Yakro / Abidjan : opt-in permissions, profils CI inchangés, pas de composantes forcées.
