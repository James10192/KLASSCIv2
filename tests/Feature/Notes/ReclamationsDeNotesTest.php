<?php

namespace Tests\Feature\Notes;

use App\Domain\Notes\Reclamations\ReglagesReclamations;
use App\Enums\StatutReclamationNote;
use App\Helpers\InstallationHelper;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPReclamationNote;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Réclamations de notes : l'élève conteste avec la photo de sa copie,
 * l'enseignant de l'évaluation donne son avis, le personnel qui porte
 * « notes.reclamations.traiter » tranche.
 */
class ReclamationsDeNotesTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private User $eleveUser;

    private ESBTPEtudiant $eleve;

    private User $enseignant;

    private User $agent;

    private ESBTPEvaluation $evaluation;

    private ESBTPNote $note;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['notes.view_own', 'notes.reclamations.create_own', 'notes.reclamations.traiter', 'identity.teach'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        foreach (['etudiant', 'enseignant', 'superAdmin', 'secretaire'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        Cache::flush();
        InstallationHelper::flushCachedStatus();
        Storage::fake('local');

        $this->monterLaClasse();
        $this->annee->update(['is_current' => true]);

        $this->enseignant = User::factory()->create(['is_active' => true]);
        $this->enseignant->assignRole('enseignant');
        $this->enseignant->givePermissionTo('identity.teach');

        // Un agent quelconque : c'est la permission qui compte, pas le rôle.
        $this->agent = User::factory()->create(['is_active' => true]);
        $this->agent->assignRole('secretaire');
        $this->agent->givePermissionTo('notes.reclamations.traiter');

        $this->eleveUser = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->eleveUser->assignRole('etudiant');
        $this->eleveUser->givePermissionTo(['notes.view_own', 'notes.reclamations.create_own']);
        $this->eleve = $this->etudiantInscrit();
        $this->eleve->update(['user_id' => $this->eleveUser->id]);

        $matiere = $this->matiereConfiguree();
        $this->evaluation = $this->evaluationDe($matiere);
        $this->evaluation->update(['enseignant_id' => $this->enseignant->id]);
        $this->noter($this->eleve, $this->evaluation, 9.5);
        $this->note = ESBTPNote::where('etudiant_id', $this->eleve->id)->where('evaluation_id', $this->evaluation->id)->firstOrFail();
    }

    public function test_l_eleve_conteste_avec_photo_et_l_enseignant_et_le_personnel_habilite_sont_prevenus(): void
    {
        $reponse = $this->deposer();

        $reponse->assertCreated()->assertJsonPath('reclamation.statut', StatutReclamationNote::SOUMISE->value);

        $r = ESBTPReclamationNote::firstOrFail();
        $this->assertSame($this->enseignant->id, (int) $r->enseignant_id, 'L\'enseignant vient de l\'évaluation.');
        $this->assertSame(9.5, (float) $r->note_initiale);
        Storage::disk('local')->assertExists($r->photo_path);
        $this->assertTrue(Notification::where('user_id', $this->enseignant->id)->exists());
        $this->assertTrue(Notification::where('user_id', $this->agent->id)->exists());
    }

    public function test_sans_photo_la_reclamation_est_refusee(): void
    {
        $this->actingAs($this->eleveUser)
            ->post(route('esbtp.mes-reclamations.store'), ['note_id' => $this->note->id, 'motif' => 'Exercice 2 non compté.'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('photo');

        $this->assertSame(0, ESBTPReclamationNote::count());
    }

    public function test_une_note_hors_delai_ne_se_conteste_plus(): void
    {
        Setting::updateOrCreate(['key' => ReglagesReclamations::REGLAGE_DELAI_JOURS], ['value' => '15', 'type' => 'integer', 'group' => 'scolarite']);
        Cache::flush();
        ESBTPNote::whereKey($this->note->id)->update(['updated_at' => now()->subDays(16)]);

        $this->deposer()->assertStatus(422)->assertJsonValidationErrors('note_id');
    }

    public function test_une_seule_reclamation_ouverte_par_note(): void
    {
        $this->deposer()->assertCreated();
        $this->deposer()->assertStatus(422)->assertJsonValidationErrors('note_id');
    }

    public function test_on_ne_conteste_pas_la_note_d_un_autre_eleve(): void
    {
        $autre = $this->etudiantInscrit();
        $this->noter($autre, $this->evaluation, 14);
        $noteAutre = ESBTPNote::where('etudiant_id', $autre->id)->firstOrFail();

        $this->deposer($noteAutre->id)->assertStatus(422);
    }

    public function test_l_enseignant_propose_sans_toucher_la_note(): void
    {
        $this->deposer()->assertCreated();
        $r = ESBTPReclamationNote::firstOrFail();

        $this->actingAs($this->enseignant)
            ->postJson(route('esbtp.reclamations-notes.avis', $r->id), ['avis' => 'corriger', 'note_proposee' => 12, 'commentaire' => 'Exercice 2 juste, oubli au report.'])
            ->assertOk()->assertJsonPath('reclamation.statut', StatutReclamationNote::AVIS_DONNE->value);

        $this->assertSame(9.5, (float) $this->note->fresh()->note, 'L\'avis ne corrige rien : seul le personnel habilité applique.');
    }

    public function test_un_autre_enseignant_ne_voit_pas_la_reclamation(): void
    {
        $this->deposer()->assertCreated();
        $r = ESBTPReclamationNote::firstOrFail();
        $intrus = User::factory()->create();
        $intrus->assignRole('enseignant');
        $intrus->givePermissionTo('identity.teach');

        $this->actingAs($intrus)
            ->postJson(route('esbtp.reclamations-notes.avis', $r->id), ['avis' => 'confirmer', 'commentaire' => 'Je ne suis pas le correcteur.'])
            ->assertForbidden();
    }

    public function test_le_personnel_habilite_accepte_et_la_note_est_corrigee(): void
    {
        $this->deposer()->assertCreated();
        $r = ESBTPReclamationNote::firstOrFail();

        $this->actingAs($this->agent)
            ->postJson(route('esbtp.reclamations-notes.decision', $r->id), ['decision' => 'accepter', 'note_finale' => 12])
            ->assertOk()->assertJsonPath('reclamation.statut', StatutReclamationNote::ACCEPTEE->value);

        $this->assertSame(12.0, (float) $this->note->fresh()->note);
        $this->assertSame($this->agent->id, (int) $this->note->fresh()->updated_by);
        $this->assertTrue(Notification::where('user_id', $this->eleveUser->id)->where('title', 'Réclamation acceptée')->exists());
    }

    public function test_maintenir_la_note_exige_un_message(): void
    {
        $this->deposer()->assertCreated();
        $r = ESBTPReclamationNote::firstOrFail();

        $this->actingAs($this->agent)
            ->postJson(route('esbtp.reclamations-notes.decision', $r->id), ['decision' => 'refuser'])
            ->assertStatus(422)->assertJsonValidationErrors('commentaire');

        $this->actingAs($this->agent)
            ->postJson(route('esbtp.reclamations-notes.decision', $r->id), ['decision' => 'refuser', 'commentaire' => 'La copie confirme 9,5 : exercice 2 incomplet.'])
            ->assertOk()->assertJsonPath('reclamation.statut', StatutReclamationNote::REJETEE->value);

        $this->assertSame(9.5, (float) $this->note->fresh()->note);
    }

    public function test_l_enseignant_ne_tranche_pas(): void
    {
        $this->deposer()->assertCreated();
        $r = ESBTPReclamationNote::firstOrFail();

        $this->actingAs($this->enseignant)
            ->postJson(route('esbtp.reclamations-notes.decision', $r->id), ['decision' => 'accepter', 'note_finale' => 15])
            ->assertForbidden();
    }

    public function test_une_note_au_dessus_du_bareme_est_refusee(): void
    {
        $this->deposer()->assertCreated();
        $r = ESBTPReclamationNote::firstOrFail();

        $this->actingAs($this->agent)
            ->postJson(route('esbtp.reclamations-notes.decision', $r->id), ['decision' => 'accepter', 'note_finale' => 25])
            ->assertStatus(422);
        $this->assertTrue($r->fresh()->estOuverte());
    }

    public function test_sans_porteur_de_la_permission_les_super_administrateurs_sont_prevenus(): void
    {
        $this->agent->revokePermissionTo('notes.reclamations.traiter');
        $chef = User::factory()->create(['is_active' => true]);
        $chef->assignRole('superAdmin');

        $this->deposer()->assertCreated();

        $this->assertTrue(Notification::where('user_id', $chef->id)->exists());
    }

    public function test_les_deux_pages_s_affichent(): void
    {
        $this->deposer()->assertCreated();

        $this->actingAs($this->eleveUser)->get(route('esbtp.mes-reclamations.index', ['note' => $this->note->id]))
            ->assertOk()->assertSee('Mes réclamations');

        $this->actingAs($this->agent)->get(route('esbtp.reclamations-notes.index'))
            ->assertOk()->assertSee('Réclamations de notes');

        $this->actingAs($this->enseignant)->get(route('esbtp.reclamations-notes.index'))
            ->assertOk();
    }

    private function deposer(?int $noteId = null)
    {
        return $this->actingAs($this->eleveUser)->post(route('esbtp.mes-reclamations.store'), [
            'note_id' => $noteId ?? $this->note->id,
            'motif' => 'À l\'exercice 2 ma réponse est juste mais n\'a pas été comptée.',
            'photo' => UploadedFile::fake()->image('copie.jpg', 800, 1100),
        ], ['Accept' => 'application/json']);
    }
}
