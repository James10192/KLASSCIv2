# Compte financier étudiant — règles métier et recette

## Paiement avant réinscription : pas de nouveau dossier
Le **paiement d'une ancienne dette non reportée** reste rattaché à l'ancienne inscription. Il passe par le même formulaire `paiements.create` avec l'identifiant de cette inscription (catégories et règles de répartition habituelles), même lorsque l'étudiant n'est pas réinscrit. La recherche « Régler une ancienne année » donne accès aux anciennes inscriptions, y compris sur mobile. Elle inclut les inscriptions d'une année achevée (`terminée`), même sans inscription courante, mais jamais les inscriptions annulées. Elle ne crée aucun dossier 2026-2027.

Sur la fiche étudiant, les anciennes années distinguent maintenant explicitement le montant **non reporté** (encaissement direct) du montant **transféré en reliquat** (circuit dédié). Une année avec dette transférée n'est pas déclarée « soldée » par erreur.

La partie déjà reportée est déduite du plafond de chaque frais d'origine. Un paiement ou une ventilation manuelle qui empiète sur ce montant est refusé côté serveur ; les frais non reportés restent payables directement.

Le **reliquat reporté** ne peut exister qu'après réinscription avec décision explicite de report. Son versement suit l'action transactionnelle `CreerVersementReliquat`, `type_paiement=reliquat`, et ne réduit le solde qu'une fois validé. Les deux cas sont présentés dans le même dialogue « Régler un arriéré », avec deux actions différentes bien identifiées.

### Comprendre les chiffres de paiements.index
- « Tous les encaissements » : toutes années de créance confondues, selon les dates de paiement filtrées.
- « Frais de l'année courante » : versements ordinaires liés aux inscriptions de l'année courante, hors reliquats reportés.
- « Règlements d'anciennes dettes » : versements des anciennes inscriptions et versements de reliquats reportés, y compris ceux rattachés à l'inscription récente.
- **Chaque ligne et KPI compte un paiement, jamais un nouvel inscrit.** L'effectif officiel vient exclusivement de `StudentCountService`, sur les inscriptions actives finalisées de l'année ; aucun règlement de dette ne crée ni ne finalise une inscription. Les exports de paiements héritent de la même portée via `PaymentFilterService`.

## Portée
La situation globale **lit** les écritures existantes : `ESBTPFraisSubscription`, `ESBTPPaiement`, `ESBTPReliquatDetail`. Elle ne duplique ni créance ni paiement. Le restant de l'année est le dû des souscriptions moins les paiements **validés, hors reliquat**, moins la créance déjà transférée. Un reliquat conserve l'inscription source et est affiché à part au solde encore ouvert. Les anciennes inscriptions non transférées restent exigibles.

Un encaissement de reliquat porte `type_paiement=reliquat` et `reliquat_detail_id`; il n'est jamais un règlement des frais de la nouvelle année. Un paiement `en_attente` réserve le montant du reliquat dès la création, mais n'éteint la dette qu'à validation. Un avoir validé recrédite la créance d'origine. Le moteur existant de correction des ventilations reste en place.

## Permission d'école
Dans `/esbtp/roles-permissions`, les habilités attribuent la nouvelle permission :
`reinscriptions.reliquats.report` (Inscriptions → « Autoriser la réinscription avec report des impayés »).
Pas d'attribution automatique à tous les caissiers ou secrétaires ; SuperAdmin conserve ses droits `*` et peut distribuer ce droit. Elle ne donne pas `paiements.create`, `paiements.validate` ni le droit d'abandonner une dette.

Après mise à jour de l'instance :
```bash
php bin/deploy/fix_permissions.php
php artisan permissions:audit
```
La commande s'exécute sur **chaque instance** seulement au moment d'un déploiement autorisé.

## Parcours de réinscription
- Voir les dettes de toutes les inscriptions lorsque `finances.etudiants.voir` le permet.
- Si des impayés empêchent l'inscription, la permission `reinscriptions.reliquats.report` autorise la poursuite, mais le report doit être **choisi explicitement**. L'abandon est une décision SuperAdmin distincte.
- Destination année / décision / affectation dans des sélecteurs premium ; filière/niveau/dernière classe restent sans rechargement.
- La finalisation `PUT /esbtp/reinscription/{etudiant}` accepte toujours la navigation classique et produit, avec `Accept: application/json`, un succès `{success:true, message, inscription_id, url}`, erreurs structurées `{message, errors}` (422), ou échec (500). Le formulaire `fetch` affiche l'erreur avec focus et évite le second clic pendant traitement. Une réponse perdue peut avoir précédé un COMMIT : vérifier le dossier avant de réessayer.

## Précautions avant déploiement
- Contrôler les frais facturés et les paiements sur un dossier à 3 années avant/après (2024–2025, 2025–2026, 2026–2027).
- Faire un remboursement/avoir sur un ancien règlement et vérifier le solde restant ; vérifier la protection de deux paiements en attente.
- Contrôler qu'aucune permission financière n'apparaît chez un lecteur non habilité.
- Conserver les états / reçus anciens et rapprocher les anomalies historiques **manuellement**, jamais de backfill automatique depuis la page.
- Capture réelle sur `presentation` (ordinateur + mobile) de la fiche étudiant, la fiche inscription, la réinscription, les listes de classe, les messages d'erreur AJAX et les droits par rôle. **Aucune propagation** aux branches écoles sans cette preuve.

## Tests ciblés
```bash
php vendor/bin/phpunit tests/Feature/Finances/SituationFinanciereEtudiantTest.php --no-coverage
php vendor/bin/phpunit tests/Feature/Reinscription/FicheReinscriptionTest.php --no-coverage
```
