# 01 — Lecture de décision

Observation : 15 septembre 2026. Preuves : [02](02-carte-des-preuves.md). Pas de pourcentage de couverture : le dénominateur « tout KLASSCI » n’est pas mesurable.

## 1. Ce que UCAO-UUC est, et ce qu’on ne sait pas

**Rapporté par les entretiens (Marcel, 15/09, et dossier artifact 14–15/09)** — pratique, pas preuve légale :

- Une unité à **Cotonou**, quatre composantes dans **une** instance : EGEI, ESMEA, FSAE, FDE.
- ~28 parcours, ~170 maquettes. Diplôme délivré par le rectorat (Ouagadougou cité). Bénin, +229, UTC+1, XOF.
- ESMEA = École de Management et Économie Appliquée (pas un rôle PHP).
- TPE réalisés **à l’école**. Agréments enseignants, fiches d’exécution / prestation / facturation, validation **SP** (sigle conservé, intitulé à confirmer).
- Chaîne financière interne : caissier, préparateur d’achats + stock, chargé des dépenses, trésorerie, visas chef comptable / DAF / président.
- RH employés et pointage : ADC Paie, pas KLASSCI. Pointage « code sur PC pro + IP ».

**Non établi par le web le 15/09** : sites `ucao.org` / `www.ucao.org` = « Déploiement en cours » ; `uuc.ucao.edu.bj` et `ucao.edu.bj` injoignables. Un site public ne prouve ni l’usage d’un logiciel ni l’absence de données à reprendre.

**Hypothèses périmées — ne pas reconduire**

| Hypothèse | Origine | Actualité |
|---|---|---|
| « prospect / aucun système / un développeur » | dossiers commerciaux anciens | Client nommé, instance `ucao-benin` déjà prévue (branche git + runbook). |
| « aucun diplômé à la prochaine rentrée » | inférence rentrée | **Rejetée.** Université existante : attestation de réussite, relevé, soutenance peuvent être dus dès la reprise. |
| Tarifs / dates d’ouverture | slides marketing adminKlassci | Hors contrat. Non repris. |
| Renouvellement agrément `+3 ans` | inférence CAMES historique | **Rejetée.** Échéance sur l’objet, pas une constante. |
| `serviceTechnique` = DSI UCAO | lecture naïve du label | **Faux.** Label « African Digit Consulting », `visible_in_ui => false`. |

Le périmètre **contractuel** n’est pas déduit des besoins : ce dossier dit quoi construire ; il ne dit pas ce qui est vendu.

## 2. État de l’existant (limites)

KLASSCI n’est pas un ERP universitaire vide. C’est un **SaaS scolarité + encaissement étudiant + LMD** déjà en production CI, avec un master Filament séparé.

**Réutilisable tel quel (chaîne prouvée)**

- Inscriptions, classes, frais configurables, échéanciers, paiements, avoirs, journal de caisse, clôture caisse, rapprochement **par mode de paiement**.
- LMD : domaines / mentions / parcours, UE/ECUE par parcours, notes, bulletins, jurys, PV immuable, relevé (gabarit KLASSCI ou MESRS), rattrapage, wallet de crédits, compensation inter-UE.
- TPE : journal + stratégies auto / enseignant.
- Emploi du temps, duplication de semaine, conflit **enseignant**.
- Audit Owen-it, registre `config/permissions.php`, rôles custom.
- Documents : bulletin, certificat, attestation de **fréquentation**, PV, relevé.
- Chatbot staff : recherche et navigation, **aucune mutation métier**.

**Anomalies démontrées (à corriger avant de vendre « prêt UCAO »)**

1. **Trois moyennes annuelles** distinctes (jury pondéré crédits, relevé, PV Excel arithmétique). Pas de moyenne de parcours.
2. Pondération CC/examen **stockée, affichée, non consommée**. Les PV l’omettent volontairement.
3. Génération de bulletin LMD **relit la maquette vivante** — pas de composition gelée dans `presentation`.
4. Import Excel notes : correctifs sur branche d’audit, **non fusionnés** dans `presentation`.
5. TPE **interdit** à la planification (dur), alors que UCAO le fait sur site.
6. Paie enseignants : assiette = durée **planifiée** de la séance émargée ; barème ITS/CNPS **ivoirien** en défaut.
7. `superAdmin` = `Gate::before` tout vrai : ne peut pas « donner un droit de jury sans l’exercer ».
8. Isolation : un **utilisateur MySQL partagé** `c2569688c_tenant` pour tous les tenants.

**Non retrouvé dans le périmètre inspecté**

Grand livre SYSCOHADA, achats–commandes–réceptions–factures fournisseurs (tables orphelines sans modèles), stocks, patrimoine, maintenance, véhicules, missions RH, fiches de poste, agréments enseignants, attestation de réussite, diplôme, supplément au diplôme, soutenance comme objet, équivalence d’UE, administrateur d’instance, impersonation (volontairement absent), client ADC Paie, e-MECeF, allowlist IP de pointage.

## 3. Risques immédiats

| Risque | Gravité | Pourquoi maintenant |
|---|---|---|
| Brancher UCAO sans fuseau `Africa/Porto-Novo` | haute | Horodatages irrattrapables après la 1re inscription. Runbook + `tenant:provision --timezone` sur **origin/main** adminKlassci (`b4bff89`, 14/09). Clone local adminKlassci **en retard**. |
| Téléphone 229 + préfixes CI | haute | Relances perdues. Réglage existant, à poser **avant** candidatures. |
| Paie / ITS CI appliqués au Bénin | haute | `PayrollComputationService` défaut 75k/16/21/32 + CNPS 6,3 %. Profil non validé doit **bloquer** le calcul définitif. |
| Fusionner la branche d’audit sans rebase | moyenne | `claude/ucao-uuc-audit-services-oi2uyn` = 33 commits d’avance / 9 de retard vs `presentation`. Contient EDT, SoD, LMD. Pas déployé. |
| Construire un GL dans KLASSCI | haute (produit) | Deux comptabilités concurrentes. Les écoles CI n’en ont pas besoin pour encaisser. |
| Donner `serviceTechnique` au DSI UCAO | haute | `*` + pages paywall / style bulletin. |
| Impersonation « voir comme » | haute | Corrompt l’audit. Refus maintenu. |

## 4. Périmètre UCAO recommandé

**Dans KLASSCI, même instance, quatre composantes**

Scolarité LMD (déjà là) + TPE sur site (setting) + enseignants (agréments, service fait, file SP) + AR étudiant (déjà là) + **achats opérationnels, stock, patrimoine, maintenance, véhicules** + administration d’instance.

**Hors KLASSCI comme maître**

- Livres officiels (SYSCOHADA / SYCEBNL) : produit comptable dédié **ou** expert-comptable, export depuis KLASSCI. Décision D-04.
- Paie salariés, déclarations sociales, ayants droit : **ADC Paie**. KLASSCI n’invente pas l’API.
- Abonnement SaaS, style bulletin, impersonation infra : **ADC / serviceTechnique**.

**Différé, périmètre visible**

Supplément au diplôme (échéance UEMOA 2009, toutes instances LMD, visée rentrée 2027). Solveur d’emploi du temps. Jury assisté par LLM. e-MECeF (formalités DGI, pas une API maison).

## 5. Recommandation d’architecture

### Invariants techniques (non configurables)

- Une base + un arbre fichiers par instance. Pas de `tenant_id` mutualisé.
- Autorisation à **chaque** requête (middleware Spatie + politiques objet). IDOR = refus.
- Écriture financière : pas de suppression silencieuse d’un encaissement validé ; avoir / extourne.
- Document officiel émis = snapshot immuable + version. Réémission ≠ écrasement.
- Pas de LLM dans l’arithmétique de note, de jury, de paie, de paiement.
- Identité de l’opérateur réel toujours journalisée. Pas d’impersonation.

### Variabilité (settings / permissions / objet daté)

Période d’évaluation, pondération CC/examen **une fois consommée**, TPE planifiable, seuils d’achat, circuit d’approbation, régime enseignant, profil fiscal **pays**, fuseau, téléphone, gabarit de relevé (MESRS déjà prévu).

### Quatre frontières de données

| Domaine | Maître | Consommateurs |
|---|---|---|
| Étudiant, inscription, notes, jury, documents académiques | KLASSCI instance | Portail, PDF, export MESRS |
| Encaissement étudiant, échéancier, avoir | KLASSCI instance | Caisse, rapprochement modes, export SAARI |
| Relation pédagogique enseignant, agrément, service certifié | KLASSCI instance | Paie heures KLASSCI **ou** export vers ADC Paie |
| Contrat salarié, pointage employé, bulletin de paie, social | ADC Paie | KLASSCI : identifiant + statut sync seulement |
| Achats, stock physique, incidents, véhicules | KLASSCI instance (nouveau) | Trésorerie opérationnelle ; export vers livres |
| Livres, plan de comptes, clôture d’exercice | **à décider** (D-04) | Jamais un second journal « officiel » dans KLASSCI si un outil externe tient les livres |

### Socle d’approbation (un, pas un BPMN)

États `brouillon → soumis → (visas parallèles ou séquentiels) → approuvé | refusé | revenu`. Conditions : montant, nature, budget, entité. Délégation datée. Changement de montant / IBAN / bénéficiaire **invalide** les visas. File unique « À traiter ». Réutiliser le motif déjà vu sur paiements / jurys / salaires, pas un moteur universel.

### Ce qu’on ne construit pas

Microservices, event sourcing, EAV, studio BPMN, migration Laravel 9 → 12 pour UCAO, génération magique d’EDT, rôle `ucao`, biométrie de pointage.

## 6. Ordre de réalisation (impératif métier, pas confort)

1. **Rentrée / première inscription UCAO** : fuseau, téléphone 229, admin d’instance, permissions scolarité+jury, maquettes importables, TPE setting.
2. **Premier paiement enseignant** : heures constatées vs planifiées, régime salarié/prestataire, **profil Bénin explicite** (même non validé = calcul non définitif), file SP.
3. **Premiers achats** : besoin → stock → engagement → commande → réception → facture → proposition de paiement. Pas le GL.
4. **Première clôture de caisse** : déjà là. Clôture d’**exercice** : seulement après D-04.
5. **Première délivrance de documents** : relevé MESRS déjà là ; **attestation de réussite** avant toute promo sortante ; diplôme / parchemin ensuite.

Les établissements CI ne reçoivent ces modules que par **permissions** : Yakro peut rester caisse + client.

## 7. Arbitrage construction vs intégration

| Besoin | Choix | Raison |
|---|---|---|
| Scolarité LMD | Étendre KLASSCI | Chaîne complète déjà testée. |
| AR étudiant | Étendre KLASSCI | Production CI. |
| P2P / stock / patrimoine | **Construire dans KLASSCI** | Pas d’existant vivant (tables mortes). Besoin quotidien UCAO. Pas un GL. |
| Grand livre / états officiels | **Intégrer** (export) sauf si D-04 dit le contraire | AUDCIF/SYSCOHADA est un métier d’expert ; KLASSCI n’a même pas `compte_id` consommé. |
| RH salariés / paie / social | **ADC Paie** | Absent des deux dépôts. Double saisie temporaire par import sécurisé. |
| Paie heures enseignants vacataires | Rester dans KLASSCI jusqu’à sync | Déjà un circuit prepare/validate/pay. |
| e-MECeF | Ne pas « développer l’API » | Parcours DGI + agrément SFE. Inconnu si UCAO y est assujetti. |
