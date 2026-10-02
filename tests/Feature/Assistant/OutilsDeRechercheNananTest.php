<?php

namespace Tests\Feature\Assistant;

use App\Models\ESBTPEtudiant;
use App\Models\User;
use App\Services\Chatbot\Tools\SearchEvaluationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Deux lectures dont Nanan a besoin avant de corriger une note.
 *
 * Capture du 2 octobre 2026 sur presentation : « corrige la note de
 * FESBTP26-0003 en Cartographie » échouait. La recherche d'évaluations levait
 * une erreur à chaque appel (une relation `user` sur un enseignant qui EST
 * déjà un utilisateur), et la recherche d'élève ne reconnaissait pas un
 * matricule tapé à la place du nom.
 */
class OutilsDeRechercheNananTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** @test */
    public function la_recherche_d_evaluations_rend_le_nom_de_l_enseignant(): void
    {
        $this->monterLaClasse();
        $enseignant = User::factory()->create(['name' => 'Prof Kouassi']);
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $evaluation->update(['enseignant_id' => $enseignant->id]);

        $resultat = (new SearchEvaluationsTool())->execute([], User::find(1));

        $this->assertStringContainsString('Prof Kouassi', json_encode($resultat, JSON_UNESCAPED_UNICODE));
    }

    /** @test */
    public function un_matricule_tape_a_la_place_du_nom_retrouve_l_eleve(): void
    {
        $eleve = ESBTPEtudiant::factory()->create(['nom' => 'BAMBA', 'prenoms' => 'Fatoumata', 'matricule' => 'FESBTP26-0003']);
        ESBTPEtudiant::factory()->create(['nom' => 'KONE', 'prenoms' => 'Awa', 'matricule' => 'FESBTP26-0004']);

        $requete = ESBTPEtudiant::query();
        (new SearchEvaluationsTool())->applyFuzzyNameSearch($requete, 'FESBTP26-0003');

        $this->assertSame([$eleve->id], $requete->pluck('id')->all());
    }

    /** @test */
    public function avant_de_corriger_nanan_compare_la_note_trouvee_a_celle_annoncee(): void
    {
        $this->monterLaClasse();

        $prompt = app(\App\Domain\Assistant\Harnais\ConstructeurDePrompt::class)->systeme(User::find(1), null, null);
        $ligne = collect(explode("\n", $prompt))->first(fn ($l) => str_contains($l, 'proposer_correction_notes'));

        // Capture du 2 octobre : « 12,50 au premier semestre » demandé, une
        // seule note de 15 au second en base, et Nanan proposait sans le dire.
        $this->assertNotNull($ligne);
        $this->assertStringContainsString('demande confirmation sans proposer', $ligne);
        $this->assertStringContainsString('cite toujours la note actuelle lue en base', $ligne);
    }

}
