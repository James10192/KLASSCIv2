<?php

namespace Tests\Feature\LMD;

use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\CompositionUe;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'ecran des unites d'enseignement sait designer la maquette qu'il edite.
 *
 * Le decoupage d'une composition par parcours existait dans le service et dans
 * la lecture (bulletins, plannings, liste filtree), mais aucun ecran ne
 * transmettait la maquette visee : le modal ECUE posait tout en commun, le
 * retrait ne visait que le commun et repondait « detache » a vide, et la
 * liste ne disait pas a qui appartenait une ligne. Ces tests tiennent le
 * contrat entre l'ecran et le serveur : la portee circule dans les deux sens.
 */
class EcuePorteeDepuisEcranTest extends TestCase
{
    use RefreshDatabase;

    private CompositionUe $composition;

    private ESBTPUniteEnseignement $ue;

    private ESBTPMatiere $ecueBu;

    private ESBTPLMDParcours $batiment;

    private ESBTPLMDParcours $travauxPublics;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();

        // L'import exige une annee courante (LMDImportService:49).
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        $this->composition = app(CompositionUe::class);

        // Meme montage que CompositionUeParParcoursTest : deux maquettes
        // importees, puis l'unite de Batiment rattachee aussi a Travaux Publics
        // par le geste du modal « Lier a des parcours ».
        app(LMDImportService::class)->import($this->maquette('BU', 'Batiment', 'UE-PARTAGEE', 'ECUE-BU'));
        app(LMDImportService::class)->import($this->maquette('TIR', 'Travaux Publics', 'UE-TIR', 'ECUE-TIR'));

        $this->ue = ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail();
        $this->ecueBu = ESBTPMatiere::where('code', 'ECUE-BU')->firstOrFail();
        $this->batiment = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $this->travauxPublics = ESBTPLMDParcours::where('code', 'TIR')->firstOrFail();

        DB::table('esbtp_lmd_parcours_ue')->insert([
            'parcours_id' => $this->travauxPublics->id,
            'unite_enseignement_id' => $this->ue->id,
            'semestre' => 1,
            'is_optional' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Le groupe de routes LMD exige un niveau d'acces global, l'acces au
        // module, puis un droit par geste.
        foreach (['admin.access', 'module.lmd.access', 'lmd.structure.view', 'lmd.structure.manage', 'lmd.structure.delete'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Sans un superAdmin en base, l'application se considere non installee
        // et redirige tout vers /install.
        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole($superAdmin);

        $this->acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->acteur->givePermissionTo(['admin.access', 'module.lmd.access', 'lmd.structure.view', 'lmd.structure.manage', 'lmd.structure.delete']);

        // Le paywall interroge le maitre SaaS : hors sujet ici.
        $this->withoutMiddleware(PaywallMiddleware::class);
    }

    public function test_la_liste_dit_a_quelle_maquette_appartient_chaque_ligne(): void
    {
        // Travaux Publics surcharge l'element commun avec son propre coefficient.
        $this->composition->poser($this->ue, (int) $this->ecueBu->id, [
            'coefficient_ecue' => 2,
            'credit_ecue' => 3,
            'ordre_bulletin' => 0,
        ], (int) $this->travauxPublics->id);

        // Vue « Tous » : les deux versions, chacune avec sa portee. L'import a
        // reserve ECUE-BU a Batiment ; Travaux Publics vient d'y ajouter la sienne.
        $tout = $this->actingAs($this->acteur)
            ->getJson(route('esbtp.lmd.ue.index', ['format' => 'json', 'search' => 'UE-PARTAGEE']))
            ->assertOk()
            ->json('ues.0.ecues');

        $lignes = collect($tout)->where('code', 'ECUE-BU');
        $this->assertCount(2, $lignes, 'La vue « Tous » doit montrer une ligne par maquette qui tient l element.');
        $this->assertEqualsCanonicalizing(
            [(int) $this->batiment->id, (int) $this->travauxPublics->id],
            $lignes->pluck('portee')->all()
        );
        $this->assertSame('TIR', $lignes->firstWhere('portee', (int) $this->travauxPublics->id)['portee_code']);
        $this->assertSame('BU', $lignes->firstWhere('portee', (int) $this->batiment->id)['portee_code']);

        // Une version commune s'ajoute aux reservees, avec sa propre ligne.
        $this->composition->poser($this->ue, (int) $this->ecueBu->id, [
            'coefficient_ecue' => 1, 'credit_ecue' => 3, 'ordre_bulletin' => 0,
        ]);
        $commune = collect($this->actingAs($this->acteur)
            ->getJson(route('esbtp.lmd.ue.index', ['format' => 'json', 'search' => 'UE-PARTAGEE']))
            ->json('ues.0.ecues'))->where('code', 'ECUE-BU')->firstWhere('portee', 0);
        $this->assertNotNull($commune, 'La version commune doit avoir sa ligne.');
        $this->assertNull($commune['portee_code']);

        // Vue filtree sur Travaux Publics : une seule ligne, la reservee.
        $tir = $this->actingAs($this->acteur)
            ->getJson(route('esbtp.lmd.ue.index', ['format' => 'json', 'search' => 'UE-PARTAGEE', 'parcours_id' => $this->travauxPublics->id]))
            ->assertOk()
            ->json('ues.0.ecues');

        $lignes = collect($tir)->where('code', 'ECUE-BU');
        $this->assertCount(1, $lignes);
        $this->assertSame((int) $this->travauxPublics->id, $lignes->first()['portee']);
        $this->assertSame('Travaux Publics', $lignes->first()['portee_label']);
        $this->assertEquals(2, (float) $lignes->first()['coefficient']);
    }

    public function test_le_modal_reserve_un_element_a_la_maquette_choisie(): void
    {
        $this->actingAs($this->acteur)
            ->postJson(route('esbtp.lmd.ue.ecue.store', $this->ue), [
                'name' => 'Topographie appliquee',
                'code' => 'TOPO-TIR',
                'credit_ecue' => 2,
                'coefficient_ecue' => 1,
                'parcours_id' => $this->travauxPublics->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $topo = ESBTPMatiere::where('code', 'TOPO-TIR')->firstOrFail();

        $this->assertDatabaseHas('esbtp_ue_matiere', [
            'unite_enseignement_id' => $this->ue->id,
            'matiere_id' => $topo->id,
            'parcours_id' => $this->travauxPublics->id,
        ]);
        $this->assertDatabaseMissing('esbtp_ue_matiere', [
            'unite_enseignement_id' => $this->ue->id,
            'matiere_id' => $topo->id,
            'parcours_id' => CompositionUe::COMMUN,
        ]);

        $this->assertContains('TOPO-TIR', $this->vusPar($this->travauxPublics));
        $this->assertNotContains('TOPO-TIR', $this->vusPar($this->batiment), 'Un element reserve a Travaux Publics ne doit pas entrer dans la maquette de Batiment.');
    }

    public function test_changer_la_maquette_d_un_element_commun_le_deplace(): void
    {
        // Le cas USAT : un element pose en commun, puis modifie en « Reservee a
        // Batiment ». La ligne commune doit partir, sinon Travaux Publics le
        // voit toujours alors que l'ecran affirme le contraire.
        $this->composition->retirer($this->ue, [(int) $this->ecueBu->id], (int) $this->batiment->id);
        $this->composition->poser($this->ue, (int) $this->ecueBu->id, ['coefficient_ecue' => 1, 'credit_ecue' => 3, 'ordre_bulletin' => 0]);
        $this->assertContains('ECUE-BU', $this->vusPar($this->travauxPublics));

        $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.ecue.update', [$this->ue, $this->ecueBu]), [
                'credit_ecue' => 3,
                'parcours_id' => $this->batiment->id,
                'portee_origine' => 0,
                'garder_origine' => 0,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('esbtp_ue_matiere', [
            'unite_enseignement_id' => $this->ue->id,
            'matiere_id' => $this->ecueBu->id,
            'parcours_id' => CompositionUe::COMMUN,
        ]);
        $this->assertContains('ECUE-BU', $this->vusPar($this->batiment));
        $this->assertNotContains('ECUE-BU', $this->vusPar($this->travauxPublics));
    }

    public function test_repasser_un_element_reserve_en_commun_le_rend_a_tous(): void
    {
        // L'import a reserve ECUE-BU a Batiment. On le rend commun.
        $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.ecue.update', [$this->ue, $this->ecueBu]), [
                'credit_ecue' => 3,
                'portee_origine' => $this->batiment->id,
                'garder_origine' => 0,
            ])
            ->assertOk();

        $lignes = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $this->ue->id)
            ->where('matiere_id', $this->ecueBu->id)
            ->pluck('parcours_id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([CompositionUe::COMMUN], $lignes, 'Une seule ligne, la commune.');
        $this->assertContains('ECUE-BU', $this->vusPar($this->travauxPublics));
    }

    public function test_garder_aussi_l_origine_conserve_la_surcharge(): void
    {
        $this->composition->poser($this->ue, (int) $this->ecueBu->id, ['coefficient_ecue' => 1, 'credit_ecue' => 3, 'ordre_bulletin' => 0]);

        $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.ecue.update', [$this->ue, $this->ecueBu]), [
                'coefficient_ecue' => 2,
                'parcours_id' => $this->travauxPublics->id,
                'portee_origine' => 0,
                'garder_origine' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('esbtp_ue_matiere', [
            'unite_enseignement_id' => $this->ue->id,
            'matiere_id' => $this->ecueBu->id,
            'parcours_id' => CompositionUe::COMMUN,
        ]);
        $this->assertDatabaseHas('esbtp_ue_matiere', [
            'unite_enseignement_id' => $this->ue->id,
            'matiere_id' => $this->ecueBu->id,
            'parcours_id' => $this->travauxPublics->id,
        ]);
    }

    public function test_le_compte_d_ecue_suit_la_maquette_filtree(): void
    {
        // ECUE-BU est reserve a Batiment : Travaux Publics n'en voit aucun.
        $compte = fn ($parcoursId) => $this->actingAs($this->acteur)
            ->getJson(route('esbtp.lmd.ue.index', ['format' => 'json', 'search' => 'UE-PARTAGEE', 'parcours_id' => $parcoursId]))
            ->assertOk()->json('ues.0.matieres_count');

        $this->assertSame(0, $compte($this->travauxPublics->id));
        $this->assertSame(1, $compte($this->batiment->id));
    }

    public function test_la_liste_signale_un_element_a_la_fois_commun_et_reserve(): void
    {
        // L'import a reserve ECUE-BU a Batiment ; on ajoute la ligne commune.
        $this->composition->poser($this->ue, (int) $this->ecueBu->id, ['coefficient_ecue' => 1, 'credit_ecue' => 3, 'ordre_bulletin' => 0]);

        $doubles = $this->actingAs($this->acteur)
            ->getJson(route('esbtp.lmd.ue.index', ['format' => 'json', 'search' => 'UE-PARTAGEE', 'parcours_id' => $this->travauxPublics->id]))
            ->assertOk()
            ->json('ues.0.communs_et_reserves');

        $this->assertCount(1, $doubles, 'Le signalement vaut quel que soit le filtre de parcours.');
        $this->assertSame(['BU'], $doubles[0]['reserve_a']);
    }

    public function test_le_retrait_vise_la_maquette_de_la_ligne_cliquee(): void
    {
        $this->composition->poser($this->ue, (int) $this->ecueBu->id, [
            'coefficient_ecue' => 2, 'credit_ecue' => 3, 'ordre_bulletin' => 0,
        ], (int) $this->travauxPublics->id);

        $this->actingAs($this->acteur)
            ->deleteJson(route('esbtp.lmd.ue.ecue.destroy', [$this->ue, $this->ecueBu]), [
                'parcours_id' => $this->travauxPublics->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, $this->lignes((int) $this->travauxPublics->id), 'La version reservee devait disparaitre.');
        $this->assertGreaterThan(0, $this->lignes((int) $this->batiment->id) + $this->lignes(CompositionUe::COMMUN), 'Les autres maquettes ne doivent pas etre touchees.');
    }

    public function test_retirer_depuis_la_vue_commune_un_element_reserve_est_refuse_en_nommant_la_maquette(): void
    {
        $this->actingAs($this->acteur)
            ->postJson(route('esbtp.lmd.ue.ecue.store', $this->ue), [
                'name' => 'Topographie appliquee', 'code' => 'TOPO-TIR', 'credit_ecue' => 2,
                'parcours_id' => $this->travauxPublics->id,
            ])->assertOk();
        $topo = ESBTPMatiere::where('code', 'TOPO-TIR')->firstOrFail();

        // Aucune maquette designee : c'est la composition commune qui est visee,
        // et l'element n'y est pas.
        $reponse = $this->actingAs($this->acteur)
            ->deleteJson(route('esbtp.lmd.ue.ecue.destroy', [$this->ue, $topo]))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertStringContainsString('Travaux Publics', $reponse->json('message'));

        $this->assertDatabaseHas('esbtp_ue_matiere', [
            'unite_enseignement_id' => $this->ue->id,
            'matiere_id' => $topo->id,
            'parcours_id' => $this->travauxPublics->id,
        ]);
    }

    public function test_retirer_un_element_commun_depuis_une_seule_maquette_est_refuse(): void
    {
        // L'element est pose en commun, sans version propre a Travaux Publics.
        $this->composition->poser($this->ue, (int) $this->ecueBu->id, [
            'coefficient_ecue' => 1, 'credit_ecue' => 3, 'ordre_bulletin' => 0,
        ]);
        $this->composition->retirer($this->ue, [(int) $this->ecueBu->id], (int) $this->batiment->id);
        $avant = $this->lignes(CompositionUe::COMMUN);
        $this->assertSame(1, $avant);

        $reponse = $this->actingAs($this->acteur)
            ->deleteJson(route('esbtp.lmd.ue.ecue.destroy', [$this->ue, $this->ecueBu]), [
                'parcours_id' => $this->travauxPublics->id,
            ])
            ->assertStatus(422);

        $this->assertStringContainsString('commun', $reponse->json('message'));
        $this->assertSame($avant, $this->lignes(CompositionUe::COMMUN), 'La version commune ne doit pas bouger.');
    }

    /** @return array<int, string> */
    private function vusPar(ESBTPLMDParcours $parcours): array
    {
        return $this->ue->fresh()->getEcuesEffectifs($parcours->id)->pluck('code')->all();
    }

    private function lignes(int $parcoursId): int
    {
        return DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $this->ue->id)
            ->where('matiere_id', $this->ecueBu->id)
            ->where('parcours_id', $parcoursId)
            ->count();
    }

    public function test_un_code_deja_pris_est_refuse_avec_le_nom_de_son_titulaire(): void
    {
        // USAT, septembre 2026 : ce cas remontait en erreur serveur (index unique).
        $this->actingAs($this->acteur)
            ->postJson(route('esbtp.lmd.ue.ecue.store', $this->ue), [
                'name' => 'Autre element',
                'code' => 'ECUE-TIR',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->assertSame(1, ESBTPMatiere::where('code', 'ECUE-TIR')->count());
    }

    public function test_le_code_d_une_matiere_supprimee_est_libere_et_la_creation_aboutit(): void
    {
        // Une matiere supprimee garde son code dans l'index unique : sans
        // liberation, le code resterait bloque par une ligne que personne ne voit.
        $ancienne = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $ancienne->delete();

        $message = $this->actingAs($this->acteur)
            ->postJson(route('esbtp.lmd.ue.ecue.store', $this->ue), [
                'name' => 'Autre element',
                'code' => 'ECUE-TIR',
            ])
            ->assertOk()
            ->json('message');

        // La reponse dit ce qui a ete fait, et ou retrouver l'ancienne matiere.
        $this->assertStringContainsString('libéré', $message);
        $this->assertStringContainsString('ECUE-TIR~suppr-'.$ancienne->id, $message);

        $this->assertSame('ECUE-TIR~suppr-'.$ancienne->id, ESBTPMatiere::withTrashed()->find($ancienne->id)->code);
        $this->assertSame('Autre element', ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail()->name);
    }

    public function test_modifier_un_ecue_vers_un_code_deja_pris_est_refuse(): void
    {
        $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.ecue.update', [$this->ue, $this->ecueBu]), [
                'code' => 'ECUE-TIR',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        // Garder son propre code reste permis.
        $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.ecue.update', [$this->ue, $this->ecueBu]), [
                'code' => 'ECUE-BU',
            ])
            ->assertOk();
    }

    public function test_modifier_un_ecue_vers_le_code_d_une_matiere_supprimee_libere_le_code(): void
    {
        $ancienne = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $ancienne->delete();

        $message = $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.ecue.update', [$this->ue, $this->ecueBu]), [
                'code' => 'ECUE-TIR',
            ])
            ->assertOk()
            ->json('message');

        $this->assertStringContainsString('ECUE-TIR~suppr-'.$ancienne->id, $message);
        $this->assertSame('ECUE-TIR', $this->ecueBu->fresh()->code);
    }

    public function test_le_code_d_une_matiere_bts_est_refuse_en_le_disant(): void
    {
        ESBTPMatiere::create(['name' => 'Topographie BTS', 'code' => 'TOPO-BTS', 'is_active' => true]);

        $erreur = $this->actingAs($this->acteur)
            ->postJson(route('esbtp.lmd.ue.ecue.store', $this->ue), [
                'name' => 'Topographie',
                'code' => 'TOPO-BTS',
            ])
            ->assertStatus(422)
            ->json('errors.code.0');

        // L'onglet « Lier un existant » ne liste pas les matieres BTS : le proposer mentirait.
        $this->assertStringContainsString('cursus BTS', $erreur);
        $this->assertStringNotContainsString('Lier un existant', $erreur);
    }

    public function test_le_formulaire_d_ue_libere_le_code_au_lieu_de_ressusciter_la_matiere(): void
    {
        // Avant : le formulaire restaurait la matiere supprimee sous le nom saisi,
        // notes et historique compris.
        $ancienne = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $ancienne->delete();

        $message = $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.update', $this->ue), [
                'name' => $this->ue->name,
                'type_ue' => 'fondamentale',
                'ecues' => [
                    ['name' => 'Matiere ECUE-BU', 'code' => 'ECUE-BU'],
                    ['name' => 'Nouvel element', 'code' => 'ECUE-TIR'],
                ],
            ])
            ->assertOk()
            ->json('message');

        $this->assertStringContainsString('libéré', $message);

        $archivee = ESBTPMatiere::withTrashed()->find($ancienne->id);
        $this->assertTrue($archivee->trashed());
        $this->assertSame('ECUE-TIR~suppr-'.$ancienne->id, $archivee->code);

        $nouvelle = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $this->assertNotSame($ancienne->id, $nouvelle->id);
        $this->assertSame('Nouvel element', $nouvelle->name);
    }

    public function test_un_formulaire_d_ue_refuse_ne_libere_aucun_code(): void
    {
        $ancienne = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $ancienne->delete();
        ESBTPMatiere::create(['name' => 'Topographie BTS', 'code' => 'TOPO-BTS', 'is_active' => true]);

        $this->actingAs($this->acteur)
            ->putJson(route('esbtp.lmd.ue.update', $this->ue), [
                'name' => $this->ue->name,
                'type_ue' => 'fondamentale',
                'ecues' => [
                    ['name' => 'Nouvel element', 'code' => 'ECUE-TIR'],
                    ['name' => 'Topographie', 'code' => 'TOPO-BTS'],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame('ECUE-TIR', ESBTPMatiere::withTrashed()->find($ancienne->id)->code);
    }

    public function test_l_import_libere_le_code_d_une_matiere_supprimee(): void
    {
        // Avant : l'import levait sur l'index unique, maquette entiere annulee.
        $ancienne = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $ancienne->forceFill(['unite_enseignement_id' => null])->save();
        DB::table('esbtp_ue_matiere')->where('matiere_id', $ancienne->id)->delete();
        $ancienne->delete();

        app(LMDImportService::class)->import($this->maquette('TIR', 'Travaux Publics', 'UE-TIR', 'ECUE-TIR'));

        $this->assertSame('ECUE-TIR~suppr-'.$ancienne->id, ESBTPMatiere::withTrashed()->find($ancienne->id)->code);
        $this->assertNotSame($ancienne->id, ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail()->id);
    }

    private function maquette(string $codeParcours, string $nomParcours, string $codeUe, string $codeEcue): array
    {
        return [
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => $nomParcours, 'code' => $codeParcours, 'credits_licence' => 180],
            'filiere' => ['name' => $nomParcours, 'code' => 'F'.$codeParcours],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => $codeUe,
                'name' => 'Unite '.$codeUe,
                'credit' => 12,
                'niveau_year' => 1,
                'semestre' => 1,
                'ecues' => [[
                    'code' => $codeEcue,
                    'name' => 'Matiere '.$codeEcue,
                    'credit_ecue' => 3,
                ]],
            ]],
        ];
    }
}
