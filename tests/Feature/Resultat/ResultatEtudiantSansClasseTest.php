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

        // La matiere porte un coefficient 2 dans la classe ; sans classe, il
        // est introuvable. La page le signale au lieu de le deviner.
        $this->assertArrayHasKey($notee->id, $matieres, 'Temoin : la matiere notee doit etre listee.');
        $this->assertEquals(1, $matieres[$notee->id]['matiere_coefficient']);
        $this->assertTrue($matieres[$notee->id]['matiere_coefficient_missing'] ?? false);

        // La moyenne saisie garde le coefficient qu'elle porte elle-meme.
        $this->assertArrayHasKey($saisie->id, $matieres, 'Temoin : la moyenne enregistree doit etre listee.');
        $this->assertEquals(3, $matieres[$saisie->id]['matiere_coefficient']);

        // Aucune moyenne de semestre : elle reposerait sur des coefficients
        // devines, et se lirait comme l'officielle.
        $this->assertNull($reponse->viewData('moyenneSemestre1'));
        $this->assertNull($reponse->viewData('moyenneSemestre2'));

        // Le repli se dit au journal : sinon personne ne cherche.
        $this->assertNotEmpty(array_filter($journal, fn (MessageLogged $e) => $e->level === 'warning'
            && str_contains($e->message, 'coefficient introuvable')
            && ($e->context['matiere_id'] ?? null) === $notee->id), 'Le repli doit etre journalise.');
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
