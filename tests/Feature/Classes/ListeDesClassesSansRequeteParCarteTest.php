<?php

namespace Tests\Feature\Classes;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * /esbtp/classes relancait une dizaine de requetes PAR CARTE : chaque lecture de
 * `nombre_etudiants` (cinq par carte, places_disponibles compris) cherchait l'annee
 * courante puis comptait les inscriptions, et le badge « Sort de … » d'une
 * specialite resolvait son tronc commun a la demande. 6,7 s sur la demo.
 *
 * Le controleur pose desormais ces valeurs sur les cartes en requetes groupees
 * (ESBTPClasse::preparerPourListe). Ce test verifie les deux faces : le nombre
 * de requetes ne depend plus du nombre de classes, et les valeurs posees sont
 * celles que le calcul classe par classe rendait.
 */
class ListeDesClassesSansRequeteParCarteTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPNiveauEtude $niveau;

    private ESBTPFiliere $tronc;

    private ESBTPFiliere $specialite;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('classes.view', 'web');
        Permission::findOrCreate('admin.access', 'web');
        Role::findOrCreate('superAdmin', 'web');
        InstallationHelper::flushCachedStatus();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $this->tronc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $this->specialite = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => $this->tronc->id]);
    }

    public function test_le_nombre_de_requetes_ne_croit_pas_avec_le_nombre_de_cartes(): void
    {
        $this->classeTroncCommun();
        $this->classesDeSpecialite(2);
        $this->requetesDeLaListe(); // caches de permissions et reglages
        $avecTrois = $this->requetesDeLaListe();

        $this->classesDeSpecialite(6);
        $avecNeuf = $this->requetesDeLaListe();

        $this->assertSame(
            $avecTrois,
            $avecNeuf,
            "La liste des classes lance {$avecTrois} requetes pour 3 cartes et {$avecNeuf} pour 9 : "
            . 'une carte relance des requetes a l\'affichage.'
        );
    }

    public function test_les_valeurs_posees_sont_celles_du_calcul_classe_par_classe(): void
    {
        $tronc = $this->classeTroncCommun();
        $specialites = $this->classesDeSpecialite(3);
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        // Une inscription d'une autre annee et une inscription en attente ne
        // prennent pas de place : la regle est celle d'occupeUnePlace().
        ESBTPInscription::factory()->create(['classe_id' => $specialites[0]->id, 'annee_universitaire_id' => $autreAnnee->id]);
        ESBTPInscription::factory()->create([
            'classe_id' => $specialites[0]->id,
            'annee_universitaire_id' => $this->annee->id,
            'workflow_step' => 'prospect',
        ]);

        $attendu = ESBTPClasse::query()->get()->mapWithKeys(fn (ESBTPClasse $c) => [$c->id => [
            'inscrits' => $c->nombre_etudiants,
            'disponibles' => $c->places_disponibles,
            'tronc' => optional($c->classeTroncCommunParent())->id,
        ]]);

        $preparees = ESBTPClasse::query()->get();
        ESBTPClasse::preparerPourListe($preparees);
        DB::enableQueryLog();
        $obtenu = $preparees->mapWithKeys(fn (ESBTPClasse $c) => [$c->id => [
            'inscrits' => $c->nombre_etudiants,
            'disponibles' => $c->places_disponibles,
            'tronc' => optional($c->classeTroncCommunParent())->id,
        ]]);

        $this->assertSame([], DB::getQueryLog(), 'Une carte preparee ne doit plus lancer de requete.');
        $this->assertEquals($attendu->all(), $obtenu->all());
        $this->assertSame($tronc->id, $obtenu[$specialites[1]->id]['tronc']);
        $this->assertSame(2, $obtenu[$specialites[1]->id]['inscrits']);
        $this->assertNull($obtenu[$tronc->id]['tronc']);
    }

    public function test_le_rattachement_manuel_l_emporte_comme_avant(): void
    {
        $this->classeTroncCommun();
        $autreTronc = $this->classeTroncCommun();
        [$specialite] = $this->classesDeSpecialite(1);
        DB::table('esbtp_classe_orientation_targets')->insert([
            'source_classe_id' => $autreTronc->id,
            'target_classe_id' => $specialite->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $preparees = ESBTPClasse::query()->get();
        ESBTPClasse::preparerPourListe($preparees);

        $this->assertSame($autreTronc->id, $specialite->fresh()->classeTroncCommunParent()->id);
        $this->assertSame($autreTronc->id, $preparees->firstWhere('id', $specialite->id)->classeTroncCommunParent()->id);
    }

    private function requetesDeLaListe(): int
    {
        $user = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $user->assignRole('superAdmin');
        $user->givePermissionTo(['admin.access', 'classes.view']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('esbtp.classes.index'))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    private function classeTroncCommun(): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'filiere_id' => $this->tronc->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
    }

    /** @return array<int, ESBTPClasse> */
    private function classesDeSpecialite(int $nombre): array
    {
        $classes = [];
        for ($i = 0; $i < $nombre; $i++) {
            $classe = ESBTPClasse::factory()->create([
                'filiere_id' => $this->specialite->id,
                'niveau_etude_id' => $this->niveau->id,
                'annee_universitaire_id' => $this->annee->id,
            ]);
            ESBTPInscription::factory()->count(2)->create([
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $this->annee->id,
            ]);
            $classes[] = $classe;
        }

        return $classes;
    }
}
