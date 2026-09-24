# API LMS — Jeton serveur et droits d'écriture

Lot 1 du plan « Liaison LMS–KLASSCI » (septembre 2026).

## Deux sortes de jetons

| Jeton | Obtenu par | Droits | Sert à |
|---|---|---|---|
| **Utilisateur** | `POST /api/lms/auth/login` | `lms:access` | Ce qui est propre à la personne : profil, tableau de bord, saisie de l'enseignant |
| **Serveur** | CLI, par le responsable KLASSCI | `lms:serveur` + `lms:lecture`, `lms:notes`, `lms:presences` | Les tâches de fond du LMS : synchronisation, remontées |

Le jeton serveur est porté par le compte technique **« Service LMS »**
(identifiant `service-lms`), créé au premier jeton : aucun rôle, un mot de passe
aléatoire jamais communiqué. Il ne peut pas se connecter à l'application.

Les droits sont lus **dans la liste du jeton** : un jeton portant `*` ne passe
jamais pour le jeton serveur.

## Gérer les jetons serveur (CLI, jeton `cli:admin`)

```
POST   /api/cli/lms/jeton-serveur        { "nom": "lms-prod", "droits": ["lms:lecture","lms:notes","lms:presences"], "remplacer": false }
GET    /api/cli/lms/jetons-serveur        liste, sans les secrets (id, nom, droits, dernier usage)
DELETE /api/cli/lms/jeton-serveur/{id}    révocation immédiate
```

- `droits` : par défaut les trois.
- `remplacer: true` révoque tous les jetons serveur existants avant d'en créer un
  (rotation).
- **Le jeton en clair n'est rendu qu'une fois**, à la création. Il se transmet
  au LMS par un canal privé.
- Création et révocation sont écrites au journal (`CLI: jeton serveur LMS …`),
  sans le secret.

## Qui peut écrire par l'API du LMS

Les routes d'écriture de `LMSDataController` ne contrôlaient que la connexion.
Or le login du LMS donne un jeton à **tous** les utilisateurs, élèves compris :
un élève pouvait écrire ses propres notes, pointer une visio ou déclencher des
rappels. Elles passent désormais par `App\Support\Lms\GardeEcritureLms`.

| Route | Autorisé |
|---|---|
| `POST /api/lms/evaluations/{id}/notes` | jeton serveur avec `lms:notes` · administration, coordination, direction des études · enseignant de l'évaluation (`enseignant_id`) ou de la matière pour l'année |
| `POST /api/lms/attendances/from-video-session` | jeton serveur avec `lms:presences` · encadrement · enseignant de la séance |
| `POST /api/lms/notifications/send-session-reminder` | idem |

Refus : **403**, avec un message.

**Commentaire de la note** : une mise à jour ne remplace plus le commentaire de
l'enseignant par « Note soumise via LMS ». Il n'est modifié que si le LMS en
envoie un. À la création, le commentaire reste « Note soumise via LMS[ - …] ».

## Lecture avec le jeton serveur

Avec `lms:lecture`, le jeton serveur lit toute l'école sur les routes filtrées
par rôle (`applyRoleFilters`), comme la coordination. Sans ce droit : aucune
ligne.

## Limites de débit

| Qui | Limite | Clé |
|---|---|---|
| Jeton serveur | réglage `lms.serveur.limite_par_minute`, **600** par défaut (60 au minimum) | le jeton |
| Autres jetons | 60 / minute | l'utilisateur |
| `check-user`, `check-availability` | 10 / minute **par identifiant recherché**, plus une enveloppe par IP : réglage `lms.decouverte.limite_ip_par_minute`, **120** par défaut | identifiant + IP, et IP |

Avant, la recherche d'utilisateur était limitée à 10 par minute **et par IP** :
tous les usagers du LMS sortant de la même IP se partageaient ces 10 appels.

## Historique

- **Septembre 2026** — création : jeton serveur, gardes d'écriture sur les trois
  routes, commentaire préservé, limites par jeton et par identifiant.
  **Changement visible par le LMS** : ces trois routes répondent désormais `403`
  à un élève ou à un compte sans lien avec l'évaluation ou la séance.
