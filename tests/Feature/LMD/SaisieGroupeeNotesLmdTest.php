<?php

namespace Tests\Feature\LMD;

use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Saisie groupée des notes LMD : la virgule est lue, la case « absent » est
 * validée puis enregistrée, et une valeur absurde est refusée avant écriture.
 */
class SaisieGroupeeNotesLmdTest extends TestCase
{
    use DatabaseTransactions;

    public function test_une_note_a_virgule_et_un_absent_sont_enregistres(): void
    {
        [$evaluation, $etudiants] = $this->evaluationLmd();

        $this->post(route('esbtp.lmd.notes.save-bulk'), [
            'evaluation_id' => $evaluation->id,
            'notes' => [
                ['etudiant_id' => $etudiants[0], 'note' => '12,5'],
                ['etudiant_id' => $etudiants[1], 'note' => '', 'is_absent' => '1'],
            ],
        ])->assertRedirect(route('esbtp.lmd.notes.index', [
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ]));

        $this->assertSame(12.5, (float) ESBTPNote::where('evaluation_id', $evaluation->id)->where('etudiant_id', $etudiants[0])->value('note'));
        $absent = ESBTPNote::where('evaluation_id', $evaluation->id)->where('etudiant_id', $etudiants[1])->first();
        $this->assertTrue((bool) $absent->is_absent);
        $this->assertSame(0.0, (float) $absent->note);
    }

    public function test_une_case_absent_mal_renseignee_est_refusee_sans_rien_ecrire(): void
    {
        [$evaluation, $etudiants] = $this->evaluationLmd();

        $this->post(route('esbtp.lmd.notes.save-bulk'), [
            'evaluation_id' => $evaluation->id,
            'notes' => [['etudiant_id' => $etudiants[0], 'note' => '10', 'is_absent' => 'peut-etre']],
        ])->assertSessionHasErrors('notes.0.is_absent');

        $this->assertSame(0, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
    }

    public function test_une_note_au_dessus_du_bareme_est_refusee(): void
    {
        [$evaluation, $etudiants] = $this->evaluationLmd();

        $this->post(route('esbtp.lmd.notes.save-bulk'), [
            'evaluation_id' => $evaluation->id,
            'notes' => [['etudiant_id' => $etudiants[0], 'note' => '25']],
        ])->assertSessionHas('error');

        $this->assertSame(0, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
    }

    public function test_lecteur_lmd_ne_peut_pas_enregistrer_de_notes(): void
    {
        [$evaluation, $etudiants] = $this->evaluationLmd();

        Permission::findOrCreate('lmd.notes.view', 'web');
        Permission::findOrCreate('lmd.notes.manage', 'web');
        $lecteur = User::withoutEvents(fn () => User::factory()->create());
        $lecteur->givePermissionTo('lmd.notes.view');
        $this->actingAs($lecteur);

        $this->post(route('esbtp.lmd.notes.save-bulk'), [
            'evaluation_id' => $evaluation->id,
            'notes' => [['etudiant_id' => $etudiants[0], 'note' => '12']],
        ])->assertForbidden();

        $this->assertSame(0, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
    }

    /** @return array{0: ESBTPEvaluation, 1: array<int, int>} */
    private function evaluationLmd(): array
    {
        $this->withoutMiddleware(PaywallMiddleware::class);
        Role::findOrCreate('superAdmin', 'web');
        // La route LMD demande AUSSI l'accès au module, avant le contrôleur.
        // Les deux permissions doivent être explicites pour les scénarios positifs.
        Permission::findOrCreate('module.lmd.access', 'web');
        Permission::findOrCreate('lmd.notes.manage', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');
        // Le rôle superAdmin n'accorde aucun passe-droit implicite : ce
        // scénario de saisie autorisée doit porter sa permission métier.
        $user->givePermissionTo(['module.lmd.access', 'lmd.notes.manage']);
        $this->actingAs($user);

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        // Le modèle déduit le système du niveau à l'enregistrement : on le pose après.
        ESBTPClasse::whereKey($classe->id)->update(['systeme_academique' => 'LMD']);
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE saisie groupée', 'code' => 'UESG'.random_int(1000, 9999),
            'credit' => 6, 'semestre' => 1, 'is_active' => true,
        ]);
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => $ue->id]);

        $etudiants = ESBTPInscription::factory()->count(2)->create([
            'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
            'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_etude_id,
            'status' => 'active', 'workflow_step' => 'etudiant_cree',
        ])->pluck('etudiant_id')->map(fn ($id) => (int) $id)->all();

        // La saisie groupée travaille sur une évaluation déjà créée :
        // son fixture ne doit pas déclencher la vérification du planning LMD
        // (qui relève des tests de création/affectation d'évaluations).
        $evaluation = ESBTPEvaluation::withoutEvents(fn () => ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $annee->id, 'bareme' => 20, 'periode' => 'semestre1',
        ]));

        return [$evaluation, $etudiants];
    }
}
