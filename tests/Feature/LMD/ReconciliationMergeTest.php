<?php

namespace Tests\Feature\LMD;

use App\Domain\LMD\Actions\MergeDuplicateEcue;
use App\Domain\LMD\Actions\MergeDuplicateUe;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests Feature de la fusion des doublons UE/ECUE LMD.
 *
 * Couvre :
 *  - dry-run ne commit pas (aucun soft-delete, aucun repointage)
 *  - merge réel repointe les pivots et soft-delete les absorbés
 *  - fusion ECUE refusée si évaluations présentes sans force
 *  - lien parcours partagé après fusion UE
 */
class ReconciliationMergeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        Auth::login($user);

        return $user;
    }

    private function niveau(): ESBTPNiveauEtude
    {
        return ESBTPNiveauEtude::create([
            'name' => 'Licence 2', 'code' => 'L2', 'type' => 'Licence', 'year' => 2, 'is_active' => true,
        ]);
    }

    private function parcours(string $name, string $code): ESBTPLMDParcours
    {
        $domaine = ESBTPLMDDomaine::firstOrCreate(['code' => 'GC'], ['name' => 'Génie Civil', 'is_active' => true]);
        $mention = ESBTPLMDMention::firstOrCreate(['code' => 'GCV'], ['name' => 'Génie Civil', 'domaine_id' => $domaine->id, 'is_active' => true]);

        return ESBTPLMDParcours::create([
            'name' => $name, 'code' => $code, 'mention_id' => $mention->id, 'is_active' => true,
        ]);
    }

    public function test_ue_dry_run_does_not_commit(): void
    {
        $niveau = $this->niveau();
        $ueA = ESBTPUniteEnseignement::create(['name' => 'Physique', 'code' => 'A', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);
        $ueB = ESBTPUniteEnseignement::create(['name' => 'Physique', 'code' => 'B', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);

        $report = app(MergeDuplicateUe::class)->execute($ueA->id, [$ueB->id], ['dry_run' => true]);

        $this->assertTrue($report['success']);
        $this->assertTrue($report['dry_run']);
        $this->assertFalse($report['committed']);
        $this->assertDatabaseHas('esbtp_unites_enseignement', ['id' => $ueB->id, 'deleted_at' => null]);
    }

    public function test_ue_merge_repoints_parcours_pivot_and_soft_deletes(): void
    {
        $niveau = $this->niveau();
        $pA = $this->parcours('Bâtiment & Urbanisme', 'BU');
        $pB = $this->parcours('Travaux Publics', 'TP');

        $ueA = ESBTPUniteEnseignement::create(['name' => 'Physique des matériaux', 'code' => 'BPM3', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);
        $ueB = ESBTPUniteEnseignement::create(['name' => 'Physique des matériaux', 'code' => 'TPPM3', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);

        $pA->unitesEnseignement()->attach($ueA->id, ['semestre' => 3, 'is_optional' => false, 'ordre' => 1]);
        $pB->unitesEnseignement()->attach($ueB->id, ['semestre' => 3, 'is_optional' => false, 'ordre' => 1]);

        $report = app(MergeDuplicateUe::class)->execute($ueA->id, [$ueB->id], ['dry_run' => false]);

        $this->assertTrue($report['committed']);
        // UE absorbée soft-deletée
        $this->assertSoftDeleted('esbtp_unites_enseignement', ['id' => $ueB->id]);
        // La canonique est maintenant liée aux DEUX parcours
        $this->assertDatabaseHas('esbtp_lmd_parcours_ue', ['parcours_id' => $pA->id, 'unite_enseignement_id' => $ueA->id]);
        $this->assertDatabaseHas('esbtp_lmd_parcours_ue', ['parcours_id' => $pB->id, 'unite_enseignement_id' => $ueA->id]);
        // Plus aucun lien vers l'UE absorbée
        $this->assertDatabaseMissing('esbtp_lmd_parcours_ue', ['unite_enseignement_id' => $ueB->id]);
    }

    public function test_ecue_merge_repoints_ue_matiere_pivot(): void
    {
        $niveau = $this->niveau();
        $ue = ESBTPUniteEnseignement::create(['name' => 'UE A', 'code' => 'UEA', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);
        $ueOther = ESBTPUniteEnseignement::create(['name' => 'UE B', 'code' => 'UEB', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);

        $canonical = ESBTPMatiere::create(['name' => 'RDM', 'code' => 'BRDM', 'unite_enseignement_id' => $ue->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);
        $absorbed = ESBTPMatiere::create(['name' => 'RDM', 'code' => 'TPRDM', 'unite_enseignement_id' => $ueOther->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);

        DB::table('esbtp_ue_matiere')->insert(['unite_enseignement_id' => $ue->id, 'matiere_id' => $canonical->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('esbtp_ue_matiere')->insert(['unite_enseignement_id' => $ueOther->id, 'matiere_id' => $absorbed->id, 'created_at' => now(), 'updated_at' => now()]);

        $report = app(MergeDuplicateEcue::class)->execute($canonical->id, [$absorbed->id], ['dry_run' => false]);

        $this->assertTrue($report['committed']);
        $this->assertSoftDeleted('esbtp_matieres', ['id' => $absorbed->id]);
        // Le pivot de l'UE "other" pointe maintenant sur la matière canonique
        $this->assertDatabaseHas('esbtp_ue_matiere', ['unite_enseignement_id' => $ueOther->id, 'matiere_id' => $canonical->id]);
        $this->assertDatabaseMissing('esbtp_ue_matiere', ['matiere_id' => $absorbed->id]);
    }

    public function test_ecue_merge_refused_when_evaluations_present_without_force(): void
    {
        $niveau = $this->niveau();
        $ue = ESBTPUniteEnseignement::create(['name' => 'UE A', 'code' => 'UEA', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);

        $canonical = ESBTPMatiere::create(['name' => 'RDM', 'code' => 'BRDM', 'unite_enseignement_id' => $ue->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);
        $absorbed = ESBTPMatiere::create(['name' => 'RDM', 'code' => 'TPRDM', 'unite_enseignement_id' => $ue->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);

        // Une évaluation rattachée à l'ECUE absorbé → bloque la fusion.
        $annee = ESBTPAnneeUniversitaire::create(['name' => '2025-2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'is_active' => true, 'is_current' => true]);
        $filiere = ESBTPFiliere::create(['name' => 'GC', 'code' => 'GC', 'is_active' => true]);
        $classe = ESBTPClasse::create(['name' => 'L2 A', 'code' => 'L2A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id, 'is_active' => true]);

        DB::table('esbtp_evaluations')->insert([
            'titre' => 'Devoir 1', 'matiere_id' => $absorbed->id, 'classe_id' => $classe->id,
            'type' => 'devoir', 'date_evaluation' => '2025-11-01', 'coefficient' => 1, 'bareme' => 20,
            'annee_universitaire_id' => $annee->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = app(MergeDuplicateEcue::class)->execute($canonical->id, [$absorbed->id], ['dry_run' => false]);

        $this->assertFalse($report['success']);
        $this->assertTrue($report['blocked']);
        $this->assertSame(1, $report['blocking']['evaluations']);
        // L'ECUE absorbé n'a PAS été supprimé.
        $this->assertDatabaseHas('esbtp_matieres', ['id' => $absorbed->id, 'deleted_at' => null]);
    }

    public function test_merge_endpoint_requires_permission_and_returns_dry_run(): void
    {
        $this->admin();
        $niveau = $this->niveau();
        $ueA = ESBTPUniteEnseignement::create(['name' => 'Physique', 'code' => 'A', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);
        $ueB = ESBTPUniteEnseignement::create(['name' => 'Physique', 'code' => 'B', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);

        $response = $this->postJson(route('esbtp.lmd.reconciliation.merge'), [
            'type' => 'ue',
            'canonical_id' => $ueA->id,
            'absorbed_ids' => [$ueB->id],
            'dry_run' => true,
        ]);

        $response->assertOk()->assertJson(['success' => true, 'dry_run' => true, 'committed' => false]);
        $this->assertDatabaseHas('esbtp_unites_enseignement', ['id' => $ueB->id, 'deleted_at' => null]);
    }
}
