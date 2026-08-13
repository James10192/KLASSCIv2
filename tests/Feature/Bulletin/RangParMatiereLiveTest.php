<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class RangParMatiereLiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, float>  $notesParEtudiant
     * @return array{0: array<string, ESBTPEtudiant>, 1: ESBTPMatiere, 2: ESBTPClasse, 3: ESBTPAnneeUniversitaire}
     */
    private function seedClasseAvecNotes(array $notesParEtudiant): array
    {
        $user = User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $classe = ESBTPClasse::factory()->create([
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create();

        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'coefficient' => 1,
            'bareme' => 20,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $etudiants = [];
        foreach ($notesParEtudiant as $key => $valeur) {
            $etudiant = ESBTPEtudiant::factory()->create();
            $etudiants[$key] = $etudiant;
            ESBTPInscription::factory()->create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $annee->id,
                'status' => 'active',
            ]);
            // Création directe (les défauts de la factory Note ciblent un schéma obsolète).
            ESBTPNote::create([
                'evaluation_id' => $evaluation->id,
                'etudiant_id' => $etudiant->id,
                'classe_id' => $classe->id,
                'matiere_id' => $matiere->id,
                'note' => $valeur,
                'semestre' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        return [$etudiants, $matiere, $classe, $annee];
    }

    private function rangLive(ESBTPMatiere $matiere, ESBTPEtudiant $etudiant, ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee): string
    {
        $service = app(BulletinService::class);
        $method = new ReflectionMethod($service, 'calculerRangsParMatiereLive');
        $method->setAccessible(true);
        $rangs = $method->invoke($service, [$matiere->id], $etudiant->id, $classe->id, $annee->id, 'semestre1');

        return $rangs[$matiere->id] ?? 'ABSENT';
    }

    public function test_rang_par_matiere_calcule_en_live_quand_esbtp_resultats_vide(): void
    {
        // A=18, B=12, C=15 → rang A=1, C=2, B=3. Aucune ligne esbtp_resultats → repli live.
        [$etu, $matiere, $classe, $annee] = $this->seedClasseAvecNotes(['A' => 18.0, 'B' => 12.0, 'C' => 15.0]);

        $this->assertSame('1', $this->rangLive($matiere, $etu['A'], $classe, $annee));
        $this->assertSame('2', $this->rangLive($matiere, $etu['C'], $classe, $annee));
        $this->assertSame('3', $this->rangLive($matiere, $etu['B'], $classe, $annee));
    }

    public function test_egalite_partage_le_meme_rang(): void
    {
        // A=16, B=16 (ex aequo rang 1), C=10 (rang 3).
        [$etu, $matiere, $classe, $annee] = $this->seedClasseAvecNotes(['A' => 16.0, 'B' => 16.0, 'C' => 10.0]);

        $this->assertSame('1', $this->rangLive($matiere, $etu['A'], $classe, $annee));
        $this->assertSame('1', $this->rangLive($matiere, $etu['B'], $classe, $annee));
        $this->assertSame('3', $this->rangLive($matiere, $etu['C'], $classe, $annee));
    }
}
