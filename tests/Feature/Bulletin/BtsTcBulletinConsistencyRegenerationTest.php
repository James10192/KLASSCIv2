<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\Setting;
use App\Services\ESBTP\BulletinConsistencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

class BtsTcBulletinConsistencyRegenerationTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts, SeedsConfiguredBulletin;

    public function test_regeneration_aligne_immediatement_officiel_et_courant_en_mode_blocs(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        Setting::updateOrCreate(['key' => 'bulletin_moyenne_mode'], $this->setting('blocs'));
        Setting::updateOrCreate(['key' => 'bulletin_bloc_general_coef'], $this->setting('1'));
        Setting::updateOrCreate(['key' => 'bulletin_bloc_professionnel_coef'], $this->setting('1'));
        Setting::updateOrCreate(['key' => 'bulletin_show_attendance_note'], $this->setting('0'));

        $generale = $this->matiereAvecType('general', 1);
        $technique = $this->matiereAvecType('technique', 4);

        $evalGenerale = $this->evaluationDe($generale);
        $evalTechnique = $this->evaluationDe($technique);
        $this->noter($etudiant, $evalGenerale, 10);
        $this->noter($etudiant, $evalTechnique, 20);

        $this->seedConfiguredBulletin(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'semestre1',
            [$generale->id],
            [$technique->id]
        );

        $snapshot = app(BulletinConsistencyService::class)->regenerateOfficialBulletin(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'semestre1'
        );

        self::assertSame('aligned', $snapshot['status']);
        self::assertFalse($snapshot['has_divergence']);
        self::assertSame(15.0, (float) $snapshot['official_effective_total']);
        self::assertSame(15.0, (float) $snapshot['current_recomputed_effective_total']);
        self::assertSame(0.0, (float) $snapshot['difference_value']);
    }

    private function matiereAvecType(string $type, int $coefficient)
    {
        $matiere = \App\Models\ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'config' => ['type' => $type, 'coefficient' => $coefficient],
        ]);
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => $coefficient,
        ]);

        return $matiere;
    }

    private function setting(string $value): array
    {
        return [
            'value' => $value,
            'type' => 'string',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'is_active' => true,
        ];
    }
}
