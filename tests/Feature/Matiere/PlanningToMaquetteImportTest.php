<?php

namespace Tests\Feature\Matiere;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Import de la maquette depuis le planning general.
 *
 * Le planning ALIMENTE la maquette par un geste explicite. Trois garanties
 * comptent ici : l'apercu n'ecrit rien, l'application EXIGE l'empreinte rendue
 * par l'apercu, et elle la refuse si la source a bouge entretemps — plutot que
 * d'ecraser en silence le travail d'un autre.
 */
class PlanningToMaquetteImportTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('matieres.edit', 'web');

        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole($superAdmin);

        $this->acteur = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->acteur->givePermissionTo(['admin.access', 'matieres.edit']);
    }

    private function matiereDeLaMaquette(): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
        ]);

        return $matiere;
    }

    private function planifier(ESBTPMatiere $matiere, int $semestre, bool $active = true): void
    {
        ESBTPPlanificationAcademique::create([
            'annee_universitaire_id' => $this->annee->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'matiere_id' => $matiere->id,
            'semestre' => $semestre,
            'volume_horaire_total' => 30,
            'is_active' => $active,
        ]);
    }

    /** Le parcours reel : on regarde l'apercu, puis on applique ce qu'on a vu. */
    private function apercuPuisAppliquer()
    {
        $empreinte = $this->importer(appliquer: false)->json('diff.empreinte');

        return $this->importer(appliquer: true, empreinte: $empreinte);
    }

    private function importer(bool $appliquer, ?string $empreinte = null)
    {
        return $this->actingAs($this->acteur)->postJson(
            route('esbtp.matieres.classification.import-planning'),
            array_filter([
                'filiere_id' => $this->filiere->id,
                'niveau_id' => $this->niveau->id,
                'annee_universitaire_id' => $this->annee->id,
                'appliquer' => $appliquer,
                'empreinte' => $empreinte,
            ], fn ($v) => $v !== null)
        );
    }

    public function test_le_diff_deduit_un_semestre_unique_et_les_deux_semestres(): void
    {
        $surLesDeux = $this->matiereDeLaMaquette();
        $auPremier = $this->matiereDeLaMaquette();

        $this->planifier($surLesDeux, 1);
        $this->planifier($surLesDeux, 2);
        $this->planifier($auPremier, 1);

        $reponse = $this->importer(appliquer: false)->assertOk();
        $lignes = collect($reponse->json('diff.lignes'))->keyBy('matiere_id');

        // Prevue aux deux semestres : aucun semestre impose.
        self::assertNull($lignes[$surLesDeux->id]['propose']);
        self::assertSame(1, $lignes[$auPremier->id]['propose']);
    }

    public function test_l_apercu_n_ecrit_rien(): void
    {
        $matiere = $this->matiereDeLaMaquette();
        $this->planifier($matiere, 2);

        $this->importer(appliquer: false)->assertOk()->assertJsonPath('applied', false);

        $ligne = ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first();
        self::assertNull($ligne->semestre);
        // Surtout : l'apercu n'active pas la maquette.
        self::assertFalse((bool) $ligne->semestre_renseigne);
    }

    public function test_l_application_ecrit_et_active_la_maquette(): void
    {
        $matiere = $this->matiereDeLaMaquette();
        $this->planifier($matiere, 2);

        $this->apercuPuisAppliquer()->assertOk()->assertJsonPath('applied', true);

        $ligne = ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first();
        self::assertSame(2, (int) $ligne->semestre);
        self::assertTrue((bool) $ligne->semestre_renseigne);
    }

    public function test_une_ligne_de_planning_inactive_est_ignoree(): void
    {
        $matiere = $this->matiereDeLaMaquette();
        $this->planifier($matiere, 1, active: false);

        $reponse = $this->importer(appliquer: false)->assertOk();

        self::assertSame(0, $reponse->json('diff.planning_lignes'));
        self::assertFalse($reponse->json('diff.lignes.0.dans_le_planning'));
    }

    public function test_une_matiere_planifiee_hors_maquette_est_signalee_sans_etre_ecrite(): void
    {
        // Planifiee, mais jamais rattachee au combo : l'import la signale et
        // s'arrete la. Creer la liaison ferait apparaitre une matiere de plus
        // au bulletin, ce qui deborde « renseigner des semestres ».
        $horsMaquette = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->planifier($horsMaquette, 1);

        $reponse = $this->apercuPuisAppliquer()->assertOk();

        self::assertSame(
            [$horsMaquette->id],
            collect($reponse->json('diff.hors_maquette'))->pluck('matiere_id')->all()
        );
        self::assertDatabaseMissing('esbtp_matiere_filiere_niveau', [
            'matiere_id' => $horsMaquette->id,
            'filiere_id' => $this->filiere->id,
        ]);
    }

    public function test_un_apercu_perime_est_refuse_au_lieu_d_ecraser(): void
    {
        $matiere = $this->matiereDeLaMaquette();
        $this->planifier($matiere, 1);

        $empreinte = $this->importer(appliquer: false)->json('diff.empreinte');

        // Le planning bouge entre l'apercu et l'application.
        $this->planifier($matiere, 2);

        $this->importer(appliquer: true, empreinte: $empreinte)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        // Rien n'a ete ecrit : l'utilisateur doit relire avant d'appliquer.
        self::assertFalse(
            (bool) ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first()->semestre_renseigne
        );
    }

    public function test_appliquer_sans_empreinte_est_refuse(): void
    {
        $matiere = $this->matiereDeLaMaquette();
        $this->planifier($matiere, 1);

        // Une garde qu'on peut sauter en n'envoyant pas le champ ne garde rien :
        // l'application doit exiger l'empreinte, pas seulement la verifier
        // quand elle est presente.
        $this->importer(appliquer: true, empreinte: null)
            ->assertStatus(422)
            ->assertJsonValidationErrors('empreinte');

        self::assertFalse(
            (bool) ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first()->semestre_renseigne
        );
    }

    public function test_appliquer_deux_fois_la_meme_chose_ne_change_rien_de_plus(): void
    {
        $matiere = $this->matiereDeLaMaquette();
        $this->planifier($matiere, 1);

        $this->apercuPuisAppliquer()->assertOk();
        $premier = ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first()->only(['semestre', 'semestre_renseigne']);

        $this->apercuPuisAppliquer()->assertOk();
        $second = ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first()->only(['semestre', 'semestre_renseigne']);

        self::assertSame($premier, $second);
    }

    public function test_sans_ligne_de_planning_le_diff_est_vide(): void
    {
        $this->matiereDeLaMaquette();

        $reponse = $this->importer(appliquer: false)->assertOk();

        self::assertSame(0, $reponse->json('diff.planning_lignes'));
        self::assertSame([], $reponse->json('diff.hors_maquette'));
    }
}
