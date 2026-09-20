<?php

namespace Tests\Feature\Notes;

use App\Models\ESBTPResultat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * `php artisan notes:recompute` — la commande n'avait AUCUN test, et son mode
 * par defaut ne recalculait rien.
 *
 * Elle appelait `(new RecomputeStudentResultatJob(...))->handle()` sans
 * argument, alors que `handle()` exige un `NoteCalculationService`.
 * L'`ArgumentCountError` tombait dans le `catch (\Throwable)` de sa boucle :
 * une ligne rouge par couple, zero agregat touche, et cela **depuis toujours**.
 * Le chantier a refactore cette boucle pour lui greffer `PerimetreDeRecalcul`
 * et a publie qu'elle etait « joignable depuis un terminal du serveur » — sans
 * l'avoir lancee une fois.
 *
 * Ce qui a ete corrige n'est pas la signature de l'appel, c'est la duplication
 * qui le rendait possible : l'execution passe desormais par
 * `PerimetreDeRecalcul::recalculer()`, le meme service que l'endpoint CLI.
 */
class NotesRecomputeCommandTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /**
     * Rendez sa boucle a la commande : ce test tombe, sur une moyenne restee
     * perimee et un code de sortie en echec.
     *
     * @test
     */
    public function la_commande_recalcule_vraiment_un_agregat_perime(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $this->noter($etudiant, $this->evaluationDe($matiere), 10);

        // On perime l'agregat a la main, comme le ferait une ecriture par query
        // builder qui ne declenche aucun evenement Eloquent.
        ESBTPResultat::where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->update(['moyenne' => 3.0]);

        $this->assertSame(3.0, $this->moyenneEnregistree($etudiant->id, $matiere->id));

        $code = $this->artisan('notes:recompute', [
            '--classe' => $this->classe->id,
            '--periode' => 'semestre1',
            '--annee' => $this->annee->id,
        ])->run();

        $this->assertSame(0, $code, 'la commande doit sortir en succes');
        $this->assertSame(10.0, $this->moyenneEnregistree($etudiant->id, $matiere->id));
    }

    /** @test */
    public function la_simulation_n_ecrit_rien(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $this->noter($etudiant, $this->evaluationDe($matiere), 10);

        ESBTPResultat::where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->update(['moyenne' => 3.0]);

        $code = $this->artisan('notes:recompute', [
            '--classe' => $this->classe->id,
            '--dry-run' => true,
        ])->run();

        $this->assertSame(0, $code);
        $this->assertSame(3.0, $this->moyenneEnregistree($etudiant->id, $matiere->id));
    }

    private function moyenneEnregistree(int $etudiantId, int $matiereId): ?float
    {
        $valeur = ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $this->classe->id)
            ->where('matiere_id', $matiereId)
            ->where('periode', 'semestre1')
            ->where('annee_universitaire_id', $this->annee->id)
            ->value('moyenne');

        return $valeur === null ? null : (float) $valeur;
    }
}
