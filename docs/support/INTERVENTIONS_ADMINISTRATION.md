# KLASSCI Care — demandeur ou administration

## Deux parcours réellement distincts

- **Répondre au demandeur** : message visible par l'auteur du ticket. Si « Attendre une réponse du demandeur » est coché, seul son retour libère l'attente. Cela peut être un étudiant.
- **Administration de l'établissement** : message `SCHOOL_STAFF` non visible par l'étudiant. Le support peut demander une action à la scolarité. L'instance avertit dans sa cloche les utilisateurs habilités ; leur réponse, publiée dans la conversation réservée, rend le ticket au support.
- **Notes internes/techniques** : invisibles au demandeur comme à l'établissement.

## Paramétrage de permissions obligatoire

Pour intervenir depuis KLASSCI, un personnel doit recevoir **les deux permissions effectives** :
`support.tickets.view_school` et `support.tickets.respond_school`.

Le nouveau droit doit être synchronisé depuis le registre des permissions et accordé par le responsable habilité. **Aucun rôle métier, établissement ni utilisateur n'est désigné automatiquement.** Les profils universels auxquels le registre attribue toutes les permissions conservent ce comportement général ; seules les personnes possédant les deux permissions effectives peuvent intervenir. Tant qu'aucun destinataire n'est configuré, le suivi garde durablement une action en attente et la réessaie (avec un délai de reprise de 15 minutes), même lorsque la fenêtre normale de synchronisation est dépassée. Il recontrôle le statut du Master pour éviter de notifier une action déjà résolue ; le défaut est également journalisé. Le support ne doit pas considérer une intervention comme distribuée avant confirmation dans la cloche de l'établissement.

## Compatibilité, déploiement et recette

1. Migrer l'instance (colonne du suivi), synchroniser les droits et attribuer explicitement les deux permissions.
2. Déployer la fonctionnalité correspondante côté Master et sa migration `waiting_on`.
3. Vérifier depuis un compte étudiant qu'un message de scolarité n'apparaît ni dans `scope=mine` ni dans ses notifications, même si le dossier attend la scolarité.
4. Vérifier qu'un personnel autorisé reçoit l'alerte, peut voir l'action, répondre, et que le ticket repart vers le support.
5. Vérifier qu'un personnel de lecture seule et une autre instance ne peuvent ni lire une action protégée ni répondre.
6. Vérifier qu'une réponse supplémentaire de l'étudiant ne lève pas l'attente de la scolarité ; les notes internes restent privées.

Ne pas activer le nouveau parcours sans une version Master/instance compatible.
