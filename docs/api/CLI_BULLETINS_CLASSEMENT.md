# Audit des statistiques BTS sur cohorte canonique (CLI)

Lecture seule via `GET /api/cli/diagnostics/bulletins/classement`.
Auth : Sanctum, aptitude `cli:read`, limite de 10 appels/minute
(en plus de la limite CLI globale).

## Besoin

Un bulletin d'un etudiant classe premier peut afficher une « plus forte
moyenne » superieure a sa moyenne si les deux chiffres sont issus de calculs
differents. L'API `/students?class_id=...` peut aussi omettre des eleves dont
la classe de S2 est resolue par les phases (inscription administrative
portee par une autre classe).

Cette route appelle **RankingService::calculerRangsClasse** : meme cohorte,
meme assiduite, meme moyenne que le rang de l'application. Aucune mutation.

## Appel cible pour le signalement ESBTP Yamoussoukro

Depuis un checkout qui contient la nouvelle version de klassci-cli.ps1,
**une fois cette API disponible sur un environnement de recette** :

```powershell
.\klassci-cli.ps1 diagnostics:bulletins:classement esbtp-yakro bulletin_id=2714
.\klassci-cli.ps1 diagnostics:bulletins:classement esbtp-yakro bulletin_id=2814
```

Le `bulletin_id` fixe la classe, l'annee, le semestre et l'etudiant.
Autre forme (sans bulletin) :

```powershell
.\klassci-cli.ps1 diagnostics:bulletins:classement esbtp-yakro classe_id=123 annee_universitaire_id=45 periode=semestre2 etudiant_id=789
```

Le bloc `statistiques` donne le maximum/minimum/moyenne calcules sur
l'ensemble de la cohorte. Le bloc `cohorte` compare l'effectif canonique
et le perimetre exact de `GET /api/cli/students?class_id=&annee_id=`.
Le bloc `ecart_liste_students` donne la note maximale et le rang des
membres presents dans la cohorte par phases, absents de cette liste.

Le serveur ne renvoie ni nom, ni matricule, ni identifiant des autres
etudiants. L'option etudiant cible n'expose que ses moyennes/rang/etat.

**Attention** : les anciennes instances ne possedent pas cette route
avant leur deploiement. Un `404` sur une instance non mise a jour ne
prouve pas qu'une cohorte est vide.

## Critere de recette

Pour un bulletin du semestre 2 dont le rang est `1`, comparer a deux
decimales la `moyenne_canonique` de `etudiant_cible` et
`statistiques.plus_forte_moyenne`. Elles doivent etre egales. Comparer
ensuite `cohorte.membres_canoniques` a l'effectif du PDF et verifier
les echanges de phases quand `cohorte.hors_liste_students > 0`.

Ne jamais appeler ici les commandes `bulletins:recalculate-ranks`,
`bulletins:backfill-averages` ou `resultats:moyennes` : elles ecrivent.
