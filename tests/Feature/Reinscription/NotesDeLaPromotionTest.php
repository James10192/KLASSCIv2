<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\Reinscription\NotesDeLaPromotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Les lignes legeres de `NotesDeLaPromotion` doivent repondre exactement ce
 * que repondent les modeles `ESBTPNote` que l'analyse de reinscription
 * chargeait : memes notes retenues, meme valeur, meme matiere, meme matiere
 * d'evaluation — y compris sur les cas ou un ecart ferait basculer une
 * decision (matiere effacee, evaluation effacee, note archivee).
 */
class NotesDeLaPromotionTest extends TestCase
{
    use RefreshDatabase;

    private int $classeId;

    public function test_les_lignes_legeres_disent_la_meme_chose_que_les_modeles(): void
    {
        User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2024-2025']);
        $eleves = ESBTPEtudiant::factory()->count(2)->create();

        $matiere = ESBTPMatiere::factory()->create();
        $matiereEffacee = ESBTPMatiere::factory()->create();
        $matiereSupprimee = ESBTPMatiere::factory()->create();
        $classe = ESBTPClasse::factory()->create(['systeme_academique' => 'BTS']);
        $this->classeId = $classe->id;
        $evaluation = ESBTPEvaluation::factory()->create(['matiere_id' => $matiere->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id]);
        $autreEvaluation = ESBTPEvaluation::factory()->create(['matiere_id' => $matiere->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id]);
        $evaluationEffacee = ESBTPEvaluation::factory()->create(['matiere_id' => $matiere->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id]);

        foreach ($eleves as $eleve) {
            $this->note($eleve, '2024-2025', $matiere->id, $evaluation->id, 12.5);
            $this->note($eleve, '2024-2025', $matiereSupprimee->id, $autreEvaluation->id, 9.25); // matiere disparue : celle de l'evaluation
            $this->note($eleve, '2024-2025', $matiereEffacee->id, null, 4.0);         // matiere effacee en douceur
            $this->note($eleve, '2024-2025', $matiere->id, $evaluationEffacee->id, 15.0); // evaluation effacee
            $this->note($eleve, '2023-2024', $matiere->id, null, 18.0);               // autre annee : absente
            $archivee = $this->note($eleve, '2024-2025', $matiere->id, null, 3.0);
            DB::table('esbtp_notes')->where('id', $archivee)->update(['archived_at' => now()]);
        }
        $matiereEffacee->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('esbtp_matieres')->where('id', $matiereSupprimee->id)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $evaluationEffacee->delete();

        $ids = $eleves->pluck('id')->all();
        $legeres = app(NotesDeLaPromotion::class)->pour($ids, '2024-2025');

        foreach ($ids as $id) {
            $modeles = ESBTPNote::where('etudiant_id', $id)
                ->where('annee_universitaire', '2024-2025')
                ->with([
                    'evaluation.matiere' => fn ($q) => $q->withTrashed(),
                    'matiere' => fn ($q) => $q->withTrashed(),
                ])
                ->orderBy('id')
                ->get();

            $this->assertCount(4, $modeles, 'Archivee et autre annee ecartees, les quatre autres retenues.');
            $this->assertSame($this->empreinte($modeles), $this->empreinte($legeres->get($id)));
        }
    }

    /** Ce que la decision lit d'une note, et rien d'autre. */
    private function empreinte($notes): array
    {
        return collect($notes)->map(fn ($note) => [
            'id' => $note->id,
            'matiere_id' => $note->matiere_id,
            'note' => $note->note,
            'matiere' => $note->matiere?->id,
            'evaluation' => $note->evaluation === null ? 'aucune' : ($note->evaluation->matiere?->id ?? 'sans matiere'),
            'cle' => $note->matiere_id ?? $note->evaluation?->matiere?->id,
        ])->values()->all();
    }

    private function note(ESBTPEtudiant $eleve, string $annee, int $matiereId, ?int $evaluationId, float $note): int
    {
        return DB::table('esbtp_notes')->insertGetId([
            'etudiant_id' => $eleve->id,
            'matiere_id' => $matiereId,
            'evaluation_id' => $evaluationId,
            'classe_id' => $this->classeId,
            'annee_universitaire' => $annee,
            'note' => $note,
            'is_absent' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
