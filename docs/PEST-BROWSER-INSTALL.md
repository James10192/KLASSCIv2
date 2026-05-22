# Pest Browser Plugin — Installation Guide

## Pourquoi

Le chantier emploi-temps LMD unification (PR14 spécifiquement) nécessite des tests E2E Browser via Pest. La stack actuelle inclut `pestphp/pest-plugin` mais pas le plugin Browser.

## Installation

```bash
# Depuis la racine du projet KLASSCIv2
composer require pestphp/pest-plugin-browser --dev

# Initialiser la config Pest pour Browser
php artisan pest:install

# Si le artisan command n'existe pas (selon version Pest), créer manuellement :
mkdir -p tests/Browser/Pages
mkdir -p tests/Browser/Flows
```

## Configuration

### `phpunit.xml` — ajouter le testsuite Browser

```xml
<testsuite name="Browser">
    <directory>tests/Browser</directory>
</testsuite>
```

### `tests/Pest.php` — pour browser tests

```php
<?php

use Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        // Setup commun pour browser tests
    })
    ->in('Browser');
```

### Chromium headless requis

Pest Browser nécessite Chromium installé. KLASSCI utilise déjà `spatie/browsershot` qui requiert Chromium. Vérifier :

```bash
# Windows
where chrome.exe
# Doit retourner un path

# Si pas trouvé, Browsershot le télécharge automatiquement via npx puppeteer
```

## Exemple de test Browser

```php
// tests/Browser/EmploiTemps/BulkEditFlowTest.php
<?php

use App\Models\User;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;

test('user can navigate to bulk-edit and see LMD matieres', function () {
    $user = User::factory()->withRole('superAdmin')->create();
    $classe = ESBTPClasse::factory()->lmd()->withParcours()->create();
    $emploiTemps = ESBTPEmploiTemps::factory()->for($classe)->create();

    $page = visit("/esbtp/emploi-temps/bulk-edit?ids={$emploiTemps->id}", actingAs: $user);

    $page->assertSee('LICENCE')
         ->assertDontSee('Planification non configurée')
         ->assertSee('Mathématiques'); // ECUE LMD
});
```

## Exécution

```bash
# Tous les tests browser
php artisan test --testsuite=Browser

# Un test spécifique
php artisan test --filter=BulkEditFlow

# Avec --browser visible (debug)
BROWSER_HEADLESS=false php artisan test --testsuite=Browser
```

## CI/CD

Pour intégrer dans GitHub Actions ou autre :

```yaml
- name: Setup Chromium
  run: |
    sudo apt-get update
    sudo apt-get install -y chromium-browser

- name: Run Browser tests
  run: php artisan test --testsuite=Browser
  env:
    BROWSER_HEADLESS: true
```

## Status PR0

- [ ] À installer par Marcel ou en PR14
- [ ] Tests Browser dossier créé en PR14
- [ ] CI intégration en PR14

## Voir aussi

- [Pest Browser docs](https://pestphp.com/docs/browser-testing)
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md` (PR14)
- Skill : `klassci-test-bts-lmd-matrix`
