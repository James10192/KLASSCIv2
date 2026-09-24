<?php

namespace Tests\Feature\Emails;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\Verification\MasqueContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Les adresses a faute de frappe (gmai.com, gmail.con...) : l'ecole voit les
 * propositions, valide cle par cle, et seules ces adresses sont corrigees,
 * vers la suggestion canonique, apres sauvegarde.
 */
class CorrigerFautesTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/api/cli/emails/corriger-fautes';

    private ESBTPAnneeUniversitaire $annee;

    private int $numero = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
            \App\Http\Middleware\ThrottleRequestsParRoute::class,
        ]);
        Sanctum::actingAs(User::factory()->create(['email' => 'agent@gmail.com']), ['cli:read', 'cli:admin']);
        Cache::flush();
        Storage::fake('local');
        config(['emails_joignables.mx.actif' => false]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    public function test_les_propositions_sont_masquees_et_relient_le_dossier(): void
    {
        $c = $this->candidature('kone@gmai.com');
        $r = $this->reservation($c, 'kone@gmai.com');
        User::factory()->create(['email' => 'secretaire@gmail.con']);

        $reponse = $this->postJson(self::ROUTE, ['execute' => false])->assertOk()
            ->assertJsonPath('data.execute', false)
            ->assertJsonPath('data.propositions_total', 2)
            ->assertJsonPath('data.corrigees', 0);

        $proposition = collect($reponse->json('data.propositions'))->firstWhere('cle', 'esbtp_candidatures:email:'.$c->id);
        $this->assertSame('gmai.com', $proposition['domaine_actuel']);
        $this->assertSame('gmail.com', $proposition['domaine_propose']);
        $this->assertSame(MasqueContact::email('kone@gmai.com'), $proposition['email_masque']);
        $this->assertSame(MasqueContact::email('kone@gmail.com'), $proposition['suggestion_masquee']);
        $this->assertNotNull($proposition['dossier_reference_masquee']);
        $this->assertSame(['esbtp_rdv_reservations:email:'.$r->id], array_column($proposition['lie_a'], 'cle'));
        $autre = collect($reponse->json('data.propositions'))->firstWhere('cle', 'esbtp_rdv_reservations:email:'.$r->id);
        $this->assertSame(['esbtp_candidatures:email:'.$c->id], array_column($autre['lie_a'], 'cle'));

        $this->assertStringNotContainsString('kone@', $reponse->getContent());
        $this->assertStringNotContainsString('secretaire', $reponse->getContent(), 'Les comptes ne sont pas proposes sans inclure_comptes.');
        $this->assertSame([], Storage::disk('local')->allFiles(), 'Une simulation n\'ecrit rien.');
    }

    public function test_seules_les_cles_validees_sont_corrigees_apres_sauvegarde(): void
    {
        $c = $this->candidature('Kone.A+x@GMAI.com');
        $r = $this->reservation($c, 'Kone.A+x@GMAI.com', StatutConvocationRdv::Echec);
        $autre = $this->candidature('awa@gmail.con');

        $reponse = $this->corriger([
            ['cle' => 'esbtp_candidatures:email:'.$c->id, 'domaine_propose' => 'gmail.com', 'domaine_actuel' => 'gmai.com'],
            ['cle' => 'esbtp_rdv_reservations:email:'.$r->id, 'domaine_propose' => 'gmail.com'],
        ])->assertOk()->assertJsonPath('data.corrigees', 2)->assertJsonPath('data.convocations_a_renvoyer', 1);

        $this->assertSame('Kone.A+x@gmail.com', $c->fresh()->email, 'La partie locale est gardee telle quelle.');
        $this->assertSame('Kone.A+x@gmail.com', $r->fresh()->email);
        $this->assertSame('awa@gmail.con', $autre->fresh()->email, 'Une cle non validee n\'est pas touchee.');
        $this->assertSame(StatutConvocationRdv::Echec, $r->fresh()->convocation_statut, 'Rien n\'est renvoye.');

        $fichier = 'backups/'.$reponse->json('data.sauvegarde');
        $this->assertStringStartsWith('emails-fautes-', $reponse->json('data.sauvegarde'));
        $sauvegarde = collect(json_decode(Storage::disk('local')->get($fichier), true));
        $this->assertTrue($sauvegarde->contains(fn ($l) => $l['table'] === 'esbtp_candidatures' && $l['id'] === $c->id
            && $l['ancienne_valeur'] === 'Kone.A+x@GMAI.com' && $l['nouvelle_valeur'] === 'Kone.A+x@gmail.com'));
        $this->assertSame(2, Audit::query()->where('event', 'correction_faute_email')->count());
    }

    public function test_un_domaine_qui_n_est_pas_la_suggestion_canonique_est_refuse(): void
    {
        $c = $this->candidature('kone@gmai.com');

        $this->corriger([['cle' => 'esbtp_candidatures:email:'.$c->id, 'domaine_propose' => 'yahoo.com']])
            ->assertOk()->assertJsonPath('data.corrigees', 0)
            ->assertJsonPath('data.ignorees.0.motif', 'domaine_non_canonique')
            ->assertJsonPath('data.sauvegarde', null);

        $this->assertSame('kone@gmai.com', $c->fresh()->email);
    }

    public function test_une_adresse_modifiee_entre_temps_est_ignoree(): void
    {
        $corrigee = $this->candidature('kone@gmail.com');
        $autreFaute = $this->candidature('awa@gmail.con');

        $this->corriger([
            ['cle' => 'esbtp_candidatures:email:'.$corrigee->id, 'domaine_propose' => 'gmail.com'],
            ['cle' => 'esbtp_candidatures:email:'.$autreFaute->id, 'domaine_propose' => 'gmail.com', 'domaine_actuel' => 'gmai.com'],
        ])->assertOk()->assertJsonPath('data.corrigees', 0)
            ->assertJsonPath('data.ignorees.0.motif', 'modifiee_entre_temps')
            ->assertJsonPath('data.ignorees.1.motif', 'modifiee_entre_temps');

        $this->assertSame('awa@gmail.con', $autreFaute->fresh()->email);
    }

    public function test_les_comptes_ne_sont_corriges_que_sur_demande_expresse(): void
    {
        $compte = User::factory()->create(['email' => 'secretaire@gmail.con']);
        $cle = ['cle' => 'users:email:'.$compte->id, 'domaine_propose' => 'gmail.com'];

        $this->corriger([$cle])->assertOk()->assertJsonPath('data.ignorees.0.motif', 'cle_invalide');
        $this->assertSame('secretaire@gmail.con', $compte->fresh()->email);

        $this->postJson(self::ROUTE, ['execute' => true, 'inclure_comptes' => true, 'corrections' => [$cle]])
            ->assertOk()->assertJsonPath('data.corrigees', 1);
        $this->assertSame('secretaire@gmail.com', $compte->fresh()->email);
    }

    public function test_une_cle_hors_des_colonnes_d_adresse_est_refusee(): void
    {
        $c = $this->candidature('kone@gmai.com');

        $this->corriger([['cle' => 'esbtp_candidatures:telephone:'.$c->id, 'domaine_propose' => 'gmail.com']])
            ->assertOk()->assertJsonPath('data.ignorees.0.motif', 'cle_invalide');
        $this->postJson(self::ROUTE, ['execute' => 'true', 'corrections' => []])->assertStatus(422);
        $this->postJson(self::ROUTE, ['execute' => true])->assertStatus(422);
    }

    private function corriger(array $corrections): TestResponse
    {
        return $this->postJson(self::ROUTE, ['execute' => true, 'corrections' => $corrections]);
    }

    private function candidature(string $email): ESBTPCandidature
    {
        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+22507010203'.sprintf('%02d', ++$this->numero),
            'email' => $email, 'annee_universitaire_id' => $this->annee->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    private function reservation(ESBTPCandidature $c, string $email, ?StatutConvocationRdv $convocation = null): ESBTPRdvReservation
    {
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee->id, 'date' => now()->addDays(3 + ++$this->numero)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $c->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $c->telephone, 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => $convocation, 'convocation_action' => 'confirme',
        ]);
    }
}
