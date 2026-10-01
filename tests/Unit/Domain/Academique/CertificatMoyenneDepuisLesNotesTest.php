<?php

namespace Tests\Unit\Domain\Academique;

use App\Http\Controllers\ESBTPEtudiantController;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Tests\Unit\Domain\Notes\SchemaDesMoyennes;

/**
 * Une année terminée sans bulletin généré n'a ni bulletin ni moyenne
 * enregistrée : `esbtp_resultats` n'est remplie qu'à la génération. Le
 * certificat de scolarité imprimait alors « — » pour cette année, alors que la
 * fiche de résultats de l'étudiant affichait sa moyenne, calculée sur les notes.
 * Il se rabat désormais sur le même calcul.
 */
class CertificatMoyenneDepuisLesNotesTest extends TestCase
{
    use SchemaDesMoyennes;

    private const ETUDIANT = 100;

    private const CLASSE = 10;

    private const ANNEE = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_classes')->insert(['id' => self::CLASSE, 'name' => '1BTS CG A', 'systeme_academique' => 'BTS']);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();
        Mockery::close();

        parent::tearDown();
    }

    public function test_sans_bulletin_ni_moyenne_enregistree_le_certificat_lit_les_notes(): void
    {
        $this->snapshotRend(12.5);

        $this->assertSame(12.5, $this->moyenneDuCertificat(self::CLASSE));
    }

    public function test_sans_notes_non_plus_la_moyenne_reste_vide(): void
    {
        $this->snapshotRend(null);

        $this->assertNull($this->moyenneDuCertificat(self::CLASSE));
    }

    public function test_l_annee_en_cours_n_imprime_pas_une_moyenne_partielle(): void
    {
        $this->snapshotNeDoitPasEtreAppele();

        $this->assertNull($this->moyenneDuCertificat(self::CLASSE, anneeEnCours: true));
    }

    public function test_une_moyenne_enregistree_prime_sur_les_notes(): void
    {
        $this->snapshotNeDoitPasEtreAppele();
        DB::table('esbtp_matieres')->insert(['id' => 5, 'name' => 'Maths', 'unite_enseignement_id' => null, 'is_active' => 1]);
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE, 'matiere_id' => 5,
            'annee_universitaire_id' => self::ANNEE, 'periode' => 'semestre1', 'moyenne' => 14, 'coefficient' => 1,
        ]);

        $this->assertSame(14.0, $this->moyenneDuCertificat(self::CLASSE));
    }

    public function test_une_classe_lmd_ne_passe_pas_par_le_calcul_bts(): void
    {
        $this->snapshotNeDoitPasEtreAppele();
        DB::table('esbtp_classes')->insert(['id' => 11, 'name' => 'L1 Droit', 'systeme_academique' => 'LMD']);

        $this->assertNull($this->moyenneDuCertificat(11));
    }

    private function moyenneDuCertificat(int $classeId, bool $anneeEnCours = false): ?float
    {
        $inscription = new \App\Models\ESBTPInscription();
        $inscription->forceFill(['classe_id' => $classeId, 'annee_universitaire_id' => self::ANNEE]);
        $inscription->setRelation('anneeUniversitaire', (object) ['id' => self::ANNEE, 'is_current' => $anneeEnCours]);
        $inscription->setRelation('classe', \App\Models\ESBTPClasse::find($classeId));

        $controleur = app(ESBTPEtudiantController::class);
        $methode = new \ReflectionMethod($controleur, 'attachMoyenneCalculee');
        $methode->setAccessible(true);
        $methode->invoke($controleur, collect([$inscription]), self::ETUDIANT);

        return $inscription->moyenne_generale_calculee === null ? null : (float) $inscription->moyenne_generale_calculee;
    }

    private function snapshotRend(?float $total): void
    {
        $snapshot = Mockery::mock(BtsCurrentResultSnapshotService::class);
        $snapshot->shouldReceive('getAnnualSnapshot')
            ->once()
            ->with(self::ETUDIANT, self::CLASSE, self::ANNEE)
            ->andReturn(['effective_total' => $total]);
        $this->app->instance(BtsCurrentResultSnapshotService::class, $snapshot);
    }

    private function snapshotNeDoitPasEtreAppele(): void
    {
        $snapshot = Mockery::mock(BtsCurrentResultSnapshotService::class);
        $snapshot->shouldNotReceive('getAnnualSnapshot');
        $this->app->instance(BtsCurrentResultSnapshotService::class, $snapshot);
    }
}
