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
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
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
 * Les trois derniers verrouillent la suppression du repli : un semestre qui n'a
 * plus que son bulletin officiel ne change pas de reponse selon l'onglet ouvert,
 * un eleve sans classe ni note n'herite pas d'un 0 invente, et, sans classe, une
 * note d'une autre annee ne fait plus tomber la page.
 */
class MoyenneSemestreSansNoteTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;
    use SeedsConfiguredBulletin;

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

    /**
     * Un semestre qui n'existe plus que par son bulletin officiel (notes
     * deplacees ou annulees) : l'onglet doit dire la meme chose quel que soit
     * l'onglet ouvert. Avant, vide ouvert sur S2, 12,00 ouvert sur S1.
     */
    public function test_la_moyenne_d_un_semestre_ne_depend_pas_de_l_onglet_ouvert(): void
    {
        $etudiant = $this->eleveNoteAuSeulSemestre1();
        $bulletin = $this->seedConfiguredBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre2', [$this->matiere->id], []
        );
        $bulletin->forceFill(['moyenne_generale' => 12])->save();

        foreach (['semestre1', 'semestre2', 'annuel'] as $ouvert) {
            $this->assertNull(
                $this->resultats($etudiant->id, $ouvert)->viewData('moyenneSemestre2'),
                "Ouvert sur {$ouvert} : sans note vivante, le semestre 2 n'a pas de moyenne d'onglet."
            );
        }
    }

    /** Sans inscription, pas de classe ni de snapshot : la page ne doit pas inventer un 0. */
    public function test_sans_classe_un_eleve_sans_note_n_a_pas_de_moyenne(): void
    {
        $this->monterLaClasse();
        $etudiant = \App\Models\ESBTPEtudiant::factory()->create();

        $reponse = $this->resultats($etudiant->id, 'semestre1', avecClasse: false);

        $this->assertNull($reponse->viewData('moyenneSemestre1'), 'Sans note, pas de moyenne : garde-fou, deja vrai sur presentation (93f75fcd).');
        $this->assertNull($reponse->viewData('moyenneAvecAssiduite'));
    }

    /**
     * Reproduit par la revue : sans classe, le repli lisait les notes de l'eleve
     * TOUTES ANNEES confondues, puis levait « Classe invalide pour le calcul du
     * coefficient » sur la classe 0. La page redirigeait (302) sans s'afficher.
     * Cas reel : un eleve pas encore reinscrit a la rentree, ouvert sans classe.
     */
    public function test_sans_classe_une_note_d_une_autre_annee_ne_fait_pas_tomber_la_page(): void
    {
        $etudiant = $this->eleveNoteAuSeulSemestre1();
        $this->noter($etudiant, $this->evaluationDuSemestre2($this->matiere), 14);
        $anneeSansInscription = \App\Models\ESBTPAnneeUniversitaire::factory()->create();

        $reponse = $this->resultats($etudiant->id, 'semestre1', avecClasse: false, annee: $anneeSansInscription->id);

        $this->assertNull($reponse->viewData('moyenneSemestre1'));
        $this->assertNull($reponse->viewData('moyenneSemestre2'), 'Les notes d une autre annee ne font pas une moyenne de cette annee.');
    }

    /**
     * Change par l'integration de presentation (ac2737fc) : la moyenne courante se juge
     * sur la somme des coefficients. Une matiere faite d'absences seules, reglage
     * « absences seules comptent 0 » desactive, n'a pas de moyenne : l'eleve n'en a donc
     * pas non plus, au lieu de 0,00. Le reglage se pose AVANT l'absence : pose apres, le
     * recalcul automatique aurait deja enregistre un 0, que le calcul « Courant » relit.
     */
    public function test_des_absences_seules_ecartees_ne_font_pas_une_moyenne_de_zero(): void
    {
        $this->monterLaClasse();
        $this->matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();
        \App\Helpers\SettingsHelper::setOrCreate(\App\Services\NoteCalculationService::REGLAGE_ABSENCES_SEULES_COMPTENT_ZERO, '0', 'bulletin');
        \App\Models\ESBTPNote::create([
            'evaluation_id' => $this->evaluationDe($this->matiere)->id,
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'note' => 0,
            'is_absent' => true,
        ]);

        $reponse = $this->resultats($etudiant->id, 'semestre1');

        $this->assertNull(
            $reponse->viewData('bulletinConsistency')['current_recomputed_raw_total'],
            'Temoin : le calcul « Courant » ne rend rien, il ne prend donc pas le relais.'
        );
        $this->assertNull($reponse->viewData('moyenneGenerale'), 'Reglage desactive : aucune moyenne (avant : 0,00).');
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

    private function resultats(int $etudiantId, string $periode, bool $avecClasse = true, ?int $annee = null): TestResponse
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = User::withoutEvents(fn () => User::factory()->create());
        $admin->assignRole('superAdmin');

        $reponse = $this->actingAs($admin)->get(route('esbtp.resultats.etudiant', array_filter([
            'etudiant' => $etudiantId,
            'classe_id' => $avecClasse ? $this->classe->id : null,
            'annee_universitaire_id' => $annee ?? $this->annee->id,
            'periode' => $periode,
        ])));
        $reponse->assertOk();

        return $reponse;
    }
}
