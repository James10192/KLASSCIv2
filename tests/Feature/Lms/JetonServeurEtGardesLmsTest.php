<?php

namespace Tests\Feature\Lms;

use App\Models\ESBTPNote;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use App\Support\Lms\JetonServeurLms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Lot 1 de la liaison LMS : le login du LMS donne un jeton a TOUS les
 * utilisateurs, eleves compris. Les routes d'ecriture doivent donc verifier
 * qui ecrit, et le jeton serveur du LMS ne vaut que par ses droits.
 */
class JetonServeurEtGardesLmsTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private \App\Models\ESBTPEvaluation $evaluation;

    private \App\Models\ESBTPEtudiant $eleve;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['identity.student', 'identity.teach', 'admin.access', 'identity.coordinate', 'identity.direct_studies'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->monterLaClasse();
        // Le garde « installed » redirige tant qu'aucun superAdmin n'existe.
        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        User::findOrFail(1)->assignRole('superAdmin');
        \App\Helpers\InstallationHelper::flushCachedStatus();
        $this->annee->update(['is_current' => true]);
        $this->evaluation = $this->evaluationDe($this->matiereConfiguree());
        $this->eleve = $this->etudiantInscrit();
    }

    /** @test */
    public function un_eleve_connecte_au_lms_ne_peut_pas_ecrire_de_notes(): void
    {
        $eleve = User::factory()->create();
        $eleve->givePermissionTo('identity.student');

        $this->ecrireNote($this->jeton($eleve, ['lms:access']), 20)->assertForbidden();

        $this->assertSame(0, ESBTPNote::count());
    }

    /** @test */
    public function l_enseignant_de_l_evaluation_ecrit_et_le_commentaire_est_garde(): void
    {
        $prof = User::factory()->create();
        $prof->givePermissionTo('identity.teach');
        $this->evaluation->update(['enseignant_id' => $prof->id]);
        $this->noter($this->eleve, $this->evaluation, 8);
        ESBTPNote::query()->update(['commentaire' => 'Copie rendue en retard']);

        $this->ecrireNote($this->jeton($prof, ['lms:access']), 14)->assertOk();

        $note = ESBTPNote::firstOrFail();
        $this->assertSame(14.0, (float) $note->note);
        $this->assertSame('Copie rendue en retard', $note->commentaire);
    }

    /** @test */
    public function un_enseignant_etranger_a_l_evaluation_est_refuse(): void
    {
        $prof = User::factory()->create();
        $prof->givePermissionTo('identity.teach');

        $this->ecrireNote($this->jeton($prof, ['lms:access']), 14)->assertForbidden();
    }

    /** @test */
    public function le_jeton_serveur_ecrit_seulement_avec_le_droit_notes(): void
    {
        $service = User::factory()->create(['username' => JetonServeurLms::COMPTE]);

        $this->ecrireNote($this->jeton($service, [JetonServeurLms::SERVEUR, JetonServeurLms::LECTURE]), 12)->assertForbidden();
        $this->ecrireNote($this->jeton($service, [JetonServeurLms::SERVEUR, JetonServeurLms::NOTES]), 12)->assertOk();
    }

    /** @test */
    public function un_jeton_etoile_ne_passe_pas_pour_le_serveur(): void
    {
        $quelconque = User::factory()->create();

        $this->ecrireNote($this->jeton($quelconque, ['*']), 12)->assertForbidden();
    }

    /** @test */
    public function un_eleve_ne_peut_ni_pointer_une_visio_ni_envoyer_un_rappel(): void
    {
        $eleve = User::factory()->create();
        $eleve->givePermissionTo('identity.student');
        $seance = $this->seance();
        $jeton = $this->jeton($eleve, ['lms:access']);

        $this->withToken($jeton)->postJson('/api/lms/attendances/from-video-session', [
            'seance_cours_id' => $seance->id, 'date' => now()->toDateString(),
            'attendances' => [['etudiant_id' => $this->eleve->id, 'statut' => 'present',
                'joined_at' => now()->format('Y-m-d H:i:s'), 'left_at' => now()->format('Y-m-d H:i:s'), 'duration_minutes' => 60]],
        ])->assertForbidden();

        $this->withToken($jeton)->postJson('/api/lms/notifications/send-session-reminder', [
            'seance_id' => $seance->id,
        ])->assertForbidden();
    }

    /** @test */
    public function la_cli_cree_liste_et_revoque_un_jeton_serveur(): void
    {
        $admin = User::factory()->create();
        $cli = $this->jeton($admin, ['cli:admin']);

        $cree = $this->withToken($cli)->postJson('/api/cli/lms/jeton-serveur', ['droits' => ['lms:lecture', 'lms:notes']])
            ->assertOk()->json('data');

        $this->assertNotEmpty($cree['jeton']);
        $this->assertSame(['lms:lecture', 'lms:notes'], $cree['droits']);
        $service = User::where('username', JetonServeurLms::COMPTE)->firstOrFail();
        $this->assertSame(['lms:serveur', 'lms:lecture', 'lms:notes'], $service->tokens()->first()->abilities);

        $liste = $this->withToken($cli)->getJson('/api/cli/lms/jetons-serveur')->assertOk()->json('data.jetons');
        $this->assertCount(1, $liste);
        $this->assertArrayNotHasKey('jeton', $liste[0]);

        $this->withToken($cli)->deleteJson('/api/cli/lms/jeton-serveur/'.$cree['jeton_id'])->assertOk();
        $this->assertSame(0, $service->tokens()->count());
    }

    /** @test */
    public function la_cli_refuse_un_jeton_sans_cli_admin(): void
    {
        $lecteur = User::factory()->create();

        $this->withToken($this->jeton($lecteur, ['cli:read']))->postJson('/api/cli/lms/jeton-serveur')->assertForbidden();
        $this->assertNull(User::where('username', JetonServeurLms::COMPTE)->first());
    }

    /** @test */
    public function le_jeton_serveur_a_sa_propre_enveloppe_de_debit(): void
    {
        $service = User::factory()->create(['username' => JetonServeurLms::COMPTE]);
        $service->withAccessToken($service->createToken('t', [JetonServeurLms::SERVEUR, JetonServeurLms::LECTURE])->accessToken);
        $requete = Request::create('/api/lms/classes');
        $requete->setUserResolver(fn () => $service);

        $limite = RateLimiter::limiter('api')($requete);

        $this->assertSame(600, $limite->maxAttempts);
        $this->assertStringStartsWith('lms-serveur:', $limite->key);
    }

    /** @test */
    public function la_recherche_d_utilisateur_ne_partage_plus_dix_appels_par_ip(): void
    {
        $a = Request::create('/api/lms/auth/check-user', 'POST', ['identifier' => 'eleve.a']);
        $b = Request::create('/api/lms/auth/check-user', 'POST', ['identifier' => 'eleve.b']);

        [$parIdentifiantA, $parIpA] = RateLimiter::limiter('lms-discovery')($a);
        [$parIdentifiantB, $parIpB] = RateLimiter::limiter('lms-discovery')($b);

        $this->assertNotSame($parIdentifiantA->key, $parIdentifiantB->key);
        $this->assertSame($parIpA->key, $parIpB->key);
        $this->assertSame(120, $parIpA->maxAttempts);
    }

    /** @param array<int,string> $droits */
    private function jeton(User $utilisateur, array $droits): string
    {
        return $utilisateur->createToken('test', $droits)->plainTextToken;
    }

    private function ecrireNote(string $jeton, float $note): \Illuminate\Testing\TestResponse
    {
        // Le garde Sanctum garde l'utilisateur d'une requete a l'autre dans un
        // meme test : sans cet oubli, le second jeton serait ignore.
        $this->app['auth']->forgetGuards();

        return $this->withToken($jeton)->postJson("/api/lms/evaluations/{$this->evaluation->id}/notes", [
            'notes' => [['etudiant_id' => $this->eleve->id, 'note' => $note]],
        ]);
    }

    private function seance(): ESBTPSeanceCours
    {
        $emploi = \App\Models\ESBTPEmploiTemps::create([
            'titre' => 'Planning test LMS',
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 'semestre1',
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploi->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->evaluation->matiere_id,
            'annee_universitaire_id' => $this->annee->id,
            'jour' => 'lundi',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
        ]);
    }
}
