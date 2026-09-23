<?php

namespace Tests\Feature\Resultat;

use App\Models\ESBTPInscription;
use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * `/esbtp/resultats/etudiant/{id}` ouvert SANS classe, pour un eleve qui a
 * pourtant des notes cette annee.
 *
 * Le cas arrive quand l'inscription a ete supprimee et que le lien ne porte pas
 * de `classe_id` — celui du chatbot, par exemple. La page lisait `$classe->id`
 * pour chercher le coefficient de chaque matiere et tombait en erreur 500.
 *
 * Ce n'est PAS le cas de l'eleve pas encore reinscrit a la rentree : lui n'a
 * aucune note dans l'annee, la boucle ne tourne pas.
 */
class ResultatEtudiantSansClasseTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;

    public function test_la_fiche_s_ouvre_sans_classe_et_ne_devine_pas_le_coefficient(): void
    {
        $this->monterLaClasse();
        $notee = $this->matiereConfiguree();
        $saisie = $this->matiereConfiguree();

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($notee), 14);

        // Second chemin : une moyenne enregistree sur une matiere sans note.
        ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $saisie->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 12,
            'coefficient' => 3,
        ]);

        ESBTPInscription::where('etudiant_id', $etudiant->id)->delete();
        $journal = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$journal) {
            $journal[] = $e;
        });

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->get(route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'annuel',
            ]));

        $reponse->assertOk();
        $this->assertNull($reponse->viewData('classe'), 'Temoin : la page doit bien etre ouverte sans classe.');

        $matieres = $reponse->viewData('notesByMatiere');
        $this->assertArrayHasKey($notee->id, $matieres, 'Temoin : la matiere notee doit etre listee.');
        $this->assertArrayHasKey($saisie->id, $matieres, 'Temoin : la moyenne enregistree doit etre listee.');

        // Aucune moyenne : elle reposerait sur des coefficients devines (1 au
        // lieu de 2) et se lirait comme l'officielle, verdict compris.
        $this->assertNull($reponse->viewData('moyenneGenerale'));
        $this->assertNull($reponse->viewData('moyenneAvecAssiduite'));
        $this->assertNull($reponse->viewData('moyenneSemestre1'));
        $this->assertNull($reponse->viewData('moyenneSemestre2'));
        $this->assertNull($reponse->viewData('detailUiState')['display_average']);
        $reponse->assertDontSee('ADMIS');
        $reponse->assertDontSee('AJOURNÉ');

        // Le repli se dit au journal, une fois, par sa vraie cause.
        $sansClasse = array_filter($journal, fn (MessageLogged $e) => $e->level === 'warning'
            && str_contains($e->message, 'fiche ouverte sans classe'));
        $this->assertCount(1, $sansClasse, 'La fiche sans classe doit etre journalisee une fois.');
        $this->assertEmpty(array_filter($journal, fn (MessageLogged $e) => str_contains($e->message, 'coefficient introuvable')),
            'Sans classe, ce n est pas un coefficient manquant : pas un avertissement par matiere.');
    }

    private function unSuperAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');

        return $user;
    }
}
