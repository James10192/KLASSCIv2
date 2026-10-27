# Mettre en service une nouvelle école sur Dokploy

Durée : 15 minutes, dont 5 d'attente. Aucune commande à taper.
Pour qui : toute personne ayant un compte sur Dokploy. Aucune connaissance technique requise.

Ce que Dokploy fait seul, à partir du fichier `deploy/docker/docker-compose.yml` :
l'application, la base de données, le planificateur, les volumes de stockage,
la clé de chiffrement et le certificat HTTPS.

Ce que vous faites : choisir un nom, un code, un domaine et un mot de passe, puis
remplir l'assistant de l'école.

---

## Avant de commencer

Ayez sous la main :

| Information | Exemple | Règle |
|---|---|---|
| Nom de l'école | `Lycée Moderne de Cocody` | Tel qu'il doit s'afficher partout (en-têtes, PDF). |
| Code de l'école | `lmc-cocody` | Minuscules, chiffres et tirets seulement. Il ne change plus ensuite. |
| Domaine | `lmc-cocody.klassci.com` | Le code suivi de `.klassci.com`. |
| Mot de passe de la base | 20 caractères au hasard | Générez-le avec votre gestionnaire de mots de passe. Personne n'aura à le retaper. |

## 1. Créer le service dans Dokploy

1. Ouvrez le projet **KLASSCI-Universite**, environnement **production**.
2. **Create Service** → **Compose**. Nom : le code de l'école (`lmc-cocody`).
3. Onglet **General** :
   - Provider : **GitHub**, dépôt `James10192/KLASSCIv2`, branche `presentation`.
   - **Compose Path** : `./deploy/docker/docker-compose.yml`
   - **Save**.

## 2. Renseigner les quatre variables

Onglet **Environment**, collez en remplaçant les valeurs :

```
NOM_ECOLE=Lycée Moderne de Cocody
CODE_ECOLE=lmc-cocody
DOMAINE=lmc-cocody.klassci.com
MOT_DE_PASSE_BASE=le-mot-de-passe-de-20-caracteres
```

Puis **Save**.

> **École hors de Côte d'Ivoire** : ajoutez aussi la ligne `FUSEAU=…` maintenant,
> avant tout déploiement. Le fuseau ne se change plus sans dégâts une fois des
> données saisies. Voir `docs/runbooks/ucao-benin-mise-en-service.md`.

## 3. Brancher le domaine

1. Onglet **Domains** → **Add Domain**.
2. Service : **app**. Host : le domaine (`lmc-cocody.klassci.com`). Port : **80**.
3. Cochez **HTTPS**, certificat **Let's Encrypt**. **Create**.
4. Chez l'hébergeur DNS de `klassci.com`, ajoutez un enregistrement **A** :
   nom `lmc-cocody`, valeur : l'adresse IP du serveur Contabo.

## 4. Déployer

Cliquez sur **Deploy**. Comptez 3 à 5 minutes la première fois.

Le déploiement est bon quand l'onglet **Deployments** affiche **Done**, et que
`https://lmc-cocody.klassci.com` affiche l'assistant de mise en service.

**Si le déploiement échoue avec un message « Renseignez … »**, une variable de
l'étape 2 manque ou est vide : complétez-la et redéployez.

## 5. Remplir l'assistant de l'école

Ouvrez `https://<domaine>/install`.

1. **Préparer** : vérifiez que tout est vert, puis continuez.
2. La base de données est reconnue toute seule : l'assistant passe directement à **Initialiser**.
3. **Initialiser** : cliquez une fois, puis attendez une à deux minutes. Vous pouvez
   recharger la page sans risque : l'initialisation continue sur le serveur et
   l'écran reprend son suivi.
4. **Administrateur** : créez le compte du responsable de l'école.
5. **Finaliser** : vous arrivez sur la page de connexion. Connectez-vous avec ce compte.

L'école est en service. Les redéploiements suivants (mises à jour) ne la font
jamais repasser par l'assistant.

---

## Si quelque chose ne va pas

| Ce que vous voyez | Ce que ça veut dire | Que faire |
|---|---|---|
| `Bad Gateway` sur le domaine | L'application n'a pas encore démarré, ou a échoué au démarrage. | Attendez une minute. Sinon, onglet **Logs**, service **app** : la première ligne en rouge dit pourquoi. |
| Le domaine ne répond pas du tout | L'enregistrement DNS n'est pas encore actif. | Attendez quelques minutes, vérifiez l'enregistrement A. |
| Logs : `ATTENTION : aucun volume…` | Le service a été créé en type **Application** et non **Compose**. | Recréez-le en suivant l'étape 1. |
| L'assistant réapparaît après une mise à jour | Le volume de l'école a été supprimé. | Ne supprimez jamais un volume d'une école en service. Prévenez l'équipe technique. |
| L'initialisation affiche une erreur | Le message et la fenêtre noire disent la cause. | Envoyez une capture à l'équipe technique. Relancer est sans danger. |

## Ce qu'il ne faut jamais faire

- Supprimer un volume (`documents`, `instance`, `journaux`, `base`) d'une école en service :
  c'est supprimer ses données.
- Changer `CODE_ECOLE` ou `MOT_DE_PASSE_BASE` après la mise en service.
- Créer deux services pour la même école : deux planificateurs enverraient tout en double.

## Pour l'équipe technique

- La clé `APP_KEY` est générée au premier démarrage dans le volume `instance`
  (`storage/instance/app.key`). Pour reprendre une école existante (migration
  depuis cPanel), posez plutôt `APP_KEY` dans l'onglet Environment avec la
  valeur d'origine, avant le premier déploiement.
- Le mot de passe root de MySQL est aléatoire et inconnu : l'application n'en a
  pas besoin. Pour une intervention, utilisez l'utilisateur `klassci`.
- La file d'attente est en `sync` (aucun worker ne tourne). Pour passer en
  `database`, ajoutez d'abord un worker.
- Le planificateur tourne dans le service `planificateur`, en `www-data`. Ne
  créez pas de tâche planifiée Dokploy en plus.
- Variables facultatives : voir `deploy/docker/ecole.env.example`.
