<?php

namespace Tests\Feature\Evaluation;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La fenêtre des notes LMD envoie la période sous la forme « 1 ». Depuis que
 * la création d'une évaluation contrôle la période (semestre1 / semestre2),
 * elle était refusée : « La valeur sélectionnée pour periode est invalide ».
 * Constaté à ESBTP Abidjan en octobre 2026 : aucune évaluation LMD ne se créait.
 */
class PeriodeDeLaFenetreLmdTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_numero_seul_s_ecrit_semestre_n(): void
    {
        $this->assertSame('semestre1', ESBTPEvaluation::periodeSaisie('1'));
        $this->assertSame('semestre3', ESBTPEvaluation::periodeSaisie('3'));
        $this->assertSame('semestre2', ESBTPEvaluation::periodeSaisie('semestre2'));
        $this->assertSame('annuel', ESBTPEvaluation::periodeSaisie('annuel'));
        $this->assertNull(ESBTPEvaluation::periodeSaisie(null));
    }

    public function test_une_classe_lmd_propose_les_semestres_de_son_niveau(): void
    {
        $niveau = new ESBTPNiveauEtude(['year' => 2, 'type' => 'Licence']);
        $lmd = new ESBTPClasse(['systeme_academique' => 'LMD']);
        $lmd->setRelation('niveau', $niveau);
        $bts = new ESBTPClasse(['systeme_academique' => 'BTS']);
        $bts->setRelation('niveau', $niveau);

        $this->assertSame(['semestre1', 'semestre2', 'semestre3', 'semestre4'], array_keys(ESBTPEvaluation::periodesPourClasse($lmd)));
        $this->assertSame(['semestre1', 'semestre2'], array_keys(ESBTPEvaluation::periodesPourClasse($bts)));
        $this->assertSame(['semestre1', 'semestre2'], array_keys(ESBTPEvaluation::periodesPourClasse(null)));
    }

    public function test_la_fenetre_cree_l_evaluation_avec_la_periode_un(): void
    {
        foreach (['admin.access', 'evaluations.create'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $role = Role::findOrCreate('superAdmin', 'web');
        $role->givePermissionTo(['admin.access', 'evaluations.create']);
        $user = User::factory()->create();
        $user->assignRole($role);

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create(['is_active' => true]);

        $this->actingAs($user)->postJson(route('esbtp.evaluations.store'), [
            'titre' => 'Examen SEMESTRE1 — test',
            'type' => 'examen',
            'periode' => '1',
            'date_evaluation' => '2026-04-30',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'bareme' => 20,
            'coefficient' => 1,
            'embed' => 1,
            'is_published' => 1,
        ])->assertJsonMissingValidationErrors('periode');

        $this->assertSame('semestre1', ESBTPEvaluation::where('titre', 'Examen SEMESTRE1 — test')->value('periode'));
    }
}
