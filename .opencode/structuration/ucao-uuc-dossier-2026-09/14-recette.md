# 14 — Recette Given / When / Then

**Vérifiable aujourd’hui** = code `presentation` `eafab30b1` + tests **fichiers** (exécution PHPUnit : non, MySQL local down).  
**À construire** = lots 1+.

## Déjà vérifiables (fichiers / lecture)

### Isolation inter-instance (partielle)

Given un utilisateur instance A.  
When il appelle l’URL / API / fichier de B.  
Then refus (processus séparés).  
**Limite** : user MySQL partagé peut encore lire B en SQL — **échec H1 jusqu’au lot infra**.

### Dispense LMD refusée

Given un parcours LMD.  
When POST dispense UE.  
Then 422 « Les dispenses ne sont pas disponibles en LMD » (`DispenseEndpointTest`).

### TPE non planifiable par défaut

Given setting actuel (dur).  
When création séance TPE.  
Then refus (`ESBTPSeanceCoursController` ~427).

### CC/examen non calculé

Given `lmd_cc_weight=40`.  
When génération bulletin.  
Then moyenne = coeffs d’évaluations, pas 40/60 (`LmdAcademicRuleProfile` + `calculerMoyenneECUE`).

### Chatbot sans mutation

Given un staff.  
When outil Claude.  
Then lecture / navigation seulement.

### Avoir et lock caisse

Given paiement validé.  
When avoir.  
Then nature `avoir`, pas suppression (`AvoirService`).

### Relevé MESRS disponible

Given setting gabarit `mesrs`.  
When émission relevé.  
Then template `lmd-releve-notes-mesrs-v1`.

## À construire (lots)

### Deux composantes, deux règles

Given ESMEA semestre, FDE année (si choisi).  
When chaque une évaluation.  
Then rattachement à **sa** période, sans toucher les décisions déjà publiées.  
Lot 2.

### Maquette nouvelle / ancienne cohorte

Given version 2026-09 effective pour cohorte 2026.  
When rectification maquette 2025.  
Then bulletins 2025 = composition gelée.  
Lot 2.

### Zéro / absence / dispense

Given trois étudiants.  
When saisie 0, absence, équivalence.  
Then trois résultats distincts identiques portail / bulletin / relevé / décision.  
Lots 2+11+B6.

### Admin instance sans abonnement ni auto-paiement

Given administrateur d’instance.  
When ouvre paywall / tente de se donner `paiements.validate`.  
Then refus.  
Lot 1.

### Permission + hors périmètre / hors instance

Given `lmd.notes.view` ESMEA.  
When note FDE, ou instance Yakro, ou export, ou URL fichier.  
Then 403 partout.  
Lots 1–2. OWASP : check ressource, pas seulement rôle.

### TPE sur site

Given mode `seance_encadree`.  
When séance validée avec durée retenue.  
Then journal OK. When Wi-Fi seul ou double déclaration. Then **pas** d’heures payables.  
Lot 3.

### Séance écourtée + remplaçant

Given 2 h planifiées, 1 h constatée, remplaçant B.  
When certification.  
Then 1 h (ou règle choisie) à B, 0 à A.  
Lot 4.

### Agrément expiré

Given service fait en mars, agrément échu en avril.  
When nouvelle affectation mai. Then refus. When paie mars. Then dette intacte.  
Lot 4.

### Réception partielle

Given commande 100, reçu 40.  
When facture 100 sans motif. Then refus. When facture 40. Then stock +40 une fois. Reliquat 60 visible.  
Lot 5–6.

### Mutation post-visa / délégation expirée

Given commande visée.  
When IBAN modifié. Then visas invalidés. When délégué après `to`. Then 403.  
Lots 1+5.

### Paiement indéterminé / replay

Given ordre état inconnu.  
When retry. Then pas de second mouvement tant que non vérifié. When webhook rejoué. Then idempotent.  
Lot 5–7.

### Écriture / période close

Given période lockée.  
When écriture ou mutation interdite. Then refus. When correction autorisée. Then lien vers l’original.  
Existant recettes ; étendre P2P / D-04.

### Sortie stock / immo / dépense

Given un bien.  
When sortie service vs cession. Then deux événements. Ajustement = motif + approbateur. Pas de double compte.  
Lot 6.

### Maintenance → planning

Given OT qui ferme une salle.  
When création / duplication / import séance. Then conflit actionnable.  
Lots 6+8.

### Pointage exception

Given IP allowlist + panne PC.  
When régularisation. Then événement brut intact + correction approuvée. When IP seule. Then **insuffisant**.  
Lot 9 (Paie).

### Rejet sync Paie

Given `hours.certified` rejeté.  
When timeout. Then pas « synchronisé ». When relance même clé. Then un seul mouvement.  
Lot 9.

### Profil Bénin

Given instance BJ `non_valide`.  
When calculer. Then indicatif, payer refusé. When `CI` Yakro. Then barème CI inchangé.  
Lot 4.

### Réémission document

Given relevé v1 publié, rectification.  
When v2. Then v1 conservée, v2 en vigueur, QR minimal.  
Étendre OfficialDocument.

## Tests proposés (fort impact)

- Domain : composition gelée, moyenne unique, 3-way lignes, idempotence paiement.
- Intégration : permissions composante sur HTTP **et** CLI **et** export.
- Interdit : cross-tenant fichier, Gate::before UCAO, impersonation absente, TPE→paie.

Commande locale non exécutée : `php artisan test --testsuite=Feature` (SQLSTATE 2002, 15/09).
