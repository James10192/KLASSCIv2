<?php

namespace Tests\Feature\Bts;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPResultatMatiere;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPMatiere;
use App\Models\User;
use App\Services\ESBTP\BtsStudentAcademicRecords;
use App\Services\ESBTP\BtsProvisionalTranscript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Performance\Concerns\ConstruitUneClasseNotee;
use Tests\TestCase;

/**
 * Parcours HTTP du 404 ESBTP Abidjan : deux bulletins non publiés,
 * des notes disponibles et un relevé provisoire en lecture seule.
 */
class BtsTranscriptProvisionalHttpTest extends TestCase
{
    use RefreshDatabase;
    use ConstruitUneClasseNotee;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            CheckInstalled::class,
            EnsureInstalled::class,
            PaywallMiddleware::class,
        ]);
        Permission::findOrCreate('bts.releve.view', 'web');
        Permission::findOrCreate('bts.releve.export', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('bts.releve.view');
        $this->actingAs($user);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'name' => '2025-2026',
        ]);
    }

    public function test_deux_bulletins_non_publies_avec_notes_donnent_un_pdf_provisoire_sans_publication(): void
    {
        $cas = $this->classeNotee($this->annee, 1);
        $etudiant = $cas['etudiants']->first();
        foreach (['semestre1', 'semestre2'] as $periode) {
            ESBTPBulletin::factory()->create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $cas['classe']->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => $periode,
                'is_published' => false,
            ]);
        }

        $before = ESBTPNote::query()->where('etudiant_id', $etudiant->id)->count();
        $this->get(route('esbtp.bts.releves.preview', [
            'etudiant' => $etudiant->id, 'annee' => $this->annee->id,
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertSame(2, ESBTPBulletin::query()->where('etudiant_id', $etudiant->id)->count());
        $this->assertSame(0, ESBTPBulletin::query()
            ->where('etudiant_id', $etudiant->id)->where('is_published', true)->count());
        $this->assertSame($before, ESBTPNote::query()->where('etudiant_id', $etudiant->id)->count());
    }

    public function test_bulletin_publie_garde_le_chemin_interne_officiel_sans_recalcul(): void
    {
        $classe = $this->classeBts();
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $classe, $this->annee);
        $bulletin = ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'is_published' => true,
        ]);
        ESBTPResultatMatiere::factory()->create([
            'bulletin_id' => $bulletin->id,
            'matiere_id' => ESBTPMatiere::factory()->create()->id,
            'moyenne' => 12.0,
        ]);

        $this->get(route('esbtp.bts.releves.preview', [
            'etudiant' => $etudiant->id, 'annee' => $this->annee->id,
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertTrue((bool) $bulletin->fresh()->is_published);
    }

    public function test_pas_de_bulletin_publie_ni_de_notes_explique_l_indisponibilite_sans_404(): void
    {
        $classe = $this->classeBts();
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $classe, $this->annee);
        ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'is_published' => false,
        ]);

        $this->get(route('esbtp.bts.releves.preview', [
            'etudiant' => $etudiant->id, 'annee' => $this->annee->id,
        ]))->assertStatus(422)->assertSee('Relevé BTS pas encore disponible');
    }

    public function test_une_annee_sans_inscription_bts_reste_inaccessible(): void
    {
        $classe = $this->classeBts();
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $classe, $this->annee);
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);

        $this->get(route('esbtp.bts.releves.preview', [
            'etudiant' => $etudiant->id, 'annee' => $autreAnnee->id,
        ]))->assertNotFound();
    }

    public function test_projection_tronc_commun_s1_specialite_s2_conserve_les_deux_classes(): void
    {
        $tc = $this->classeBts();
        $specialite = $this->classeBts();
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = $this->inscrire($etudiant, $specialite, $this->annee);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tc->id,
            'filiere_id' => $tc->filiere_id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specialite->id,
            'filiere_id' => $specialite->filiere_id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        $matiere = ESBTPMatiere::factory()->create();
        foreach (['semestre1' => $tc, 'semestre2' => $specialite] as $periode => $classe) {
            $evaluation = ESBTPEvaluation::factory()->create([
                'matiere_id' => $matiere->id,
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => $periode,
                'status' => 'completed',
                'bareme' => 20,
                'coefficient' => 1,
            ]);
            ESBTPNote::factory()->create([
                'evaluation_id' => $evaluation->id,
                'etudiant_id' => $etudiant->id,
                'matiere_id' => $matiere->id,
                'classe_id' => $classe->id,
                'annee_universitaire' => $this->annee->name,
                'note' => 12,
                'valeur' => 12,
                'is_absent' => false,
            ]);
        }
        $sections = app(BtsProvisionalTranscript::class)->sections(
            $etudiant->id,
            $inscription,
            'annuel',
            app(BtsStudentAcademicRecords::class)
        );
        $this->assertSame([$tc->id, $specialite->id], array_column($sections, 'classe_id'));
        $this->assertTrue(BtsProvisionalTranscript::hasResults($sections));
        $this->assertSame(2, ESBTPNote::where('etudiant_id', $etudiant->id)->count());
    }
}
