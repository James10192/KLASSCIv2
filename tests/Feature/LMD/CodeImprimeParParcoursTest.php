<?php

namespace Tests\Feature\LMD;

use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\CodeDeMaquette;
use App\Services\LMD\ConflitDeMaquette;
use App\Services\LMD\LMDImportService;
use App\Services\LMD\ParcoursUeSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Un code de maquette n'est unique que DANS un parcours (USAT, septembre 2026).
 *
 * Productions Animales et Productions Vegetales impriment tous deux AGR2103 et
 * AGR21031, pour des enseignements totalement differents : « Genetique
 * animale » d'un cote, « Genetique vegetale » de l'autre. Un releve ne montre
 * qu'un parcours. L'ecole doit pouvoir saisir les deux, sans que l'un renomme
 * l'autre — ce que faisait l'import, en silence.
 */
class CodeImprimeParParcoursTest extends TestCase
{
    use RefreshDatabase;

    private LMDImportService $import;

    protected function setUp(): void
    {
        parent::setUp();

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->import = app(LMDImportService::class);
        $this->import->import($this->maquette('LPV', 'Productions Vegetales', 'vegetale'));
    }

    public function test_le_code_imprime_s_arrete_au_tilde(): void
    {
        $this->assertSame('AGR21031', CodeDeMaquette::affiche('AGR21031~LPA'));
        $this->assertSame('AGR21031', CodeDeMaquette::affiche('AGR21031'));
        $this->assertSame('LPA', CodeDeMaquette::suffixe('AGR21031~LPA~2'));
        $this->assertNull(CodeDeMaquette::suffixe('AGR21031~suppr-12'), "Le suffixe d'archivage n'est pas un parcours.");
        $this->assertTrue(CodeDeMaquette::memeIntitule('Génétique  végétale', 'genetique vegetale'));
        $this->assertFalse(CodeDeMaquette::memeIntitule('Génétique animale', 'Génétique végétale'));
    }

    public function test_sans_le_dire_l_import_refuse_de_renommer_l_element_de_l_autre_parcours(): void
    {
        try {
            $this->import->import($this->maquette('LPA', 'Productions Animales', 'animale'));
            $this->fail("L'import aurait renommé la Génétique végétale en Génétique animale.");
        } catch (ConflitDeMaquette $e) {
            $types = collect($e->conflits())->pluck('type')->unique()->values()->all();
            $this->assertContains('ECUE', $types);
            $this->assertStringContainsString('propre_au_parcours', $e->conflits()[0]['detail']);
        }

        $this->assertSame('Génétique vegetale', ESBTPMatiere::where('code', 'AGR21031')->value('name'));
    }

    public function test_un_intitule_d_ue_different_sans_le_dire_est_refuse(): void
    {
        try {
            $this->import->import($this->maquette('LPA', 'Productions Animales', 'animale', nomUe: 'PHYSIOLOGIE ANIMALE'));
            $this->fail("L'import aurait partagé une UE d'intitulé différent.");
        } catch (ConflitDeMaquette $e) {
            $this->assertContains('UE', collect($e->conflits())->pluck('type')->all());
        }
    }

    public function test_une_ue_propre_au_parcours_imprime_le_meme_code_sans_toucher_l_autre(): void
    {
        $this->import->import($this->maquette('LPA', 'Productions Animales', 'animale', propre: true));
        // Rejouee, la maquette ne cree rien de plus.
        $this->import->import($this->maquette('LPA', 'Productions Animales', 'animale', propre: true));

        $lpa = ESBTPLMDParcours::where('code', 'LPA')->firstOrFail();
        $lpv = ESBTPLMDParcours::where('code', 'LPV')->firstOrFail();

        $this->assertSame(2, ESBTPUniteEnseignement::where('code', 'like', 'AGR2103%')->count());
        $ueAnimale = ESBTPUniteEnseignement::where('code', 'AGR2103~LPA')->firstOrFail();
        $ueVegetale = ESBTPUniteEnseignement::where('code', 'AGR2103')->firstOrFail();

        $this->assertSame('AGR2103', $ueAnimale->code_affiche);
        $animales = $ueAnimale->getEcuesEffectifs($lpa->id)->sortBy('code');
        $this->assertSame(['Génétique animale', 'Reproduction animale et biotechnologies'], $animales->pluck('name')->values()->all());
        $this->assertSame(['AGR21031', 'AGR21032'], $animales->pluck('code_affiche')->values()->all());
        $this->assertSame(
            ['Génétique vegetale', 'Reproduction vegetale et biotechnologies'],
            $ueVegetale->fresh()->getEcuesEffectifs($lpv->id)->sortBy('code')->pluck('name')->values()->all()
        );

        $this->assertSame(1, ESBTPMatiere::where('code', 'AGR21031~LPA')->count());
        $this->assertSame('Génétique vegetale', ESBTPMatiere::where('code', 'AGR21031')->value('name'));
    }

    public function test_un_parcours_n_imprime_jamais_deux_fois_le_meme_code(): void
    {
        $this->import->import($this->maquette('LPA', 'Productions Animales', 'animale', propre: true));
        $lpa = ESBTPLMDParcours::where('code', 'LPA')->firstOrFail();
        $ueVegetale = ESBTPUniteEnseignement::where('code', 'AGR2103')->firstOrFail();

        $this->expectException(ValidationException::class);
        app(ParcoursUeSyncService::class)->sync($lpa, [[
            'id' => $ueVegetale->id, 'semestres' => [3], 'is_optional' => false, 'ordre' => 0,
        ]], detachMissing: false);
    }

    public function test_l_ecran_cree_une_ue_propre_au_parcours_et_ses_ecue(): void
    {
        $acteur = $this->acteur();
        $lpa = $this->parcoursSansMaquette('LPA', 'Productions Animales');

        // Sans la case, le code déjà pris est refusé, avec la marche à suivre.
        $this->actingAs($acteur)->postJson(route('esbtp.lmd.ue.store'), [
            'name' => 'AMELIORATION GENETIQUE ET REPRODUCTION', 'code' => 'AGR2103', 'credit' => 4, 'type_ue' => 'fondamentale',
        ])->assertStatus(422)->assertJsonValidationErrors('code');

        // Le tilde est réservé.
        $this->actingAs($acteur)->postJson(route('esbtp.lmd.ue.store'), [
            'name' => 'X', 'code' => 'AGR~2103', 'credit' => 4, 'type_ue' => 'fondamentale',
        ])->assertStatus(422)->assertJsonValidationErrors('code');

        $this->actingAs($acteur)->postJson(route('esbtp.lmd.ue.store'), [
            'name' => 'AMELIORATION GENETIQUE ET REPRODUCTION', 'code' => 'AGR2103', 'credit' => 4, 'type_ue' => 'fondamentale',
            'propre_au_parcours' => 1, 'parcours_id' => $lpa->id, 'semestre' => 3,
        ])->assertOk();

        $ue = ESBTPUniteEnseignement::where('code', 'AGR2103~LPA')->firstOrFail();
        $this->assertTrue(DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->where('parcours_id', $lpa->id)->where('semestre', 3)->exists());

        // La modale ECUE : le code déjà pris par l'autre parcours reçoit une clé propre.
        $this->actingAs($acteur)->postJson(route('esbtp.lmd.ue.ecue.store', $ue), [
            'name' => 'Génétique animale', 'code' => 'AGR21031', 'credit_ecue' => 2, 'coefficient_ecue' => 2, 'parcours_id' => $lpa->id,
        ])->assertOk();
        $animale = ESBTPMatiere::where('code', 'AGR21031~LPA')->firstOrFail();
        $this->assertSame('Génétique animale', $animale->name);
        $this->assertSame('Génétique vegetale', ESBTPMatiere::where('code', 'AGR21031')->value('name'));

        // La liste et la modale d'édition montrent le code imprimé.
        $liste = $this->actingAs($acteur)->getJson(route('esbtp.lmd.ue.index', ['format' => 'json', 'parcours_id' => $lpa->id]))->json('ues.0');
        $this->assertSame('AGR2103', $liste['code']);
        $this->assertSame('LPA', $liste['propre_a']);
        $this->assertSame('AGR21031', $liste['ecues'][0]['code']);

        // Modifier l'UE en renvoyant le code imprimé lui rend sa clé.
        $this->actingAs($acteur)->putJson(route('esbtp.lmd.ue.update', $ue), [
            'name' => 'AMELIORATION GENETIQUE ET REPRODUCTION ANIMALE', 'code' => 'AGR2103', 'credit' => 4, 'type_ue' => 'fondamentale',
        ])->assertOk();
        $this->assertSame('AGR2103~LPA', $ue->fresh()->code);

        // Modifier l'ECUE en renvoyant son code imprimé ne touche pas l'autre.
        $this->actingAs($acteur)->putJson(route('esbtp.lmd.ue.ecue.update', [$ue, $animale]), [
            'name' => 'Génétique animale appliquée', 'code' => 'AGR21031', 'parcours_id' => $lpa->id,
        ])->assertOk();
        $this->assertSame('AGR21031~LPA', $animale->fresh()->code);
        $this->assertSame('Génétique vegetale', ESBTPMatiere::where('code', 'AGR21031')->value('name'));
    }

    public function test_le_formulaire_d_ue_refuse_de_renommer_l_element_d_une_autre_unite(): void
    {
        $acteur = $this->acteur();
        $autre = ESBTPUniteEnseignement::create([
            'name' => 'Autre unité', 'code' => 'AUT1', 'credit' => 4, 'type_ue' => 'fondamentale', 'semestre' => 1, 'is_active' => true,
        ]);

        $this->actingAs($acteur)->putJson(route('esbtp.lmd.ue.update', $autre), [
            'name' => 'Autre unité', 'code' => 'AUT1', 'credit' => 4, 'type_ue' => 'fondamentale',
            'ecues' => [['name' => 'Génétique animale', 'code' => 'AGR21031', 'credit_ecue' => 2]],
        ])->assertStatus(422)->assertJsonValidationErrors('ecues.0.code');

        $this->assertSame('Génétique vegetale', ESBTPMatiere::where('code', 'AGR21031')->value('name'));
    }

    private function acteur(): User
    {
        foreach (['admin.access', 'module.lmd.access', 'lmd.structure.view', 'lmd.structure.manage', 'lmd.structure.delete'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole(Role::findOrCreate('superAdmin', 'web'));
        $acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $acteur->givePermissionTo(['admin.access', 'module.lmd.access', 'lmd.structure.view', 'lmd.structure.manage', 'lmd.structure.delete']);
        $this->withoutMiddleware(PaywallMiddleware::class);

        return $acteur;
    }

    private function parcoursSansMaquette(string $code, string $nom): ESBTPLMDParcours
    {
        $lpv = ESBTPLMDParcours::where('code', 'LPV')->firstOrFail();

        return ESBTPLMDParcours::create([
            'name' => $nom, 'code' => $code, 'mention_id' => $lpv->mention_id,
            'credits_licence' => 180, 'credits_master' => 120, 'is_active' => true,
        ]);
    }

    private function maquette(string $codeParcours, string $nomParcours, string $variante, bool $propre = false, ?string $nomUe = null): array
    {
        $ue = [
            'code' => 'AGR2103',
            'name' => $nomUe ?? 'AMELIORATION GENETIQUE ET REPRODUCTION',
            'type_ue' => 'fondamentale',
            'credit' => 4,
            'niveau_year' => 2,
            'semestre' => 3,
            'ecues' => [
                ['code' => 'AGR21031', 'name' => 'Génétique '.$variante, 'credit_ecue' => 2],
                ['code' => 'AGR21032', 'name' => 'Reproduction '.$variante.' et biotechnologies', 'credit_ecue' => 2],
            ],
        ];
        if ($propre) {
            $ue['propre_au_parcours'] = true;
        }

        return [
            'domaine' => ['name' => 'Sciences Agronomiques', 'code' => 'SA'],
            'mention' => ['name' => 'Productions Végétales et Animales', 'code' => 'PVA'],
            'parcours' => ['name' => $nomParcours, 'code' => $codeParcours, 'credits_licence' => 180],
            'filiere' => ['name' => $nomParcours, 'code' => 'F'.$codeParcours],
            'niveaux' => [['name' => 'Licence 2', 'year' => 2]],
            'ues' => [$ue],
        ];
    }
}
