<?php

namespace Tests\Feature\Bulletin;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RangAnnuelPariteTroncCommunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(['id' => 1]);
        SettingsHelper::setOrCreate('bulletin_semester1_weight', '1');
        SettingsHelper::setOrCreate('bulletin_semester2_weight', '1');
        SettingsHelper::setOrCreate('bulletin_show_attendance_note', '0');
    }

    public function test_mode_s1_s2_classe_sur_la_moyenne_annuelle_complete(): void
    {
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '1');
        [$annee, $spec, $a, $b] = $this->scenario();

        $service = app(BulletinService::class);
        $this->assertSame(1, $service->calculerRangAnnuel($a->id, $spec->id, $annee->id, 14.0));
        $this->assertSame(2, $service->calculerRangAnnuel($b->id, $spec->id, $annee->id, 13.0));
    }

    public function test_mode_s2_seul_classe_la_specialite_sur_s2(): void
    {
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '0');
        [$annee, $spec, $a, $b] = $this->scenario();

        $service = app(BulletinService::class);
        $this->assertSame(2, $service->calculerRangAnnuel($a->id, $spec->id, $annee->id, 10.0));
        $this->assertSame(1, $service->calculerRangAnnuel($b->id, $spec->id, $annee->id, 20.0));
    }

    public function test_une_seule_cohorte_annuelle_et_le_rang_du_bulletin_s1_utilise_la_specialite(): void
    {
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '1');
        SettingsHelper::setOrCreate('tronc_commun_rang_annuel_classe', 'specialisation');
        [$annee, $spec, $a, $b] = $this->scenario();

        $tcId = app(\App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver::class)->resolve(
            $a->id, $spec->id, $annee->id
        )['semestre1_classe_id'];

        $cohorte = app(\App\Domain\BtsTroncCommun\BtsClassCohortCounter::class);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $cohorte->etudiantIdsPourPeriode($tcId, $annee->id, 'semestre1'));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $cohorte->etudiantIdsPourPeriode($spec->id, $annee->id, 'annuel'));
        $this->assertSame([], $cohorte->etudiantIdsPourPeriode($tcId, $annee->id, 'annuel'));

        $bulletins = app(BulletinService::class);
        // Un bulletin S1 garde son en-tête TC, mais jamais un rang annuel TC
        // si le conseil classe sur la spécialité.
        $this->assertSame(1, $bulletins->calculerRangAnnuel($a->id, $tcId, $annee->id, 14.0));
        $this->assertSame(2, $bulletins->calculerRangAnnuel($b->id, $tcId, $annee->id, 13.0));

        $ids = $bulletins->buildEtudiantsQuery($tcId, $annee->id, false, 'annuel')->pluck('id')->all();
        $this->assertSame([], $ids);
        $this->assertEqualsCanonicalizing([$a->id, $b->id],
            $bulletins->buildEtudiantsQuery($spec->id, $annee->id, false, 'annuel')->pluck('id')->all());
    }

    public function test_option_classe_origine_ne_produit_pas_deux_rangs_annuels(): void
    {
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '1');
        SettingsHelper::setOrCreate('tronc_commun_rang_annuel_classe', 'origine');
        [$annee, $spec, $a, $b] = $this->scenario();
        $tcId = app(\App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver::class)->resolve(
            $a->id, $spec->id, $annee->id
        )['semestre1_classe_id'];

        $cohorte = app(\App\Domain\BtsTroncCommun\BtsClassCohortCounter::class);
        $this->assertSame([], $cohorte->etudiantIdsPourPeriode($spec->id, $annee->id, 'annuel'));
        $this->assertEqualsCanonicalizing([$a->id, $b->id],
            $cohorte->etudiantIdsPourPeriode($tcId, $annee->id, 'annuel'));
        $this->assertSame(1, app(BulletinService::class)->calculerRangAnnuel($a->id, $spec->id, $annee->id, 14.0));
        $this->assertSame(2, app(BulletinService::class)->calculerRangAnnuel($b->id, $spec->id, $annee->id, 13.0));
    }

    public function test_bulletin_s1_depuis_classe_origine_et_bulletin_s2_prioritaire_dans_la_liste_annuelle(): void
    {
        [$annee, $spec, $a] = $this->scenario();
        $tcId = app(\App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver::class)->resolve(
            $a->id, $spec->id, $annee->id
        )['semestre1_classe_id'];
        $s1 = ESBTPBulletin::where('etudiant_id', $a->id)
            ->where('classe_id', $tcId)->where('periode', 'semestre1')->firstOrFail();
        $s2 = ESBTPBulletin::where('etudiant_id', $a->id)
            ->where('classe_id', $spec->id)->where('periode', 'semestre2')->firstOrFail();

        $service = app(BulletinService::class);
        $liens = [];
        $service->getStudentBulletins(collect([$a]), $tcId, $annee->id, '1', $liens);
        $this->assertSame($s1->id, $liens[$a->id] ?? null);

        $liens = [];
        $service->getStudentBulletins(collect([$a]), $spec->id, $annee->id, '2', $liens);
        $this->assertSame($s2->id, $liens[$a->id] ?? null);

        $liens = [];
        $service->getStudentBulletins(collect([$a]), $spec->id, $annee->id, null, $liens);
        $this->assertSame($s2->id, $liens[$a->id] ?? null);
    }

    public function test_moyenne_annuelle_absente_ne_donne_pas_de_rang(): void
    {
        [$annee, $spec, $a] = $this->scenario();
        $this->assertNull(app(BulletinService::class)->calculerRangAnnuel($a->id, $spec->id, $annee->id, null));
    }

    /** @return array{ESBTPAnneeUniversitaire, ESBTPClasse, ESBTPEtudiant, ESBTPEtudiant} */
    private function scenario(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tc = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $spec = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);

        $a = $this->etudiantOriente($annee, $niveau, $specFiliere, $tcFiliere, $tc, $spec);
        $b = $this->etudiantOriente($annee, $niveau, $specFiliere, $tcFiliere, $tc, $spec);

        $this->bulletin($a, $tc, $annee, 'semestre1', 18);
        $this->bulletin($a, $spec, $annee, 'semestre2', 10);
        $this->bulletin($b, $tc, $annee, 'semestre1', 6);
        $this->bulletin($b, $spec, $annee, 'semestre2', 20);

        return [$annee, $spec, $a, $b];
    }

    private function etudiantOriente($annee, $niveau, $specFiliere, $tcFiliere, $tc, $spec): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $spec->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tc->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $spec->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return $etudiant;
    }

    private function bulletin(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
        float $moyenne
    ): void {
        ESBTPBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => $periode,
            'moyenne_generale' => $moyenne,
            'note_assiduite' => 0,
        ]);
    }
}
