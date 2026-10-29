# 03 — Matrice de couverture

Légende **écart** : réutilisable · configurable · extension · incomplet · anomalie · non retrouvé · intégrer.

Priorité : P0 rentrée / 1er paiement enseignant · P1 1ers achats · P2 1re clôture livres · P3 fin de cycle / confort.

| ID | Besoin | Origine | Acteur | Périmètre | Existant prouvé | Écart | Voie | Dépend | Prio | Acceptation |
|---|---|---|---|---|---|---|---|---|---|---|
| A1 | Paramétrer l’instance (hors abonnement) | entretien SI | Admin instance | instance | Settings `/esbtp/settings` ; paywall ST | `system.manage` non scindé ; superAdmin = tout | extension | A2 | P0 | Admin change téléphone/TPE/périodes sans voir l’abonnement ADC |
| A2 | Rôle administrateur d’instance ≠ ST | entretien + artifact | produit | produit | `serviceTechnique` = ADC `permissions.php:118-124` | rôle absent | extension | — | P0 | ST hors UI client ; admin sans `*` finance |
| A3 | Comptes : invite, active, suspend, départ, sessions | SI | Admin instance | instance | Users Filament-less Laravel UI, Spatie | cycle incomplet (délégation, revue périodique) | extension | A2 | P0 | Départ révoque sessions ; revue datée |
| A4 | Attribuer une permission ≠ l’exercer | SI + SoD | Admin instance | instance | partiel (ST gère superAdmin) | superAdmin Gate::before | extension | A2 | P0 | Admin donne `lmd.jury.preside` sans pouvoir publier un PV |
| A5 | Audit complet **borné** | SI | Admin / audit | instance | Owen-it + `ESBTPAuditController` | pas de masquage salaires/identité/santé | extension | A2 | P1 | Export motivé ; champs sensibles masqués par défaut |
| A6 | Diagnostic « pourquoi Mme X ne voit pas » | SI | Admin instance | instance | permissions:audit CLI | pas d’écran métier | extension | A2 | P0 | Message FR : permission / périmètre / config / file |
| A7 | Pas d’impersonation | SI + organigramme | produit | produit | **non retrouvé** (volontaire) | — | invariant | — | P0 | Aucune route « voir comme » |
| A8 | Santé technique client | SI | Admin instance | instance | tenant:health-check master | pas de surface client | configurable / léger | A2 | P2 | Files, jobs, sync — pas disque cPanel |
| B1 | 4 composantes / 1 instance | entretien | scolarité | instance | classes, filières, parcours LMD | pas d’entité « composante » avec isolation dossiers | extension | — | P0 | ESMEA ne voit pas les notes FDE par défaut |
| B2 | Maquettes versionnées, import, 30 cr/sem | artifact + entretien | dir. adjoint ESMEA | programme | `CompositionUe`, import JSON, plafond 30 | pas de version datée / prévisualisation sans effet de bord complète | extension | — | P0 | Import ligne à ligne ; ancienne cohorte intacte |
| B3 | Gel composition dans bulletin | artifact | scolarité | bulletin | live `getUEsForSemestre` ; classe sur branche audit | absent `presentation` | fusion + extension | audit branch | P0 | Recalcul N-1 = arbre stocké |
| B4 | Pondération CC/examen réelle | artifact | pédagogie | programme | settings 40/60 **non consommés** | incomplet | extension | nature de note | P1 | Moyenne ECUE = f(natures) ; PV l’imprime alors seulement |
| B5 | Unifier moyennes + moyenne parcours | artifact | jury | programme | 3 formules | anomalie | correction | B3 | P0 | Un service, un libellé sur PV/relevé/fiche |
| B6 | Équivalence d’UE (crédits +) | artifact | scolarité | étudiant | dispense LMD **refusée** 422 | non retrouvé | extension | wallet | P1 | Crédits ajoutés, pas un trou |
| B7 | Déverrouillage tracé décision jury | artifact | jury | jury | override avant lock ; destroy si locked | pas de reopen | extension | — | P1 | Motif + acteur + ancienne décision conservée |
| B8 | Scolarité UCAO fait les jurys | entretien | scolarité | rôle | permissions `lmd.jury.*` | défaut ivoirien = coordinateur | **configurable** | A4 | P0 | Cocher avant 1er jury — pas de code |
| B9 | Agrément enseignant (échéance objet) | oral | scolarité | enseignant | diplôme texte sur `ESBTPTeacher` | non retrouvé | nouveau module | — | P0 | Affectation refusée si agrément inapplicable **ce jour** ; service passé conservé |
| B10 | Contrat / convention ≠ agrément ≠ taux ≠ paiement | oral | scolarité / DAF | enseignant | `TeacherRegime` affichage | pas d’objets séparés | extension | B9 | P1 | 4 objets, 4 dates d’effet |
| B11 | Fiches exécution, prestation, facturation + visa SP | oral | scolarité, SP | enseignant | paie heures prepare/validate/pay | pas de fiches ni file SP | extension | B9, heures | P0 | SP (D-01) vise ; sans visa ≠ paiement |
| B12 | TPE à l’école | oral + artifact | scolarité | instance | journal TPE ; type séance existe | planifiable **dur interdit** | setting + extension | — | P0 | UCAO : planifiable sur site ; Yakro : inchangé |
| B13 | TPE ≠ heure payée ≠ crédit | conception | pédagogie | invariant | TPE hors bulletin | à préserver | invariant | B12 | P0 | Wi-Fi / QR seuls ≠ validation |
| C1 | Frais, échéancier, avoirs, remises | existant | caisse | inscription | chaîne AR complète | trop-perçu à la baisse : fix 15/09 `eafab30b1` | réutilisable | — | P0 | Recette existante + écart prévu négatif |
| C2 | Rapprochement caisse / modes | existant | caisse / comptable | caisse | Domain Reconciliation | ≠ banque, ≠ fournisseur | réutilisable | — | P0 | Écart 100 F configurable |
| C3 | Expression de besoin → dette fournisseur | oral | achats | instance | tables mortes `esbtp_depenses` | incomplet / non retrouvé vivant | **nouveau** | budget optionnel | P1 | Voir workflows 6.1 |
| C4 | Dette → règlement → lettrage | oral | trésorerie | instance | paiements étudiants seulement | non retrouvé AP | **nouveau** | C3 | P1 | Statuts distincts : autorisé / ordonnancé / réglé / rapproché |
| C5 | 4 rapprochements | cahier | trésorerie | instance | 1 seul (modes vs recettes) | 3 manquants | nouveau / intégrer | D-04 | P2 | Ne pas fusionner deux ops même montant+date |
| C6 | Grand livre / journaux / clôture exercice | cahier | DAF | personne morale | `compte_id` stub ; export SAARI | non retrouvé GL | **intégrer** (défaut) | D-04 | P2 | Pas deux livres |
| C7 | Budget engagement / disponible | oral | DAF | entité | `VolumeBudgetService` = heures cours | non retrouvé argent | extension | C3 | P1 | Engagement remplacé par facture sans double compte |
| C8 | Celtiis Cash | runbook | caisse | instance | `ModePaiement` sans Celtiis sur presentation | incomplet | extension | — | P0 | Mode ajouté, CI inchangée |
| D1 | Attestation de travail (reçue **et** émise) | oral RH | RH | employé | non retrouvé | — | ADC Paie + pont | E1 | P2 | Deux types de pièces nommés |
| D2 | Assurance santé (admin, pas diagnostic) | oral | RH | employé | non retrouvé | — | ADC Paie | E1 | P2 | Couverture / ayants droit, pas de diagnostic |
| D3 | Promotion datée | oral | RH | employé | non retrouvé | — | ADC Paie | E1 | P2 | Future ≠ rétroactive sur paie passée |
| D4 | Mission / déploiement | oral | RH + logistique | employé | non retrouvé | — | **deux objets liés** | G4 | P2 | RH ≠ réservation véhicule |
| D5 | CRUD fiches de poste versionnées | oral | RH | instance | `ESBTPPersonnelController` = users | non retrouvé | ADC Paie **ou** KLASSCI léger | E1 | P2 | Version + affectation |
| D6 | Pointage PC pro + IP | oral | RH | employé | émargement enseignant IP **loguée** | allowlist absente | nouveau (ADC Paie maître) | E1, NIST | P2 | IP = signal, pas identité |
| E1 | Frontière ADC Paie | oral | ADC | groupe | `GroupPayrollProvider` lit `esbtp_salaires` | pas de client API Paie | **contrat d’échange** | D-03 | P1 | Voir 07 |
| F1 | Base patrimoniale | oral | moyens gén. | campus | `Classroom` mort ; `salle` string | non retrouvé | nouveau | — | P1 | Local = ressource planning |
| F2 | Maintenance + incidents | oral | moyens gén. | bien | non retrouvé | — | nouveau | F1, C3 | P2 | Ticket fermé ≠ efface indispo/coût |
| F3 | Carnet véhicules | oral | moyens gén. | flotte | non retrouvé | — | nouveau | F1 | P2 | Compteur, assurance, entretien |
| F4 | Planification déplacements | oral | moyens gén. | mission | non retrouvé | — | nouveau | D4, F3 | P2 | Conflit véhicule/salle → action planning |
| F5 | Comparaison d’offres (pas prix min) | oral | achats | dossier | non retrouvé | — | inclus C3 | C3 | P1 | Grille coût/délai/garantie/conformité |
| G1 | Notes : zéro ≠ absence ≠ dispense | cahier | pédagogie | note | partiel (`noteEffective`, dispense BTS) | LMD natures incomplètes | extension | B4 | P0 | Même résultat partout |
| G2 | Capitalisation / dettes / redoublement | cahier | jury | parcours | wallet crédits | moyenne parcours absente | extension | B5 | P1 | Pas de double comptage crédits |
| G3 | Réclamations notes | cahier | scolarité | étudiant | non retrouvé (hors unlock jury) | — | **différé** | B7 | P3 | Portail + fenêtre : pas avant ouverture |
| G4 | Fin de cycle : soutenance objet | cahier | scolarité | parcours | `TypeSeance::SOUTENANCE` + frais | pas d’objet mémoire | nouveau | — | P1 | Encadreur, rapporteur, PV |
| G5 | Attestation de **réussite** | artifact | scolarité | étudiant | fréquentation seulement ; commentaire LMDBulletinService | non retrouvé | extension | jury publié | P1 | Document État |
| G6 | Diplôme / parchemin | cahier | rectorat | étudiant | non retrouvé | — | extension | G5, D-07 | P1 | Émetteur = rectorat (à confirmer) |
| G7 | Supplément au diplôme | artifact UEMOA | toutes LMD | diplôme | non retrouvé | — | différé 2027 | G6 | P3 | Toutes instances LMD |
| G8 | Accréditation / homologation en colonne parcours | artifact | scolarité | parcours | non retrouvé | — | extension | D-06 | P1 | Homologation État ≠ CAMES ≠ agrément enseignant |
| G9 | Planning : salles structurées, conflits pièce | cahier | programmation | campus | conflit enseignant ; skip duplication | salle string | extension | F1 | P1 | Conflit aussi à l’import et hors semaine |
| G10 | Duplication semaine / suggestion / solveur | artifact | programmation | instance | duplication **présente** | suggestion partielle ; solveur absent | vendre duplication ; solveur **non** | G9 | P3 | Solveur seulement si salles + indispo datées |
| G11 | IA métier décideuse | cahier | produit | invariant | Claude search/navigate | — | invariant | — | P0 | Aucune publication / paiement LLM |
| H1 | Isolation inter-instances | SaaS | ADC | technique | DB+files par code | user MySQL partagé | correction ADC | — | P0 | API/fichier d’une instance inaccessibles à l’autre |
| H2 | Isolation intra-instance (composante) | UCAO | scolarité | composante | absente | — | extension | B1 | P0 | Export et jobs inclus |
