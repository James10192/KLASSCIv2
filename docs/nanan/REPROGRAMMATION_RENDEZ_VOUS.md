# Nanan — reprogrammation administrative des rendez-vous

## Besoin couvert

Quand un jour de réception doit être retiré alors que des familles ont déjà un rendez-vous, Nanan peut désormais constater les réservations concernées puis préparer leur reprogrammation en masse.

Exemple de demande :

> J’ai fermé les rendez-vous du vendredi 9 octobre. Reprogramme toutes les familles sur les prochains créneaux disponibles et renvoie leurs convocations.

## Parcours Nanan

1. Nanan appelle `lire_rendez_vous` avec `date=AAAA-MM-JJ` pour lire les réservations actives de la campagne courante.
2. Elle appelle `proposer_reprogrammation_rdv` avec `date_source=AAAA-MM-JJ`.
3. La proposition indique le nombre de familles, les créneaux cibles et les créneaux source qui seront fermés.
4. Rien n’est modifié avant le clic **Valider**.
5. À la validation, tout le lot est traité dans une transaction : fermeture du jour source, déplacement, journalisation et remise des nouvelles convocations en file.

## Règles de répartition

- Seuls les créneaux postérieurs au jour supprimé sont utilisés.
- Les jours qui ne figurent plus dans `inscriptions.rdv.jours_ouverts` sont exclus, même si d’anciens créneaux de ces jours sont encore marqués ouverts.
- Les créneaux sont pris dans l’ordre chronologique.
- Un créneau complet ou déjà commencé n’est jamais utilisé.
- Si les places restantes ne suffisent pas à toutes les familles, Nanan refuse la proposition : aucun déplacement partiel n’est fait.

## Fermeture du jour source

Tous les créneaux de la date source appartenant à la campagne courante sont fermés dans la même transaction, y compris ceux qui étaient encore vides. Le verrou appliqué avant la fermeture empêche qu’une nouvelle réservation publique se glisse entre la simulation et l’exécution.

Si la liste des réservations a changé entre la proposition et la validation, la proposition devient périmée et doit être recalculée.

## Traçabilité

Chaque famille déplacée conserve une trace dans `esbtp_rdv_reprogrammations` :

- réservation ;
- ancien créneau ;
- nouveau créneau ;
- auteur du déplacement ;
- `non_venue=false`, puisqu’une fermeture administrative future n’est pas une absence.

Le déplacement réutilise `ReservateurRdv::replacerAuGuichet()` afin de conserver les verrous et contrôles de capacité déjà utilisés par l’accueil des rendez-vous.

## Convocation après déplacement

Chaque réservation déplacée est replannifiée avec `convocation_action=deplace`. La file `FileConvocationsRdv` assure ensuite l’envoi selon le comportement multicanal existant.

À ce stade, KLASSCI suit un canal principal par convocation :

- e-mail en priorité lorsqu’il est joignable ;
- WhatsApp si aucun e-mail valide n’est disponible ;
- WhatsApp en fallback lorsque l’échec e-mail est propre au destinataire et permet un basculement.

Le double envoi systématique **e-mail + WhatsApp** n’est pas activé par ce lot : le suivi actuel (`convocation_canal`, destination et identifiant de message uniques) ne permettrait pas de relancer un seul canal sans risquer de dupliquer l’autre. Une évolution dédiée devra introduire un suivi par canal avant d’activer ce mode de manière fiable.

## Garde-fous testés

`tests/Feature/Assistant/NananRdvReprogrammationTest.php` couvre notamment :

- lecture des réservations d’une date ;
- absence d’écriture avant validation ;
- fermeture des créneaux occupés et vides du jour supprimé ;
- exclusion d’un futur vendredi retiré des réglages ;
- déplacement atomique vers les jours autorisés ;
- journalisation des déplacements ;
- remise en attente des convocations déplacées ;
- refus complet lorsque la capacité disponible est insuffisante.
