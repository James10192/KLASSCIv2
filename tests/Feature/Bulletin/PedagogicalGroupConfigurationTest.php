<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Services\BulletinInlineConfigurationService;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class PedagogicalGroupConfigurationTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts;

    public function test_un_coefficient_par_classe_ne_modifie_pas_une_autre_classe_de_meme_filiere(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $this->evaluationDe($matiere);
        $autre = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        app(BulletinInlineConfigurationService::class)->save([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'matiere_type' => [$matiere->id => 'general'],
            'coefficients' => [$matiere->id => 4],
            'professeurs' => [$matiere->id => 'Professeur référent'],
        ], 1);

        $bulletins = app(BulletinService::class);
        self::assertSame(4.0, $bulletins->getCoefficientForCombination($matiere->id, $this->classe->id, $this->annee->id, 'semestre1'));
        self::assertSame(2.0, $bulletins->getCoefficientForCombination($matiere->id, $autre->id, $this->annee->id, 'semestre1'));
        self::assertSame(2.0, (float) ESBTPMatiereCoefficient::where('matiere_id', $matiere->id)->value('coefficient'));
        self::assertSame(4.0, (float) ESBTPConfigMatiere::where('classe_id', $this->classe->id)->firstOrFail()->config['coefficient']);
    }

    public function test_une_matiere_etrangere_au_groupe_ne_peut_pas_etre_configuree(): void
    {
        $this->monterLaClasse();
        $etrangere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        $this->expectException(ValidationException::class);
        app(BulletinInlineConfigurationService::class)->save([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'matiere_type' => [$etrangere->id => 'technique'],
        ], 1);
    }

    public function test_un_enregistrement_sans_surcharge_ne_fige_pas_le_coefficient_herite(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $this->evaluationDe($matiere);
        $ligne = ESBTPConfigMatiere::firstOrFail();
        $ligne->update(['config' => ['type' => 'general']]);

        $service = app(BulletinInlineConfigurationService::class);
        $avant = $service->data($this->classe->id, $this->annee->id, 'semestre1')['matieres']->firstWhere('id', $matiere->id);
        self::assertSame('filiere', $avant['coefficient_source']);
        self::assertSame(2.0, $avant['coefficient']);

        $service->save([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'matiere_type' => [$matiere->id => 'general'],
            'coefficients' => [$matiere->id => ''],
        ], 1);

        self::assertArrayNotHasKey('coefficient', ESBTPConfigMatiere::firstOrFail()->config);
        self::assertSame('filiere', $service->data($this->classe->id, $this->annee->id, 'semestre1')['matieres']->firstWhere('id', $matiere->id)['coefficient_source']);
    }

    public function test_semestres_independants_et_json_historique_preserve(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);
        $ligne = ESBTPConfigMatiere::firstOrFail();
        $ligne->update(['config' => json_encode(['type' => 'general', 'coefficient' => 2, 'memo' => 'ancien'])]);

        $service = app(BulletinInlineConfigurationService::class);
        $service->save([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'matiere_type' => [$matiere->id => 'technique'],
            'coefficients' => [$matiere->id => 4],
        ], 1);
        // Historique S2 pour valider cette matière même si le pivot maquette
        // n'est pas monté par la fabrique de test.
        $evaluation->update(['periode' => 'semestre2']);
        $service->save([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'matiere_type' => [$matiere->id => 'general'],
            'coefficients' => [$matiere->id => 5],
        ], 1);

        $s1 = ESBTPConfigMatiere::where('periode', 'semestre1')->firstOrFail();
        $s2 = ESBTPConfigMatiere::where('periode', 'semestre2')->firstOrFail();
        self::assertSame('ancien', $s1->config['memo']);
        self::assertSame(4.0, (float) $s1->config['coefficient']);
        self::assertSame(5.0, (float) $s2->config['coefficient']);
        self::assertSame(4.0, app(BulletinService::class)->getCoefficientForCombination($matiere->id, $this->classe->id, $this->annee->id, 'semestre1'));
        self::assertSame(5.0, app(BulletinService::class)->getCoefficientForCombination($matiere->id, $this->classe->id, $this->annee->id, 'semestre2'));
    }

    public function test_une_configuration_archivee_est_reactivee_lors_de_la_saisie(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $this->evaluationDe($matiere);
        ESBTPConfigMatiere::firstOrFail()->delete();

        app(BulletinInlineConfigurationService::class)->save([
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'matiere_type' => [$matiere->id => 'general'],
            'coefficients' => [$matiere->id => 3],
        ], 1);

        self::assertSame(1, ESBTPConfigMatiere::query()->count());
        self::assertSame(3.0, app(BulletinService::class)->getCoefficientForCombination($matiere->id, $this->classe->id, $this->annee->id, 'semestre1'));
    }
}
