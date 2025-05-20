Ça c’est pour le côté enseignant

La fonctionnalité qu'il faudra implémenter après avoir réglé les bugs éventuels sur l'application, il faudra aussi savoir bien faire les tests aussi tu vas devoir remonter même jusqu'à la base de données et au fichier de migration parce que ce n'est pas moi qui ait fait les updates donc on peut s'attendre à tout

Nous devons développer une fonctionnalité d'émargement permettant aux enseignants de confirmer leur présence aux cours. Le système doit être sécurisé via un code généré quotidiennement pour garantir que seuls les enseignants physiquement présents puissent émarger.

PRINCIPE DE FONCTIONNEMENT :

1. Génération du code quotidien

-   Le secrétariat ou le superadmin génère un code unique chaque jour
-   Code alphanumérique de 6 caractères (ex: ABC123)
-   Expiration automatique après 24h (configurable)
-   Possibilité de générer un nouveau code en cas de besoin
-   Le code est affiché sur un écran au secrétariat ou communiqué en personne

2. Process d'émargement

-   L'enseignant ouvre l'application
-   Accède à la section "Émargement"
-   Voit la liste de ses cours du jour (basée sur son emploi du temps)
-   Saisit le code du jour pour chaque cours
-   Confirme sa présence
-   Reçoit une confirmation d'émargement

3. Sécurité du système

-   Code valide uniquement pour la journée en cours
-   Chaque code ne peut être utilisé qu'une fois par enseignant par cours
-   Impossibilité d'émarger en avance ou avec retard (configurable)
-   Blocage après 3 tentatives incorrectes

4. Interface administrateur

-   Génération du code du jour
-   Visualisation en temps réel des émargements
-   Rapports de présence par enseignant/cours/période
-   Alertes pour les absences non justifiées
-   Export des données d'émargement

5. Interface enseignant

-   Vue de l'emploi du temps du jour
-   Champ de saisie du code
-   Statut de l'émargement (fait/à faire)
-   Historique des émargements
-   Notifications de rappel
