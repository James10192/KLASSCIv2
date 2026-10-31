<?php

namespace Tests\Feature\Notes;

use App\Domain\Notes\FormatFeuilleViergeA4;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormatFeuilleViergeA4Test extends TestCase
{
    public function test_formats_par_defaut_et_paysage_sept_colonnes(): void
    {
        $this->assertSame(['portrait', 1], FormatFeuilleViergeA4::depuisRequete(Request::create('/notes/pdf', 'GET')));
        $this->assertSame(['portrait', 4], FormatFeuilleViergeA4::depuisRequete(Request::create('/notes/pdf', 'GET', ['orientation' => 'portrait', 'colonnes' => '4'])));
        $this->assertSame(['landscape', 7], FormatFeuilleViergeA4::depuisRequete(Request::create('/notes/pdf', 'GET', ['orientation' => 'landscape', 'colonnes' => '7'])));
        $this->assertSame(['landscape', 8], FormatFeuilleViergeA4::depuisRequete(Request::create('/notes/pdf', 'GET', ['orientation' => 'landscape', 'colonnes' => '8'])));
    }

    public function test_depassement_refuse_sans_generer_un_pdf_trop_serre(): void
    {
        $this->expectException(ValidationException::class);
        FormatFeuilleViergeA4::depuisRequete(Request::create('/notes/pdf', 'GET', [
            'orientation' => 'portrait', 'colonnes' => '7',
        ]));
    }

    public function test_parametres_de_format_non_valides_refuses(): void
    {
        foreach ([
            ['orientation' => 'a3', 'colonnes' => 1],
            ['orientation' => 'landscape', 'colonnes' => 9],
            ['orientation' => 'portrait', 'colonnes' => '2.5'],
            ['orientation' => 'portrait', 'colonnes' => 0],
        ] as $options) {
            try {
                FormatFeuilleViergeA4::depuisRequete(Request::create('/notes/pdf', 'GET', $options));
                $this->fail('Le format invalide aurait dû être refusé : '.json_encode($options));
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_liens_apercu_et_telechargement_partagent_les_options_et_securite(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPNoteController.php'));
        $view = file_get_contents(resource_path('views/esbtp/notes/index.blade.php'));
        $pdf = file_get_contents(resource_path('views/esbtp/notes/saisie-rapide-pdf.blade.php'));

        $this->assertSame(2, substr_count($controller, 'FormatFeuilleViergeA4::depuisRequete($request)'));
        $this->assertStringContainsString("pdfUrl.searchParams.set('colonnes'", $view);
        $this->assertStringContainsString("pdfUrl.searchParams.set('orientation'", $view);
        $this->assertStringContainsString('nm-blank-config-fields { display: flex;', $view);
        $this->assertStringContainsString('nm-blank-config { display: flex;', $view);
        $this->assertStringContainsString('nm-blank-config-field--columns', $view);
        $this->assertStringContainsString('max-width: 540px', $view);
        $this->assertStringNotContainsString('nm-blank-config-sub', $view);
        $this->assertStringContainsString('nmBlankOrientation', $view);
        $this->assertStringContainsString('nmBlankColumns', $view);
        $this->assertStringContainsString("pdf->setPaper('A4', \$orientationFeuille)", $controller);
        $this->assertStringContainsString('notes.feuille_vierge.export', $view);
        $this->assertStringContainsString('nm-notes-table--multi', $pdf);
        $this->assertStringContainsString('$plusieursNotes', $pdf);
        $this->assertStringContainsString('page-break-inside: avoid', $pdf);
    }
}
