<?php

namespace Tests\Feature\Bts;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Un semestre sans aucune note n'a pas de moyenne : il n'a pas 0,00.
 *
 * Sur `/esbtp/resultats/etudiant/{id}`, l'onglet du semestre OUVERT affichait
 * 0,00 pour un eleve sans note sur ce semestre, pendant que la meme page disait
 * « Aucune note exploitable n'est disponible pour cette periode ». Le snapshot
 * rendait bien `null` ; c'est le repli qui le remplacait par la moyenne courante,
 * 0 faute de note. Ouvert sur « Annuel », le meme eleve n'avait pas de pastille :
 * seule la periode affichee etait touchee, et la moyenne annuelle avec elle.
 *
 * Le troisieme cas protege l'erreur inverse : une vraie note de 0 est une valeur.
 */
class MoyenneSemestreSansNoteTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;

    protected function setUp(): void
    {
        parent::setUp();
        CoherenceSystemeAcademique::oublierLesEcartsJournalises();
    }

    public function test_le_semestre_ouvert_sans_note_n_a_pas_de_moyenne(): void
    {
        $etudiant = $this->eleveNoteAuSeulSemestre1();

        $reponse = $this->resultats($etudiant->id, 'semestre2');

        $this->assertNotNull($reponse->viewData('moyenneSemestre1'), 'Temoin : le semestre 1 porte une note.');
        $this->assertNull($reponse->viewData('moyenneSemestre2'), 'Sans note, le semestre 2 n a pas de moyenne (avant : 0,00).');
        $this->assertNull($reponse->viewData('moyenneAnnuelle'), 'Sans semestre 2, pas de moyenne annuelle (avant : calculee avec un faux 0).');
    }

    public function test_ouvert_sur_l_annuel_le_semestre_sans_note_reste_sans_moyenne(): void
    {
        $etudiant = $this->eleveNoteAuSeulSemestre1();

        $reponse = $this->resultats($etudiant->id, 'annuel');

        $this->assertNotNull($reponse->viewData('moyenneSemestre1'), 'Temoin : le semestre 1 porte une note.');
        $this->assertNull($reponse->viewData('moyenneSemestre2'));
    }

    public function test_une_vraie_note_de_zero_reste_une_moyenne(): void
    {
        $etudiant = $this->eleveNoteAuSeulSemestre1();
        $this->noter($etudiant, $this->evaluationDuSemestre2($this->matiere), 0);

        $reponse = $this->resultats($etudiant->id, 'semestre2');
        $courant = $reponse->viewData('bulletinConsistency');

        // Valeurs exactes : un simple « non nul » passait deja avant le correctif
        // (0,13 d'assiduite), et ne verrait pas un `?:` qui avalerait le zero.
        $this->assertSame(0.0, (float) $courant['current_recomputed_raw_total'], 'La moyenne brute d une note de 0 est 0, pas une absence.');
        $this->assertEqualsWithDelta(
            (float) $courant['current_recomputed_raw_total'] + (float) $courant['current_recomputed_note_assiduite'],
            $reponse->viewData('moyenneSemestre2'),
            0.001,
            'L onglet affiche la moyenne brute plus l assiduite, comme le bandeau « Courant ».'
        );
    }

    private ESBTPMatiere $matiere;

    private function eleveNoteAuSeulSemestre1(): \App\Models\ESBTPEtudiant
    {
        $this->monterLaClasse();
        $this->matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($this->matiere), 14);

        return $etudiant;
    }

    private function evaluationDuSemestre2(ESBTPMatiere $matiere): ESBTPEvaluation
    {
        return ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
    }

    private function resultats(int $etudiantId, string $periode): TestResponse
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = User::withoutEvents(fn () => User::factory()->create());
        $admin->assignRole('superAdmin');

        $reponse = $this->actingAs($admin)->get(route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiantId,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => $periode,
        ]));
        $reponse->assertOk();

        return $reponse;
    }
}
