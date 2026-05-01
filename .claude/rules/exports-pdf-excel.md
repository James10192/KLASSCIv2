# Rule: Exports PDF + Excel — Pattern unifié KLASSCI

## Quand s'active

À chaque fois qu'une page liste/dashboard/rapport doit pouvoir être exportée en PDF ou Excel par l'utilisateur (comptable, secrétaire, admin), ou quand on touche un fichier qui ressemble à un export :
- Controller avec méthode `export*()` / `download*()` / `pdf*()`
- Vue Blade dans `resources/views/**/pdf/**` ou `resources/views/**/exports/**`
- Classe dans `app/Exports/`
- Service `*ExportService` ou `*Export.php`

## Architecture imposée

### 1. Composant Blade `<x-pdf-document>` OBLIGATOIRE

**JAMAIS recopier** un header/footer PDF dans un nouveau template. Utiliser le composant unifié :

```blade
<x-pdf-document
    title="Recouvrement quotidien"
    subtitle="Liste priorisée des étudiants à relancer"
    :filters="['Filière' => 'BTS Compta', 'Niveau de risque' => 'Haut']"
    orientation="landscape">
    
    <table class="report-table">
        ...
    </table>
</x-pdf-document>
```

Le composant fait automatiquement :
- Header logo + nom école + adresse + téléphone (via `SettingsHelper::getSchoolInfo()`)
- Theme colors via `SettingsHelper::getPdfSettings()` (header_bg, primary, secondary, accent, text)
- Marges configurables via PDF settings (`margin_top/bottom/left/right`)
- Watermark si configuré
- Footer paginé "Page X / Y" + date génération + utilisateur + nom directeur
- Bandeau filtres appliqués via `<x-pdf-filters-recap>` si props `:filters` non vide

### 2. Classe abstraite `ExportableReport` OBLIGATOIRE

Tout export DOIT passer par une concrete class qui hérite de `App\Domain\Exports\ExportableReport`. Pas d'export direct dans un controller.

```php
class RecouvrementReport extends ExportableReport
{
    public function __construct(private array $rows, private array $appliedFilters) {}
    
    public function title(): string { return 'Recouvrement quotidien'; }
    public function pdfView(): string { return 'esbtp.comptabilite.recouvrement.pdf'; }
    public function viewData(): array { return ['rows' => $this->rows]; }
    public function excelExport(): FromCollection { return new RecouvrementExport($this->rows); }
    public function filters(): array { return $this->appliedFilters; }
    public function orientation(): string { return 'landscape'; }
}
```

### 3. Service `ExportRenderer` OBLIGATOIRE

Tout rendu PDF passe par `App\Services\ExportRenderer`. Pas d'appel direct à `Pdf::loadView(...)` dans un controller.

```php
public function previewPdf(Request $request, ExportRenderer $renderer)
{
    $report = new RecouvrementReport($this->buildRows($request), $this->buildFilters($request));
    return $renderer->pdfPreview($report);  // inline new tab
}

public function downloadPdf(Request $request, ExportRenderer $renderer)
{
    $report = new RecouvrementReport(...);
    return $renderer->pdfDownload($report);  // attachment
}

public function downloadExcel(Request $request, ExportRenderer $renderer)
{
    $report = new RecouvrementReport(...);
    return $renderer->excelDownload($report);
}
```

### 4. Composant `<x-export-modal>` pour l'UI OBLIGATOIRE

Toute page exportable utilise le dropdown standard (placé dans le hero) :

```blade
<x-export-modal
    :preview-url="route('esbtp.comptabilite.recouvrement.preview-pdf')"
    :pdf-url="route('esbtp.comptabilite.recouvrement.export-pdf')"
    :excel-url="route('esbtp.comptabilite.recouvrement.export-excel')"
    :email-url="route('esbtp.comptabilite.recouvrement.email-pdf')" />
```

### 5. Filtres reproduits server-side OBLIGATOIRE

Les filtres actifs côté UI (search, niveau, période) doivent être reproduits server-side pour que l'export reflète exactement la vue. Pattern :

```js
// Dans Alpine, exposer les filtres globalement
window.exportFilters = () => ({
    level: this.levelFilter,
    retard_min: this.retardFilter,
    search: this.search,
});
```

`<x-export-modal>` les ajoute en query string aux URLs avant ouverture.

### 6. Téléphone CI au format lisible OBLIGATOIRE

Quand un export contient une colonne téléphone, **toujours** formater via `App\Domain\Notifications\PhoneFormatter::toReadable()` qui retourne `+225 07 07 12 34 56`. Si numéro invalide, afficher `—`.

Pour Excel, déclarer la colonne en `WithColumnFormatting` format `'@'` (text) sinon Excel perd le `+` initial.

### 7. Garde-fous volume OBLIGATOIRES

```php
public const PDF_MAX_ROWS = 1000;
public const EXCEL_MAX_ROWS = 50000;
```

À implémenter dans la classe `*Report` ou via le service. Au-delà, retour 422 avec message clair "Affinez les filtres".

### 8. Throttling routes export OBLIGATOIRE

Toutes les routes d'export sont throttlées pour éviter abus :

```php
Route::get('/preview-pdf', ...)->middleware('throttle:30,1');
Route::get('/export-pdf', ...)->middleware('throttle:10,1');
Route::get('/export-excel', ...)->middleware('throttle:10,1');
```

## Cache PDF (Phase 6)

`ExportRenderer::pdfDownload()` cache le binaire PDF par `$report->cacheKey()` (hash des filtres + data) pour 5 min via `Cache::remember`. Désactivable via setting `analytics.exports.cache_enabled`.

`pdfPreview()` ne cache PAS (preview = doit toujours refléter l'état réel courant).

## Email PDF (Phase 7)

Bouton "Envoyer par email" dans `<x-export-modal>` → POST vers route `email-pdf` → dispatch `ExportableReportMail` queued.

## Export programmé (Phase 8)

Modèle `ExportSchedule` (id, user_id, report_class, frequency, cron, recipients, filters_json, est_actif). Job `SendScheduledExportJob` scheduled hourly check qui dispatch les exports dont `prochaine_execution <= now()`.

## Anti-patterns à BLOQUER en review

1. ❌ `Pdf::loadView('mytemplate', ...)` direct dans un controller — utiliser ExportRenderer
2. ❌ Copier-coller le header `pdf-header` dans un nouveau template — utiliser `<x-pdf-document>`
3. ❌ Excel avec téléphone sans `WithColumnFormatting('@')` — perd le `+`
4. ❌ Export sans aperçu (download direct) — toujours offrir l'aperçu
5. ❌ Pas de garde-fou volume — DomPDF crash sur 5000+ lignes
6. ❌ Hardcoder colors dans le template PDF — utiliser `SettingsHelper::getPdfSettings()`
7. ❌ Pas de throttle sur routes export — abus possible
8. ❌ Filtres ignorés à l'export (export TOUT au lieu de la vue filtrée) — bug

## Migration des templates legacy

Les templates legacy (`liste-complete-pdf.blade.php`, `bulletin-pdf.blade.php`, etc.) ne sont **pas obligés** de migrer immédiatement vers `<x-pdf-document>`. Mais TOUT NOUVEAU template DOIT l'utiliser. Migration progressive au fil des touches.

## Voir aussi

- Mémoire projet : `analytics-superalgorithm-spec.md` — contexte exports analytics
- Composants : `resources/views/components/pdf-document.blade.php`
- Service : `app/Services/ExportRenderer.php`
- Contrat : `app/Domain/Exports/ExportableReport.php`
- Helper téléphone : `app/Domain/Notifications/PhoneFormatter.php`
- Maatwebsite/Excel : `composer.json` `^3.1`
