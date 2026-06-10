# Rule: Exécuter la suite de tests KLASSCIv2 en local (Windows/XAMPP) + flux de vérif pre-merge

## Quand s'active

Cette rule s'active quand tu :
- Dois **exécuter `php artisan test`** / PHPUnit / Pest en local sur KLASSCIv2 (notamment des tests `RefreshDatabase`)
- Vérifies une feature **avant merge** (Plan C, bulletins, BTS TC, compta, etc.)
- Vois un test échouer avec `SQLSTATE[42S22]`, `1452 FK constraint`, `Configuration manquante`, ou « tous les tests DB échouent identiquement »
- Veux le **flux complet** test → merge → déploiement → e2e → backfill sur KLASSCIv2 multi-instance

## Pourquoi cette rule existe

Session Plan C (10 juin 2026) : pour valider 16 tests Feature/Unit, j'ai perdu du temps sur des
frictions d'environnement **non documentées** : MySQL local à relancer, `RefreshDatabase` très lent sur
Windows, sortie Pest masquée par un pipe `| tail`, et surtout une **migration seed pré-existante qui
cassait toute la suite** depuis le 4 juin. Cette rule capture la recette pour ne plus jamais reperdre ce temps.

---

## 1. Démarrer MySQL local (XAMPP)

Pas de service Windows — `mysqld` se lance à la main :

```bash
# en arrière-plan (run_in_background)
C:/xampp/mysql/bin/mysqld.exe --defaults-file=C:/xampp/mysql/bin/my.ini --console
```

Vérifier la connexion + l'existence de la DB de test :
```bash
php -r "try{new PDO('mysql:host=127.0.0.1','root','');echo 'UP';}catch(Exception \$e){echo 'DOWN';}"
# klassci_testing doit exister (sinon la créer : CREATE DATABASE klassci_testing)
```

DBs : `klassci_local` (dev, peut être **vide** de données métier → inutile pour un dry-run réel),
`klassci_testing` (tests), `klassci_master` (SaaS). **Ne JAMAIS** `migrate:fresh`/wipe sur autre chose
que `klassci_testing` (rule `no-migrate-fresh`).

## 2. Config DB de test

- `phpunit.xml` : les lignes `DB_CONNECTION`/`DB_DATABASE` sont **commentées** → Laravel charge
  `.env.testing` (qui pinne `DB_DATABASE=klassci_testing`) sous `APP_ENV=testing`. C'est normal, ne pas
  « corriger » phpunit.xml.
- `RefreshDatabase` **re-migre tout le schéma** (≈180 tables) à chaque run → **lent (~5-8 min/run** sur
  Windows/XAMPP). C'est le goulot, pas un hang. Lance une seule fois un filtre large plutôt que N runs
  par classe (chaque run re-migre).

## 3. Lancer les tests SANS te tirer une balle dans le pied

```bash
# ❌ MAUVAIS : | tail -N bufferise TOUT → 0 sortie tant que ce n'est pas fini, impossible de voir un hang
php artisan test --filter="..." | tail -60

# ✅ BON : log direct + marqueur de fin + monitor
php artisan test --filter="Foo|Bar" > /tmp/suite.log 2>&1; echo "EXIT=$?" >> /tmp/suite.log
```

Puis suivre via l'outil **Monitor** (until-loop sur le marqueur), JAMAIS un `sleep` foreground :
```bash
until grep -q "EXIT=" /tmp/suite.log; do sleep 5; done
```

**Marqueurs Pest** (pas PHPUnit) : la fin = `Tests:  N passed` / `Duration:` ; un échec = `FAIL` / `⨯`.
Ne grep PAS `FAILURES`/`ERRORS` (PHPUnit) — Pest ne les émet pas → faux négatif de monitor.

Warning bénin Windows : `TTY mode is not supported on Windows platform.` → ignorer.

## 4. Piège fondateur : migration seed avec `created_by => 1` casse RefreshDatabase sur DB vide

**Symptôme** : sur `klassci_testing` fraîche, `migrate:fresh` plante avec
`SQLSTATE[23000] 1452 FK ... settings.created_by → users(id)` → schéma incomplet → **TOUS les tests DB
échouent identiquement** (le test pur sans DB passe). Faux signal de « 6 bugs de logique ».

**Cause** : une migration qui **seed des données** (souvent `settings`) hardcode `created_by => 1`
(et `updated_by => 1`). Sur un tenant réel l'user id 1 existe ; sur une DB de test vide, non → FK casse.

**Diagnostic rapide** (au lieu de subir 8 min de tests à l'aveugle) :
```bash
php artisan migrate:fresh --env=testing --force > /tmp/mig.log 2>&1
grep -iE "FAIL|SQLSTATE|1452" /tmp/mig.log   # 'create_failed_jobs_table' = faux positif (mot "failed")
```

**Fix canonique** (vu sur les 3 migrations réconciliation 2026-06-04) — `created_by`/`updated_by`
= 1er user existant, `null` si DB vide (FK `ON DELETE SET NULL` accepte null) :
```php
$creatorId = DB::table('users')->min('id'); // null sur DB vide
// ... 'created_by' => $creatorId, 'updated_by' => $creatorId,
```
Ne change PAS le comportement prod (user 1 = min id), et débloque toute la suite de tests.

**Anti-pattern à BLOQUER en review** : toute migration qui `insert` dans une table à FK vers `users`
avec `created_by => 1` / `updated_by => 1` en dur.

## 5. Autres causes fréquentes d'échec de tests Feature (écrits sans DB)

Les agents écrivent souvent des tests sans pouvoir les exécuter (MySQL down) → setups incomplets :
- **`genererDonneesBulletin` lève « Configuration bulletin manquante »** (BulletinService) : il EXIGE un
  `ESBTPBulletin` pré-existant avec `config_matieres` (JSON `generales`/`techniques` non vides) +
  `professeurs`. → seeder ce bulletin (trait réutilisable `tests/Feature/Bts/Concerns/SeedsConfiguredBulletin`).
- **Colonne factory inexistante** (`Unknown column 'decision'`) : la factory écrivait `decision` au lieu de
  `decision_conseil`. → vérifier les noms de colonnes réels (`SHOW COLUMNS`).
- **`abort(422)` avalé → 302** : un `catch (\Exception)` large convertit un `HttpException` en redirect.
  → `catch (HttpExceptionInterface) { throw $e; }` AVANT le catch large (vrai bug prod, pas que le test).
- **Mock Mockery `->with(Carbon)`** par identité échoue si le service re-hydrate l'objet → matcher par
  valeur via `Mockery::on(...)`.
- **`new BulletinService(...)`** dans les tests : le ctor a évolué (Plan C → 3 args :
  `ESBTPAbsenceService`, `BtsAnnualClassMapResolver`, `BtsBulletinCohortResolver`). Mettre à jour l'arité.

**Règle** : préférer corriger le **setup du test** ; ne corriger le **code prod** que si tu prouves un vrai
bug (et BTS uniquement, jamais de fichier LMD — rule `lmd-bts-bulletin-separation`).

## 6. Flux complet de livraison + vérif (validé Plan C, 10 juin 2026)

```
1. TESTS LOCAUX  → MySQL up → migrate:fresh --env=testing (sanity) → php artisan test --filter (log+monitor) → 0 failed
2. MERGE         → git fetch + rebase origin/presentation → push → gh pr create → gh pr merge <n> --merge --admin
                   → git checkout presentation → git merge --ff-only origin/presentation
3. SYNC TENANTS  → for t in esbtp-abidjan esbtp-yakro ephrata hetec rostan; do git push origin presentation:$t; done
4. DÉPLOIEMENT   → klassci pull presentation && klassci cache:clear presentation (opcache reset)
                   → smoke : curl -s -o /dev/null -w "%{http_code}" https://presentation.klassci.com/login  (attendu 200)
5. E2E TENANT    → /klassci-test-e2e (diagnostics CLI + navigateur) — voir le skill
6. BACKFILL      → dry-run d'abord, JAMAIS prod Élite direct (voir skill + runbook)
```

Notes infra découvertes :
- **Pas de SSH** au serveur LWS depuis la machine dev (`web44.lws-hosting.com:22` timeout) → les commandes
  artisan serveur (ex: backfill) se lancent via le **terminal cPanel** (`!` dans le prompt), pas en SSH.
- Le binaire `klassci` (PATH) ≠ le wrapper `klassci-cli.ps1` (repo, diagnostics BTS-TC riches). Le binaire
  n'a **pas** de passthrough artisan arbitraire → pour une commande custom serveur, soit cPanel, soit créer
  un endpoint `/api/cli/*` + commande CLI (rule `feedback_cli_create_commands`).
- Workflow multi-commits séquentiel : peut **caler sur une erreur socket transitoire** → `TaskStop` +
  `git stash -u` le partiel + relancer `Workflow({scriptPath, resumeFromRunId})` (c1-cN reviennent du cache).

## Anti-patterns à BLOQUER

1. ❌ `php artisan test | tail` (masque progression + hang) → log direct + Monitor
2. ❌ Conclure « N bugs de logique » quand TOUS les tests DB échouent identiquement → suspecter migration/seed d'abord
3. ❌ Migration seed avec `created_by => 1` hardcodé (FK users) → `DB::table('users')->min('id')`
4. ❌ Lancer la suite par classe (re-migration à chaque run) → un seul filtre large
5. ❌ `migrate:fresh` sur `klassci_local`/un tenant → uniquement `klassci_testing`
6. ❌ Lancer un backfill/artisan custom sur prod en SSH (pas de SSH) ou en buildant sur prod (rule `never-build-on-prod-server`)
7. ❌ Corriger du code prod pour faire passer un test quand c'est le **setup du test** qui manque

## Voir aussi

- `.claude/rules/pre-merge-checklist.md` — quels tests exiger selon le diff
- `.claude/rules/feature-delivery-methodology.md` — méthodologie 13 phases
- `.claude/rules/multi-agent-git-safety.md` — merge PR-via-gh, ff-only
- `.claude/rules/migrations.md` — `php artisan make:migration`
- `.claude/skills/klassci-test-e2e/SKILL.md` — e2e tenant réel (diagnostics + navigateur)
- Mémoire : `plan-c-tc-bulletins-blueprint-2026-06.md` (env test + bugs trouvés)
