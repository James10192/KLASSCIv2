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

## Composant `<x-pdf-document>` étendu (Phase 9 — mai 2026)

Le composant accepte 2 nouveaux props avancés :

```blade
<x-pdf-document
    title="Bulletin"
    :overrides="$pdfOverrides ?? []"        {{-- merge avec SettingsHelper::getPdfSettings() --}}
    signature-block="director">             {{-- null|'director'|'secretary'|'both' --}}
    ...
</x-pdf-document>
```

**`overrides`** : utilisé par la route preview `/esbtp/settings/pdf-preview` pour appliquer les paramètres en cours d'édition sans persister. Le composant fait `array_merge(SettingsHelper::getPdfSettings(), $overrides)`.

**`signatureBlock`** : ajoute une zone signature OFFICIELLE (pour bulletins/certificats). Reserve `pdf_signature_height + 30px`, affiche image base64 si `pdf_signature_director` configuré, sinon zone vide. Pattern document officiel : ligne fine + nom signataire EN BAS uniquement (pas de "Espace réservé pour signature manuscrite" bavard — un vrai bulletin n'écrit jamais ça).

## 6 settings PDF avancés (Phase 9 — registry)

Tous lus via `SettingsHelper::getPdfSettings()` :
- `pdf_logo_size` (int 20-120 px) — hauteur max du logo dans le header
- `pdf_footer_custom_text` (string nullable) — texte footer override
- `pdf_show_pagination` (bool) — affiche "Page X / Y" dans le footer
- `pdf_show_director_signature` (bool) — toggle "Directeur : Nom" dans le footer
- `pdf_show_generator_name` (bool default true) — toggle "Généré par {auth->user->name}" dans header/footer
- `pdf_signature_height` (int 40-200 px) — hauteur max images signature
- `pdf_watermark_opacity` (float 0.02-0.30) — opacité du filigrane
- `pdf_watermark_rotation` (int -90/+90) — rotation du filigrane

Permission requise pour modifier : `settings.pdf.manage` (registry).

## Pattern PRÉVIEW universel (Phase 9.5 — mai 2026)

**Chaque export PDF doit avoir un bouton "Aperçu PDF"** qui ouvre le PDF inline dans une nouvelle tab. Pas seulement les documents officiels — aussi les listings (paiements, classes, étudiants).

### Refactor SOLID controller (extraction `buildXxxPdf`)

```php
// AVANT (download seulement)
public function exportPdf(Request $request) {
    $pdf = Pdf::loadView('foo', [...]);
    return $pdf->download('foo.pdf');
}

// APRÈS (download + preview, DRY)
public function exportPdf(Request $request) {
    [$pdf, $filename] = $this->buildExportPdf($request);
    return $pdf->download($filename);
}
public function exportPdfPreview(Request $request) {
    [$pdf, $filename] = $this->buildExportPdf($request);
    return new Response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
}
private function buildExportPdf(Request $request): array {
    // Logique de construction du PDF (DRY entre download + preview)
    return [Pdf::loadView(...), 'filename.pdf'];
}
```

### Routes (toujours en couple)

```php
Route::get('/foo/export-pdf', [FooController::class, 'exportPdf'])->name('foo.export-pdf');
Route::get('/foo/export-pdf/preview', [FooController::class, 'exportPdfPreview'])
    ->middleware('throttle:60,1')  // preview = lecture, throttle généreux
    ->name('foo.export-pdf-preview');
```

### Composant UI

- `<x-export-modal>` : pour LISTINGS avec dropdown 4 actions (Aperçu / PDF / Excel / Email)
- `<x-pdf-actions>` : pour DOCUMENTS INDIVIDUELS (bulletin, certificat) — 2 boutons inline (Aperçu + Télécharger)

## Bug accents en majuscules (CRITIQUE)

**JAMAIS** utiliser `strtoupper()`, `strtolower()`, `ucfirst()`, `substr()` sur du texte français dans les templates PDF — bug PHP UTF-8 historique.

```php
// ❌ INTERDIT — affiche "TABLEAU DéTAILLé" au lieu de "TABLEAU DÉTAILLÉ"
strtoupper("Tableau détaillé")

// ✅ OBLIGATOIRE — gère UTF-8
mb_strtoupper("Tableau détaillé", 'UTF-8')
mb_strtolower($x, 'UTF-8')
mb_substr($x, 0, 1, 'UTF-8')
```

Aussi : éviter `text-transform: uppercase` en CSS pour DomPDF — préférer écrire le texte directement en majuscules dans le PHP via `mb_strtoupper()`.

## Form spoofing PUT + `formaction` (trap connu)

Si un form parent utilise `@method('PUT')` (Laravel form spoofing), un bouton avec `formaction="/preview"` envoie `POST` mais le `_method=PUT` reste dans le body → Laravel route en PUT, pas POST.

**Solution** : `Route::match(['POST', 'PUT'], '/preview', ...)` pour accepter les deux. Vu sur `/esbtp/settings/pdf-preview` (mai 2026).

## Settings idempotence (anti seed bugs)

Dans toute méthode `update()` qui boucle sur des settings, **comparer value submitted vs value en DB AVANT de valider**. Si identique → skip silencieusement. Évite les seeds historiques pourris (ex: setting marqué `is_required=1` mais avec value vide en DB qui fail à chaque save).

```php
$currentValue = (string) ($setting->value ?? '');
$newValue = $value === null ? '' : (string) $value;
if ($currentValue === $newValue) {
    continue;  // pas de modification = pas de validation
}
```

## Permissions adaptatives sur exports paiements

Pattern à reproduire pour tout export sensible avec `view` vs `view_own` :

```php
$canViewAll = $user && $user->can('paiements.view');
$showCreatorColumn = $canViewAll;
$creatorHeader = null;
if (!$canViewAll && $user && $user->can('paiements.view_own')) {
    // Afficher rôle français + nom dans le header (identifie l'auteur)
    $roleName = optional($user->roles->first())->name;
    $roleLabels = ['caissier' => 'Caissier', 'comptable' => 'Comptable', ...];
    $displayRole = $roleLabels[$roleName] ?? ucfirst($roleName);
    $creatorHeader = $displayRole . ' : ' . $user->name;
}
```

Eager-load avec `->loadMissing('createdBy:id,name')` pour éviter N+1 sur la nouvelle colonne.

## Anti-patterns à BLOQUER en review

1. ❌ `Pdf::loadView('mytemplate', ...)` direct dans un controller — utiliser ExportRenderer ou pattern `buildXxxPdf` extrait
2. ❌ Copier-coller le header `pdf-header` dans un nouveau template — utiliser `<x-pdf-document>`
3. ❌ Excel avec téléphone sans `WithColumnFormatting('@')` — perd le `+`
4. ❌ Export sans aperçu (download direct) — toujours offrir l'aperçu via `<x-pdf-actions>` ou `<x-export-modal>`
5. ❌ Pas de garde-fou volume — DomPDF crash sur 5000+ lignes
6. ❌ Hardcoder colors dans le template PDF — utiliser `SettingsHelper::getPdfSettings()`
7. ❌ Pas de throttle sur routes export — abus possible (preview: 60/min, download: 10/min)
8. ❌ Filtres ignorés à l'export (export TOUT au lieu de la vue filtrée) — bug
9. ❌ `strtoupper()` sur texte français → bug accents UTF-8 — toujours `mb_strtoupper(..., 'UTF-8')`
10. ❌ Bouton signature avec label "SIGNATURE & CACHET" + texte "Espace réservé pour signature manuscrite et cachet officiel" — un vrai document officiel n'écrit JAMAIS ça, juste zone vide + nom du signataire EN BAS
11. ❌ Hardcoder "Généré par X" sans respecter `pdf_show_generator_name` toggle — pattern `@if(($pdfCfg['show_generator_name'] ?? true) && auth()->check()) par {{ auth()->user()->name }} @endif`

## Migration des templates legacy

Les templates legacy (`liste-complete-pdf.blade.php`, `bulletin-pdf.blade.php`, etc.) ne sont **pas obligés** de migrer immédiatement vers `<x-pdf-document>`. Mais TOUT NOUVEAU template DOIT l'utiliser. Migration progressive au fil des touches.

## Voir aussi

- Mémoire projet : `analytics-superalgorithm-spec.md` — contexte exports analytics
- Composants : `resources/views/components/pdf-document.blade.php`
- Service : `app/Services/ExportRenderer.php`
- Contrat : `app/Domain/Exports/ExportableReport.php`
- Helper téléphone : `app/Domain/Notifications/PhoneFormatter.php`
- Maatwebsite/Excel : `composer.json` `^3.1`
