# Messages & Centre d’actions

Cette note décrit la transition du module `/messages` vers une séparation explicite entre communication humaine et opérations métier.

## Principe produit

- **Boîte de réception** : conversations privées, groupes et échanges humains.
- **Centre d’actions** : validations, corrections, réclamations et demandes internes qui nécessitent une décision ou un suivi.
- **Contexte lié** : étudiant, inscription, paiement ou autre dossier consultable sans quitter le module.
- **Nanan** : aide contextuelle qui prépare résumés, réponses et propositions d’actions ; aucune action sensible n’est envoyée ou exécutée sans confirmation humaine.

## Compatibilité avec l’existant

Les conversations historiques de type `workflow` restent stockées dans `chat_conversations`. `MessageHubProjection` les transforme en `workflowAction` pour la nouvelle interface, afin de conserver les notifications, deep-links et cartes métier existants.

La nouvelle table `workflow_actions` prépare la migration progressive vers une vraie entité de tâche métier. Les producteurs de workflow historiques pourront être migrés progressivement sans conversion destructive des conversations existantes.

## Frontend

Le projet reste sur son architecture Laravel Blade/JavaScript existante : aucun SDK de chat, React ou framework UI supplémentaire n’est introduit uniquement pour le redesign. Le module utilise `public/css/messages-hub.css` et `public/js/messages-hub.js`.

Les points de rupture responsive sont conçus ainsi :

- desktop large : liste, conversation, contexte ;
- tablette : contexte en panneau repliable ;
- mobile : navigation progressive liste → conversation → contexte/action.

## Étapes suivantes

La première phase pose l’architecture, le rendu, la projection de compatibilité et la persistance cible. Les étapes suivantes sont de brancher les commandes CRUD de `workflow_actions`, la création persistante des groupes d’équipe et les préférences de conversation (important, épinglé, archivé, attente/résolu), puis de migrer progressivement les événements métier existants vers la nouvelle entité.
