<?php

namespace Tests\Unit\Services;

use App\Domain\Exports\ExportableReport;
use App\Services\ExportRenderer;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Tests\TestCase;

/**
 * Le téléchargement PDF met le fichier en cache. Sur les instances en
 * `CACHE_DRIVER=database`, la valeur va dans une colonne texte que MySQL strict
 * refuse de remplir avec des octets binaires : tous les téléchargements PDF
 * répondaient 500 (presentation, 19/11/2026), l'aperçu non.
 *
 * Le magasin ci-dessous rejoue cette contrainte : il refuse toute valeur qui
 * n'est pas du texte UTF-8 valide.
 */
class ExportRendererCacheBinaireTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        View::addLocation(__DIR__.'/vues');
        Cache::extend('texte_strict', fn () => Cache::repository(new class extends ArrayStore {
            public function put($key, $value, $seconds)
            {
                if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
                    throw new \RuntimeException('Incorrect string value');
                }

                return parent::put($key, $value, $seconds);
            }
        }));
        config(['cache.stores.texte_strict' => ['driver' => 'texte_strict'], 'cache.default' => 'texte_strict']);
        Cache::forgetDriver('texte_strict');
    }

    public function test_le_pdf_telecharge_passe_par_un_cache_texte_et_reste_intact(): void
    {
        $renderer = app(ExportRenderer::class);

        $premier = $renderer->pdfDownload($this->rapport())->getContent();
        $second = $renderer->pdfDownload($this->rapport())->getContent();

        self::assertStringStartsWith('%PDF', $premier);
        self::assertSame($premier, $second, 'Le second appel doit relire le cache sans altérer le fichier.');
    }

    public function test_une_entree_illisible_ne_donne_jamais_un_pdf_vide(): void
    {
        Cache::put('exports:pdf:b64:'.$this->rapport()->cacheKey(), '%%% pas du base64 %%%', 300);

        $pdf = app(ExportRenderer::class)->pdfDownload($this->rapport())->getContent();

        self::assertStringStartsWith('%PDF', $pdf);
    }

    public function test_une_panne_du_cache_ne_fait_pas_tomber_le_telechargement(): void
    {
        Cache::shouldReceive('remember')->andThrow(new \RuntimeException('table cache absente'));

        $pdf = app(ExportRenderer::class)->pdfDownload($this->rapport())->getContent();

        self::assertStringStartsWith('%PDF', $pdf);
    }

    private function rapport(): ExportableReport
    {
        return new class extends ExportableReport {
            public function title(): string { return 'Test'; }
            public function pdfView(): string { return 'export-binaire'; }
            public function viewData(): array { return []; }
            public function excelExport(): FromCollection
            {
                return new class implements FromCollection {
                    public function collection(): Collection { return collect(); }
                };
            }
        };
    }
}
