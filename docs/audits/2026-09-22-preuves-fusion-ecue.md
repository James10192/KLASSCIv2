# Preuves d'exécution — fusion d'ECUE en doublon

**Branche** `claude/eloquent-wright-q09r8f` · **22 septembre 2026**

## Ce que ces preuves sont, et ce qu'elles ne sont pas

Elles viennent de l'application **réellement exécutée** — `php artisan serve`,
base MariaDB 10.11 migrée par la chaîne complète des migrations, rôles et
permissions synchronisés par `bin/deploy/fix_permissions.php`, navigateur
Chromium piloté par Playwright. Les écrans sont les vrais, les requêtes HTTP sont
les vraies.

Elles **ne** viennent **pas** d'un tenant de production : les données sont un
jeu de démonstration fabriqué pour l'occasion (deux ECUE en doublon, deux élèves
LMD). La moyenne à 0,00 visible sur la
capture 6 est celle d'un bulletin de démonstration jamais généré. Une validation
sur `presentation` reste à faire après déploiement.

## 1. Fusion d'ECUE — `/esbtp/lmd/reconciliation`

| capture | ce qu'elle montre |
|---|---|
| ![](assets/deplacements-notes-2026-09/01-groupes-detectes.png) | Deux groupes d'ECUE en doublon détectés. |
| ![](assets/deplacements-notes-2026-09/02-apercu-bloque-avec-impact.png) | Fusion bloquée (l'élément absorbé porte 2 notes) **et son impact affiché**, dont les 2 lignes de bulletin LMD : c'est sur lui que se coche « Forcer ». Avant, un refus ne montrait rien. |
| ![](assets/deplacements-notes-2026-09/03-apercu-bloque-400px.png) | Le même aperçu à 400 px. |
| ![](assets/deplacements-notes-2026-09/04-fusion-effectuee.png) | Après la fusion forcée, la fenêtre reste ouverte sur les bulletins LMD à régénérer. Le second élève porte une collision, nommée. |
| ![](assets/deplacements-notes-2026-09/05-fusion-effectuee-400px.png) | Le même compte rendu à 400 px, sans défilement horizontal. |
| ![](assets/deplacements-notes-2026-09/06-lien-vers-bulletins-filtres.png) | Le lien d'un bulletin ouvre `/esbtp/lmd/bulletins?classe_id=1&annee_universitaire_id=1&semestre=3&search=ET-2026-014` : filtres pré-remplis. |
| ![](assets/deplacements-notes-2026-09/07-sans-permission-liste-non-cliquable.png) | Rôle personnalisé avec `lmd.reconciliation.manage` mais **sans** `lmd.bulletins.view` : la liste reste lisible, sans lien (`href` nul), et dit à qui la transmettre. |
| ![](assets/deplacements-notes-2026-09/08-sans-permission-400px.png) | Le même à 400 px. |

État de `esbtp_lmd_resultats_ecues` après la fusion, relu en base :

| id | élève | élément | moyenne | note de rattrapage |
|---|---|---|---|---|
| 1 | KOUASSI | BRDM321 (conservé) | 8,00 | **12,00** — reportée depuis l'élément absorbé |
| 2 | TRAORÉ | TPRDM321 (absorbé) | 11,00 | — laissée en place : collision |
| 3 | TRAORÉ | BRDM321 (conservé) | 14,00 | — |

Aucune erreur JavaScript relevée pendant les deux parcours.

**Postérieur à ces captures, et non rejoué au navigateur** : sous « Forcer », la
fusion reporte aussi les moyennes enregistrées (`esbtp_resultats`) de l'élément
absorbé sur l'élément conservé, et nomme celles en collision (bloc
d'avertissement du compte rendu). La preuve de ce report est un test :
`MergeDuplicateEcueSousForceTest`, dont le premier cas tombe à 7 au lieu de 10
sur le certificat quand on retire le report.

## Ce qui n'est plus prouvé ici

Deux sections suivaient : la rebascule CLI (`POST /api/cli/evaluations/{id}/matiere`)
et le changement de semestre depuis l'écran d'une évaluation. Elles prouvaient une
implémentation de cette branche que #1132, fusionné entre-temps dans
`presentation`, a remplacée par la sienne (recalcul synchrone, moyennes laissées
et signalées au lieu d'être mises de côté). Elles ont été retirées plutôt que
laissées décrire un code qui n'existe plus. Les tests de #1132 couvrent ces
chemins ; ils n'ont pas été rejoués ici dans un navigateur.

## Rejouer

Les scripts (amorçage des données, parcours Playwright) ne sont pas versionnés :
ils écrivent dans une base locale dédiée. Leur recette : base MariaDB neuve
(jamais `migrate:fresh` hors `klassci_testing`), `php artisan migrate`,
`php bin/deploy/fix_permissions.php`, `APP_INSTALLED=true`, service sur le port
**8000** (sur un autre port, `AppServiceProvider::forcerLesUrlsDeBase()` préfixe
les URL par `public`). Chromium doit faire confiance à l'autorité du proxy du bac
à sable et ne passer par lui qu'en HTTPS, sans quoi Alpine et Bootstrap, servis
par CDN, ne se chargent pas.
