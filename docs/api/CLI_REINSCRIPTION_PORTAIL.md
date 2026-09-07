# API CLI — Diagnostic du portail de réinscription

## GET `/api/cli/reinscription/portail/diagnose`

Dit **pourquoi** le portail public de réinscription (`klassci.com`) répond
« Aucun dossier ne correspond » pour un matricule donné.

Auth : Bearer token, ability `cli:admin`.

L'ability n'est pas `cli:read` à dessein : la réponse nomme la date de naissance
enregistrée, c'est-à-dire la moitié du facteur d'identification du portail.

### Pourquoi ce point d'entrée existe

Le portail public rend **volontairement la même réponse** pour cinq causes
distinctes — c'est ce qui empêche d'énumérer les matricules d'un établissement.
Le diagnostic est le pendant interne de cette uniformité : il tranche, mais
seulement pour qui détient déjà un jeton d'administration.

Ne jamais rebrancher cette logique sur une surface publique : ce serait
exactement l'oracle que le portail évite.

### Paramètres

| Champ | Requis | Format | Rôle |
|---|---|---|---|
| `matricule` | oui | chaîne, 50 max | Le matricule tel que la famille le saisit |
| `date_naissance` | non | `AAAA-MM-JJ` | La date saisie. Omise, la comparaison est sautée |

Le format de date a la même sévérité que le portail : accepter ici ce qu'il
refuse reviendrait à diagnostiquer autre chose que le problème vécu.

### Réponse

`trouve` dit ce que le portail aurait répondu. Quand il vaut `false`, `cause`
nomme le verrou :

| `cause` | Portée | Ce qu'il faut faire |
|---|---|---|
| `annee_cible_absente` | **toute l'école** | Désigner l'année de rentrée, ou marquer une année comme courante |
| `annee_cible_sans_date_de_debut` | **toute l'école** | Renseigner la date de début de l'année visée |
| `matricule_inconnu` | un dossier | Voir `matricules_proches` |
| `etudiant_supprime` | un dossier | Restaurer le dossier si la suppression était une erreur |
| `date_de_naissance_absente_en_base` | un dossier | Renseigner la date sur la fiche |
| `date_de_naissance_differente` | un dossier | Faire saisir la date figurant dans `etudiant.date_naissance` |
| `aucune_inscription_anterieure` | un dossier | Voir `inscriptions[].anteriorite_utilisable` |

Les deux premières lignes refusent **tous** les élèves, en silence : les
distinguer d'une faute de saisie est la raison d'être de ce diagnostic.

`inscriptions[]` porte `annee_start_date` et `anteriorite_utilisable` : une
inscription dont l'année n'a pas de date de début est écartée du calcul
d'antériorité, alors qu'elle s'affiche normalement partout ailleurs dans
l'application. C'est la panne la plus difficile à voir autrement.

`canal` rapporte l'interrupteur et la fenêtre de dates. Un canal fermé produit
un « service indisponible », **jamais** « aucun dossier » : un visiteur qui lit
« aucun dossier » ne bute donc pas là-dessus.

### Lecture seule

Le diagnostic ne dépose aucune demande, ne corrige aucune fiche et ne consomme
aucun quota du portail.

### Équivalent en ligne de commande

```
php artisan reinscription:portail-diagnose MESBTP25-0070 --date=2007-09-15
```

Le point d'entrée HTTP ne fait qu'exécuter cette commande avec `--json`. Il
existe parce que le serveur de production n'est pas joignable en SSH : sans lui,
il faudrait ouvrir un terminal cPanel avec une scolarité au téléphone.

## Historique

- 2026-09-07 : création. Motivée par un refus du portail sur `MESBTP25-0070`
  (ESBTP Abidjan) que ni la famille, ni l'école, ni l'équipe ne pouvaient
  expliquer.
