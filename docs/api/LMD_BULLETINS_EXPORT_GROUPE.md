# Export PDF groupé des bulletins LMD

## Fonctionnement

Sur **ESBTP → LMD → Bulletins**, le bandeau « Impression groupée » permet de :

- sélectionner individuellement des bulletins, y compris lors du défilement infini ;
- choisir tous les bulletins correspondant aux filtres courants (classe, année, semestre, recherche) ;
- lancer un **aperçu** ou un **téléchargement** d'un PDF unique, sans rechargement de page.

Les filtres et le total viennent de la même requête serveur que la liste. Un changement de filtre remet à zéro la sélection manuelle. Le plafond est de **400 bulletins par tâche**, pour limiter la charge de rendu DomPDF et d'assemblage.

Le serveur fige la liste des identifiants autorisés au lancement et la traite par tranches de 6 grâce à l'ordonnanceur déjà employé pour les bulletins BTS. Il génère chaque page par le moteur **LMD officiel** (`ESBTPLMDBulletinController::buildBulletinPdf`), puis assemble les PDF via `BulletinBulkPdfExporter` ; aucun calcul de moyenne, crédit ou rang BTS n'est appelé.

## API AJAX

`POST /esbtp/lmd/bulletins/export-pdf/lancer`

Permissions cumulatives : `module.lmd.access`, `lmd.bulletins.view` et `lmd.bulletins.export.bulk`. Protection CSRF, authentification et limite de 10 lancements par minute.

Corps JSON :

```json
{
  "mode": "telechargement",
  "portee": "selection",
  "ids": [12, 15, 19],
  "classe_id": 5,
  "annee_universitaire_id": 3,
  "semestre": 2,
  "search": ""
}
```

Pour exporter l'ensemble des résultats filtrés, envoyer `"portee": "filtres"` et `"ids": []`. Les filtres sont facultatifs. Une sélection explicite doit appartenir *intégralement* au périmètre des filtres actifs et à une classe LMD.

- **201** : `{ "success": true, "tache": { "id": …, "domaine": "lmd", "statut": "en_attente", "total": … } }` (avec les autres champs standards des tâches).
- **422** : filtre vide, dépassement du plafond, sélection incohérente, impayé ou autorisation d'impression manquante. Aucune tâche n'est créée.
- **403** : permissions insuffisantes ou retirées.
- **429** : limitation de fréquence.

Le front poursuit avec les routes mutualisées `POST /esbtp-special/bulletins-taches/{tache}/avancer` et `GET /esbtp-special/bulletins-taches/{tache}`, puis le lien de fichier renvoyé par le serveur. La progression et les erreurs restent sur le même écran. L'ordonnanceur `bulletins:traiter-taches` continue si la page est fermée.

## Sécurité et conservation

L'accès à une tâche est réservé à son demandeur (les tâches LMD ne sont pas mutualisées entre utilisateurs). Le contrôleur, chaque tranche de rendu et le téléchargement final recontrôlent les droits. `DocumentPrintGuard` applique les paramètres existants d'approbation et de solde à chaque bulletin ; si un seul échoue, **aucun PDF partiel n'est publié**. Un bulletin supprimé ou des droits retirés interrompent également la tâche.

Le fichier PDF final est conservé au maximum **72 heures** par la purge existante. Les fichiers temporaires sont supprimés après assemblage ou échec. Les notifications de fin et les liens de retour pointent vers la liste LMD avec ses filtres ; le suivi des exports BTS ne change pas.

## Vérifications recommandées

```bash
php artisan test --filter=TachesBulletinsArrierePlanTest
php artisan test --filter=DocumentPrintGuardTest
```

Effectuer aussi un parcours navigateur sur une instance de test : sélection multiligne, scroll infini, changement des filtres, aperçu, téléchargement, rôle sans permission, approbation refusée et impression d'au moins deux bulletins LMD de semestres différents. Le serveur nécessite le moteur DomPDF/FPDI et l'ordonnanceur habituel.
