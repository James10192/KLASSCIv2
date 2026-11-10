<?php

namespace Tests\Feature\Bulletin;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Domain\OfficialDocuments\Services\BulletinArchiveService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityException;
use App\Http\Controllers\ESBTPBulletinController;
use App\Http\Controllers\ESBTPLMDBulletinController;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPLMDBulletin;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Mockery;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * Émettre un bulletin revient à figer des octets immuables, pas à enregistrer
 * un statut booleen pour permettre un re-rendu plus tard.
 */
class ArchivesBulletinsBtsLmdTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts, SeedsConfiguredBulletin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->monterLaClasse();
        $this->actingAs(User::findOrFail(1));
    }

    public function test_bts_publication_archive_l_original_et_la_lecture_resiste_aux_reglages_futurs(): void
    {
        $etudiant = $this->etudiantInscrit();
        $b = $this->seedConfiguredBulletin($etudiant->id, $this->classe->id, $this->annee->id);
        $b->forceFill(['moyenne_generale' => 14.4, 'rang' => 1])->save();

        $this->fauxRendererBts();
        $archives = app(BulletinArchiveService::class);
        $doc = $archives->publier($b, auth()->user());

        self::assertTrue((bool) $b->fresh()->is_published);
        self::assertSame(BulletinArchiveService::TYPE_BTS, $doc->document_type);
        self::assertSame(OfficialDocument::STATUS_VALID, $doc->status);
        self::assertSame(hash('sha256', $archives->octets($doc)), $doc->checksum_sha256);
        self::assertSame(14.4, (float) ($doc->snapshot['bulletin']['moyenne_generale'] ?? 0));
        $original = $archives->octets($doc);

        \App\Helpers\SettingsHelper::setOrCreate('bulletin_moyenne_mode', 'blocs');
        \App\Helpers\SettingsHelper::setOrCreate('bulletin_bloc_professionnel_coef', '2');

        self::assertSame($original, $archives->octets($doc));
        self::assertSame($doc->id, $archives->publier($b->fresh(), auth()->user())->id);
    }

    public function test_lmd_publication_utilise_la_meme_archive_sans_adopter_les_blocs_bts(): void
    {
        $etudiant = $this->etudiantInscrit();
        $this->classe->forceFill(['systeme_academique' => 'LMD'])->save();
        $b = ESBTPLMDBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 1,
            'moyenne_generale' => 12.75,
            'credits_totaux' => 30,
            'credits_capitalises' => 24,
        ]);

        $this->fauxRendererLmd();
        $archives = app(BulletinArchiveService::class);
        $doc = $archives->publier($b, auth()->user());
        self::assertSame(BulletinArchiveService::TYPE_LMD, $doc->document_type);
        self::assertTrue((bool) $b->fresh()->is_published);
        self::assertSame(12.75, (float) $doc->snapshot['bulletin']['moyenne_generale']);
        self::assertArrayHasKey('lmd_cc_weight', $doc->snapshot['reglages']);
        self::assertArrayNotHasKey('bulletin_bloc_professionnel_coef', $doc->snapshot['reglages']);
        self::assertSame(hash('sha256', $archives->octets($doc)), $doc->checksum_sha256);
    }

    public function test_un_bulletin_historique_publie_sans_archive_ne_devient_pas_faussement_original(): void
    {
        $etudiant = $this->etudiantInscrit();
        $b = $this->seedConfiguredBulletin($etudiant->id, $this->classe->id, $this->annee->id);
        $b->forceFill(['moyenne_generale' => 13.0, 'is_published' => true])->save();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('historique');
        app(BulletinArchiveService::class)->publier($b, auth()->user());
    }

    public function test_une_erreur_de_rendu_annule_la_publication_atomiquement(): void
    {
        $etudiant = $this->etudiantInscrit();
        $b = $this->seedConfiguredBulletin($etudiant->id, $this->classe->id, $this->annee->id);
        $b->forceFill(['moyenne_generale' => 11.2])->save();

        $controller = Mockery::mock(ESBTPBulletinController::class);
        $controller->shouldReceive('buildBulletinPdf')->once()->andThrow(new \RuntimeException('Rendu indisponible'));
        app()->instance(ESBTPBulletinController::class, $controller);

        try {
            app(BulletinArchiveService::class)->publier($b, auth()->user());
            self::fail('La publication devait être annulée.');
        } catch (\RuntimeException $e) {
            self::assertSame('Rendu indisponible', $e->getMessage());
        }
        self::assertFalse((bool) $b->fresh()->is_published);
        self::assertSame(0, OfficialDocument::query()->count());
    }

    public function test_une_archive_modifiee_est_refusee_au_telechargement(): void
    {
        $etudiant = $this->etudiantInscrit();
        $b = $this->seedConfiguredBulletin($etudiant->id, $this->classe->id, $this->annee->id);
        $b->forceFill(['moyenne_generale' => 10.0])->save();
        $this->fauxRendererBts();
        $archives = app(BulletinArchiveService::class);
        $doc = $archives->publier($b, auth()->user());

        Storage::disk('local')->put($doc->path, '%PDF-PDF-ALTERE');
        $this->expectException(OfficialDocumentIntegrityException::class);
        $archives->octets($doc);
    }

    private function fauxRendererBts(): void
    {
        $renderer = Mockery::mock(ESBTPBulletinController::class);
        $renderer->shouldReceive('buildBulletinPdf')
            ->once()
            ->andReturn(Pdf::loadHTML('<html><body>Original BTS figé</body></html>'));
        app()->instance(ESBTPBulletinController::class, $renderer);
    }

    private function fauxRendererLmd(): void
    {
        $renderer = Mockery::mock(ESBTPLMDBulletinController::class);
        $renderer->shouldReceive('rendrePdfPourExport')
            ->once()
            ->andReturn(Pdf::loadHTML('<html><body>Original LMD figé</body></html>'));
        app()->instance(ESBTPLMDBulletinController::class, $renderer);
    }
}
