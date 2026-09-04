<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le filtre de periode de /esbtp/inscriptions.
 *
 * Il porte sur la DATE D'INSCRIPTION — celle que le dossier affiche — et non
 * sur la date de saisie. Les deux bornes sont incluses.
 */
class FiltrePeriodeInscriptionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('inscriptions.view', 'web');
        Cache::flush();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['admin.access', 'inscriptions.view']);
        $this->actingAs($this->user);

        $this->classe = ESBTPClasse::factory()->create();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
    }

    private function inscriptionDu(string $date): ESBTPInscription
    {
        return ESBTPInscription::factory()->create([
            'classe_id' => $this->classe->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'annee_universitaire_id' => $this->annee->id,
            'date_inscription' => $date,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * @param  array<string, string>  $periode
     */
    private function lister(array $periode): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.inscriptions.index', $periode + [
                'annee' => $this->annee->id,
                'status' => 'all',
            ]));
    }

    public function test_la_periode_ne_garde_que_les_inscriptions_comprises_entre_les_bornes(): void
    {
        $this->inscriptionDu('2026-08-20');
        $dedans = $this->inscriptionDu('2026-09-10');
        $this->inscriptionDu('2026-10-05');

        $reponse = $this->lister(['date_debut' => '2026-09-01', 'date_fin' => '2026-09-30']);

        $reponse->assertOk()->assertJsonPath('total', 1);
        // Le matricule identifie la ligne sans dependre du balisage du tableau.
        $this->assertStringContainsString(
            $dedans->etudiant->matricule,
            $reponse->json('html'),
        );
    }

    public function test_les_deux_bornes_sont_incluses(): void
    {
        $this->inscriptionDu('2026-09-01');
        $this->inscriptionDu('2026-09-30');
        $this->inscriptionDu('2026-10-01');

        $this->lister(['date_debut' => '2026-09-01', 'date_fin' => '2026-09-30'])
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_une_borne_seule_ouvre_la_periode_de_l_autre_cote(): void
    {
        $this->inscriptionDu('2026-08-20');
        $this->inscriptionDu('2026-09-10');
        $this->inscriptionDu('2026-10-05');

        $this->lister(['date_debut' => '2026-09-01'])
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->lister(['date_fin' => '2026-09-30'])
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_des_bornes_inversees_sont_remises_a_l_endroit(): void
    {
        // Saisir « du 30 au 1er » est une inversion de doigts, pas une demande
        // de zero resultat.
        $this->inscriptionDu('2026-09-10');
        $this->inscriptionDu('2026-11-10');

        $this->lister(['date_debut' => '2026-09-30', 'date_fin' => '2026-09-01'])
            ->assertOk()
            ->assertJsonPath('total', 0);

        $this->lister(['date_debut' => '2026-09-30', 'date_fin' => '2026-09-05'])
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_une_date_illisible_est_ignoree_et_ne_casse_pas_la_liste(): void
    {
        $this->inscriptionDu('2026-09-10');

        $this->lister(['date_debut' => 'pas-une-date'])
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_les_compteurs_du_bandeau_portent_sur_la_meme_periode_que_la_liste(): void
    {
        $this->inscriptionDu('2026-09-10');
        $this->inscriptionDu('2026-11-10');

        $reponse = $this->lister(['date_debut' => '2026-09-01', 'date_fin' => '2026-09-30']);

        $reponse->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('stats.total', 1);
    }
}
