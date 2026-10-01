# Portail rendez-vous — convocation multicanale

Cette évolution complète le flux public de rendez-vous après vérification du contact.

## Parcours couvert

1. La candidature ou la réinscription est déposée.
2. Le contact est vérifié par e-mail ou WhatsApp.
3. Un rendez-vous est attribué ou retrouvé.
4. Une convocation est mise en file sans bloquer la réponse HTTP du portail.
5. La livraison utilise le canal disponible et peut basculer de l'e-mail vers WhatsApp lorsque le premier envoi échoue et qu'un numéro exploitable est disponible.
6. Le PDF de convocation reste accessible par une URL signée et temporaire.

## Suivi enregistré

La réservation conserve le canal réellement utilisé, la destination masquée, l'identifiant de message du transport, le statut, les tentatives, l'utilisation éventuelle du fallback, les dates d'envoi/livraison et le dernier motif d'échec.

## Consultation et renvoi

Le portail public peut consulter un rendez-vous existant à partir de la référence et de la date de naissance. La réponse contient l'URL signée du PDF ainsi que le suivi de convocation.

Le point d'entrée `POST /api/public/rendez-vous/renvoyer` remet uniquement la convocation de la réservation existante dans la file. Il ne crée, ne déplace et n'annule aucun rendez-vous.

## WhatsApp et PDF

Le message WhatsApp contient pour l'instant le lien signé vers le PDF. L'envoi du PDF comme document natif ne doit être activé qu'après validation du contrat exact des pièces jointes/documents côté MailPulse.

## Exploitation

Le CLI dispose d'un renvoi ciblé des convocations. L'opération est en simulation par défaut et nécessite `--execute` pour modifier l'état serveur.
