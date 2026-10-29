<?php

namespace Tests\Feature\Examens;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\Examens\FeuilleDeNotesDeLExamen;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPExamenAnonymat;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * L'examen planifié mène à sa feuille de notes, son verrou protège ces notes,
 * et un examen anonyme numérote les copies.
 */
class FeuilleDeNotesDeLExamenTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_feuille_est_creee_une_seule_fois_et_liee_a_l_examen(): void
    {
        [$examen, $user] = $this->examen(anonyme: false);
        $service = app(FeuilleDeNotesDeLExamen::class);

        $evaluation = $service->ouvrir($examen, $user);
        $encore = $service->ouvrir($examen->fresh(), $user);

        $this->assertSame($evaluation->id, $encore->id);
        $this->assertSame($evaluation->id, (int) $examen->fresh()->evaluation_id);
        $this->assertSame('semestre1', $evaluation->periode);
        $this->assertNull($service->numerosPourLaSaisie($evaluation));
    }

    public function test_un_examen_anonyme_numerote_chaque_copie_jusqu_a_la_levee(): void
    {
        [$examen, $user] = $this->examen(anonyme: true);
        $service = app(FeuilleDeNotesDeLExamen::class);

        $evaluation = $service->ouvrir($examen, $user);
        $numeros = $service->numerosPourLaSaisie($evaluation);

        $this->assertCount(3, $numeros);
        $this->assertSame(3, $numeros->unique()->count());
        $this->assertSame(3, ESBTPExamenAnonymat::where('examen_planifie_id', $examen->id)->count());

        $service->leverAnonymat($examen->fresh(), $user);

        $this->assertNull($service->numerosPourLaSaisie($evaluation));
        $this->assertNotNull($examen->fresh()->anonymat_leve_at);
    }

    public function test_un_inscrit_tardif_recoit_le_numero_suivant_le_plus_grand(): void
    {
        [$examen, $user] = $this->examen(anonyme: true);
        $service = app(FeuilleDeNotesDeLExamen::class);
        $evaluation = $service->ouvrir($examen, $user);

        // Un numéro retiré : compter les lignes redonnerait un numéro existant.
        ESBTPExamenAnonymat::where('examen_planifie_id', $examen->id)->orderBy('numero')->first()->delete();
        $classe = \App\Models\ESBTPClasse::find($examen->classe_id);
        ESBTPInscription::factory()->create([
            'classe_id' => $classe->id, 'annee_universitaire_id' => $examen->annee_universitaire_id,
            'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_etude_id,
        ]);

        $numeros = $service->numerosPourLaSaisie($evaluation);

        $this->assertSame($numeros->count(), $numeros->unique()->count());
        $this->assertContains('E'.$examen->id.'-004', $numeros->values()->all());
    }

    public function test_une_evaluation_sous_anonymat_sort_de_la_grille_jusqu_a_la_levee(): void
    {
        [$examen, $user] = $this->examen(anonyme: true);
        $service = app(FeuilleDeNotesDeLExamen::class);
        $evaluation = $service->ouvrir($examen, $user);

        $this->assertSame([$evaluation->id], $service->evaluationsSousAnonymat([$evaluation->id])->all());

        $service->leverAnonymat($examen->fresh(), $user);

        $this->assertSame([], $service->evaluationsSousAnonymat([$evaluation->id])->all());
    }

    public function test_une_note_d_un_examen_verrouille_ne_peut_plus_etre_ecrite(): void
    {
        [$examen, $user] = $this->examen(anonyme: false);
        $evaluation = app(FeuilleDeNotesDeLExamen::class)->ouvrir($examen, $user);
        $examen->fresh()->forceFill(['notes_locked' => true, 'notes_locked_at' => now()])->save();

        $this->expectException(AcademicPilotageException::class);

        ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => DB::table('esbtp_inscriptions')->where('classe_id', $examen->classe_id)->value('etudiant_id'),
            'matiere_id' => $evaluation->matiere_id,
            'classe_id' => $evaluation->classe_id,
            'note' => 12,
            'semestre' => $evaluation->periode,
        ]);
    }

    /** @return array{0: ESBTPExamenPlanifie, 1: User} */
    private function examen(bool $anonyme): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $matiere = ESBTPMatiere::factory()->create();

        ESBTPInscription::factory()->count(3)->create([
            'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
            'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_etude_id,
        ]);

        $examen = ESBTPExamenPlanifie::create([
            'annee_universitaire_id' => $annee->id, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
            'semestre' => 1, 'type_examen' => 'EXAMEN', 'titre' => 'Examen de test',
            'date_debut' => now()->subDay(), 'date_fin' => now()->subDay()->addHours(2), 'duree_minutes' => 120,
            'coefficient' => 2, 'bareme' => 20, 'is_anonymous' => $anonyme, 'status' => 'completed',
            'notes_locked' => false, 'scope_type' => 'classe',
        ]);

        return [$examen, $user];
    }
}
