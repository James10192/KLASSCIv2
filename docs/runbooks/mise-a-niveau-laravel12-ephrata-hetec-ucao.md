# Mise à niveau d'Ephrata, HETEC et UCAO Bénin vers presentation

État relevé le 1er novembre 2026. Ces trois branches sont sur `744101e51`
(26 octobre), environ 160 commits derrière presentation.
Abidjan, Yakro, ISLG et USAT ont déjà franchi ce pas.

## Pourquoi `klassci pull` ne suffit pas

- `composer.json` passe de Laravel 9 à 12, Sanctum 3 à 4, Spatie Permission 5 à 6,
  DomPDF 2 à 3, webpush 8 à 10. `vendor/` n'est pas versionné : sans
  `composer install`, le code neuf tourne sur d'anciens paquets.
- Précédent : Abidjan, 29 octobre, erreur 500 sur le middleware Spatie jusqu'au
  `composer install`, puis erreur SQL sur `email_notification_enabled` jusqu'à la
  migration (`incidents-techniques-admin-klassci.md`).
- Deux migrations sont nouvelles : `2026_10_29_110000` et `2026_10_29_160000`
  (annonces). Sept anciennes ont été retouchées, sans effet si elles sont déjà
  passées. `php artisan migrate:status` le confirme avant tout.

## Situation de départ

| École | Dossier supposé | Connexion | Ordre |
|---|---|---|---|
| HETEC | `~/public_html/hetec` | 500, trait WebPush introuvable | 1 |
| UCAO Bénin | `~/public_html/ucao-benin` | 500 | 2 |
| Ephrata | `~/public_html/ephrata` | 200 | 3, seule à avoir quelque chose à perdre |

Les dossiers viennent de la documentation : vérifier avec `ls` avant tout. Le 500
de HETEC montre une page d'erreur technique : contrôler `APP_DEBUG=false`.
Le message du 500 d'UCAO n'a pas été lu : le relire à l'étape 1.

## Prérequis, à vérifier une fois

```bash
php -v            # doit être 8.3.x, sinon sélecteur PHP cPanel
composer --version
df -h ~           # place pour vendor et la sauvegarde
```

## Procédure, une école à la fois

Terminal cPanel, en remplaçant `ECOLE`. Une école n'est commencée que si la
précédente a passé l'étape 7.

**1. État initial**

```bash
cd ~/public_html/ECOLE
git status --short && git log -1 --oneline
grep -E '^(APP_DEBUG|APP_TIMEZONE|DB_DATABASE|QUEUE_CONNECTION)=' .env
tail -n 30 storage/logs/laravel.log
```

Fichiers modifiés ou non suivis inattendus : s'arrêter et les signaler.

**2. Sauvegarde hors `/tmp`, vérifiée non vide**

```bash
mkdir -p ~/backups
STAMP=$(date +%Y%m%d_%H%M%S)
mysqldump --single-transaction --routines --triggers -u UTILISATEUR -p BASE > ~/backups/ECOLE_$STAMP.sql
ls -lh ~/backups/ECOLE_$STAMP.sql
git rev-parse HEAD > ~/backups/ECOLE_$STAMP.commit
cp -a vendor ~/backups/ECOLE_vendor_$STAMP
```

**3. Pousser la branche, depuis le poste de développement**

```bash
KLASSCI_PROPAGER_SANS_NOUVEAUTES="mise a niveau ECOLE, runbook laravel12" git push origin origin/presentation:refs/heads/ECOLE
```

**4. Mettre à jour le serveur**

```bash
php artisan down || true   # peut échouer si l'école est déjà en 500
git pull origin ECOLE
composer install --no-dev --no-interaction --optimize-autoloader
```

Composer respecte `composer.lock`. Ne jamais lancer `composer update`.

**5. Migrations**

```bash
php artisan migrate:status | grep -v Ran
php artisan migrate --force
```

Lire la liste avant de lancer. Jamais `migrate:fresh`, `migrate:reset` ni `db:wipe`.

**6. Caches et permissions**

```bash
php artisan optimize:clear
php bin/deploy/fix_permissions.php
php artisan up
```

Vider aussi l'opcache : `klassci cache:clear ECOLE` depuis le poste si l'école y
est configurée (Ephrata l'est ; HETEC et UCAO demandent un jeton), sinon redémarrer
PHP depuis cPanel.

**7. Contrôles avant l'école suivante**

- `/login` répond 200, connexion réelle réussie.
- Pages : tableau de bord, étudiants, paiements, un bulletin PDF, un reçu PDF, notes.
- `tail -n 50 storage/logs/laravel.log` sans nouvelle erreur.
- `composer show laravel/framework | grep versions` indique 12.x.
- Le planificateur tourne : `GET /api/cli/planificateur` rend `actif: true`.

La campagne d'annonces par e-mail reste à activer séparément
(`annonces-courriels-mailpulse.md`) : aucun envoi de masse dans cette procédure.

## Retour arrière

Si l'étape 7 échoue et que la cause n'est pas trouvée en quelques minutes.
`STAMP` est l'horodatage de la sauvegarde de l'étape 2 :

```bash
php artisan down
git checkout "$(cat ~/backups/ECOLE_STAMP.commit)"
mv vendor ~/backups/ECOLE_vendor_rejete_STAMP
cp -a ~/backups/ECOLE_vendor_STAMP vendor
php artisan optimize:clear
php artisan up
```

Pour HETEC et UCAO, ce retour ramène à l'état de départ, donc à l'erreur 500.

Les deux migrations d'annonces n'ajoutent que des colonnes et une table : l'ancien
code les ignore, donc on ne restaure pas la base. Restaurer le dump effacerait tout
ce qui a été saisi depuis : décision à prendre par une personne, jamais par réflexe.

La branche distante reste en avance : le signaler avant le prochain `klassci pull`.

## Limites

Cette procédure n'a pas été jouée telle quelle sur ces écoles. Le dossier, l'accès
à composer et la version de PHP de chaque école n'ont pas pu être vérifiés depuis
le poste. Elle s'appuie sur le précédent d'Abidjan et sur le diff du 1er novembre.
