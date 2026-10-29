<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Renvoi CIBLE : seules les reservations nommees reviennent en file, rien ne
 * part pendant l'appel, et un second appel ne change rien.
 */
class RenvoiConvocationsCibleesTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/api/cli/rendez-vous/convocations/renvoyer';

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
        Sanctum::actingAs(User::factory()->create(), ['cli:read', 'cli:admin']);
        Cache::flush();
        Http::fake();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    public function test_la_simulation_dit_qui_est_eligible_et_ne_touche_a_rien(): void
    {
        $cible = $this->reservation('kone@gmail.com', StatutConvocationRdv::Envoyee);
        $annulee = $this->reservation('awa@gmail.com', StatutConvocationRdv::Envoyee, 'annulee');
        $enFile = $this->reservation('ali@gmail.com', StatutConvocationRdv::EnAttente);
        $fautive = $this->reservation('ama@gmai.com', StatutConvocationRdv::Envoyee);
        $passee = $this->reservation('yao@gmail.com', StatutConvocationRdv::Envoyee, 'confirmee', -2);

        $lignes = collect($this->renvoyer(false, [$cible->id, $annulee->id, $enFile->id, $fautive->id, $passee->id, 999999])
            ->assertOk()->assertJsonPath('data.execute', false)->json('data.reservations'))->keyBy('id');

        $this->assertTrue($lignes[$cible->id]['eligible']);
        $this->assertSame('envoyee', $lignes[$cible->id]['statut_convocation']);
        $this->assertStringNotContainsString('kone@', (string) $lignes[$cible->id]['email_masque']);
        $this->assertSame('reservation_non_active', $lignes[$annulee->id]['raison']);
        $this->assertSame('deja_en_file', $lignes[$enFile->id]['raison']);
        $this->assertSame('adresse_non_joignable', $lignes[$fautive->id]['raison']);
        $this->assertSame('creneau_passe', $lignes[$passee->id]['raison']);
        $this->assertSame('introuvable', $lignes[999999]['raison']);
        $this->assertSame(StatutConvocationRdv::Envoyee, $cible->fresh()->convocation_statut);
    }

    public function test_seules_les_reservations_nommees_reviennent_en_file_sans_rien_envoyer(): void
    {
        $cible = $this->reservation('kone@gmail.com', StatutConvocationRdv::Envoyee, 'confirmee', 3, ['convocation_message_id' => 'msg_ancien', 'convocation_delivree_at' => now()]);
        $autre = $this->reservation('awa@gmail.com', StatutConvocationRdv::Echec);
        $voisine = $this->reservation('ali@gmail.com', StatutConvocationRdv::Envoyee);

        $this->renvoyer(true, [$cible->id])->assertOk()
            ->assertJsonPath('data.remises', 1)
            ->assertJsonPath('data.non_eligibles', [])
            ->assertJsonPath('data.a_envoyer', 1);

        $cible->refresh();
        $this->assertSame(StatutConvocationRdv::EnAttente, $cible->convocation_statut);
        $this->assertNull($cible->convocation_message_id, 'Remise a zero comme le chemin canonique.');
        $this->assertNull($cible->convocation_delivree_at);
        $this->assertSame(StatutConvocationRdv::Echec, $autre->fresh()->convocation_statut, 'Une reservation non nommee n\'est pas touchee.');
        $this->assertSame(StatutConvocationRdv::Envoyee, $voisine->fresh()->convocation_statut);
        Http::assertNothingSent();

        $audit = Audit::query()->where('event', 'renvoi_convocation')->sole();
        $this->assertSame('envoyee', $audit->old_values['convocation_statut']);
        $this->assertSame('msg_ancien', $audit->old_values['convocation_message_id']);
        $this->assertSame('adresse_corrigee', $audit->new_values['motif']);
    }

    public function test_l_audit_garde_tout_ce_que_la_remise_a_zero_efface(): void
    {
        // Famille deja appelee par un agent : c'est la seule trace de qui l'a prevenue.
        $agent = User::factory()->create();
        $cible = $this->reservation('kone@gmail.com', StatutConvocationRdv::Telephone, 'confirmee', 3, [
            'prevenue_par' => $agent->id, 'convocation_tentatives' => 2, 'convocation_erreur' => 'Rebond (email_bounced)',
        ]);

        $this->renvoyer(true, [$cible->id])->assertOk()->assertJsonPath('data.remises', 1);

        $this->assertNull($cible->fresh()->prevenue_par);
        $avant = Audit::query()->where('event', 'renvoi_convocation')->sole()->old_values;
        $this->assertSame('telephone', $avant['convocation_statut']);
        $this->assertSame($agent->id, (int) $avant['prevenue_par']);
        $this->assertSame(2, (int) $avant['convocation_tentatives']);
        $this->assertSame('Rebond (email_bounced)', $avant['convocation_erreur']);
        $this->assertSame('confirme', $avant['convocation_action']);
    }

    public function test_un_dossier_clos_ou_un_avis_d_annulation_ne_sont_pas_renvoyes(): void
    {
        $inscrit = $this->reservation('kone@gmail.com', StatutConvocationRdv::Envoyee);
        $inscrit->candidature->forceFill(['statut' => ESBTPCandidature::statutsDossierClos()[0]])->saveQuietly();
        $annulation = $this->reservation('awa@gmail.com', StatutConvocationRdv::Envoyee, 'confirmee', 3, ['convocation_action' => 'annule']);

        $this->renvoyer(true, [$inscrit->id, $annulation->id])->assertOk()
            ->assertJsonPath('data.remises', 0)
            ->assertJsonPath('data.non_eligibles.0.raison', 'dossier_clos')
            ->assertJsonPath('data.non_eligibles.1.raison', 'avis_d_annulation');

        $this->assertSame(StatutConvocationRdv::Envoyee, $inscrit->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Envoyee, $annulation->fresh()->convocation_statut);
    }

    public function test_un_second_appel_ne_change_rien(): void
    {
        $cible = $this->reservation('kone@gmail.com', StatutConvocationRdv::Envoyee);

        $this->renvoyer(true, [$cible->id])->assertOk()->assertJsonPath('data.remises', 1);
        $this->renvoyer(true, [$cible->id])->assertOk()
            ->assertJsonPath('data.remises', 0)
            ->assertJsonPath('data.non_eligibles.0.raison', 'deja_en_file');

        $this->assertSame(1, Audit::query()->where('event', 'renvoi_convocation')->count());
    }

    public function test_les_non_eligibles_ne_sont_pas_remis(): void
    {
        $fautive = $this->reservation('ama@gmai.com', StatutConvocationRdv::Envoyee);
        $annulee = $this->reservation('awa@gmail.com', StatutConvocationRdv::Envoyee, 'annulee');

        $this->renvoyer(true, [$fautive->id, $annulee->id])->assertOk()->assertJsonPath('data.remises', 0);

        $this->assertSame(StatutConvocationRdv::Envoyee, $fautive->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Envoyee, $annulee->fresh()->convocation_statut);
    }

    public function test_les_reservations_a_mauvaise_adresse_se_retrouvent(): void
    {
        $corrigee = $this->reservation('kone@gmail.com', StatutConvocationRdv::Envoyee, 'confirmee', 3, ['convocation_delivree_at' => now()]);
        Audit::query()->create([
            'event' => 'correction_faute_email', 'auditable_type' => $corrigee->getMorphClass(), 'auditable_id' => $corrigee->id,
            'old_values' => ['email' => 'kone@gmai.com'], 'new_values' => ['email' => 'kone@gmail.com'],
        ]);
        $piege = $this->reservation('ama@gmai.com', StatutConvocationRdv::Envoyee);
        $saine = $this->reservation('ali@gmail.com', StatutConvocationRdv::Envoyee);

        $reponse = $this->getJson('/api/cli/rendez-vous/convocations/adresses-corrigees')->assertOk()->assertJsonPath('data.total', 2);
        $lignes = collect($reponse->json('data.reservations'))->keyBy('id');

        $this->assertSame('adresse_corrigee', $lignes[$corrigee->id]['raison']);
        $this->assertTrue($lignes[$corrigee->id]['delivree']);
        $this->assertSame('domaine_piege', $lignes[$piege->id]['raison']);
        $this->assertArrayNotHasKey($saine->id, $lignes->all());
        foreach (['kone@', 'ama@'] as $clair) {
            $this->assertStringNotContainsString($clair, $reponse->getContent());
        }
    }

    public function test_les_droits_et_les_limites_sont_tenus(): void
    {
        $cible = $this->reservation('kone@gmail.com', StatutConvocationRdv::Envoyee);

        $this->postJson(self::ROUTE, ['execute' => 'true', 'reservations' => [$cible->id], 'motif' => 'adresse_corrigee'])->assertStatus(422);
        $this->renvoyer(false, [])->assertStatus(422);
        $this->renvoyer(false, [$cible->id, $cible->id])->assertStatus(422);
        $this->renvoyer(false, range(1, 51))->assertStatus(422);
        $this->postJson(self::ROUTE, ['execute' => false, 'reservations' => [$cible->id], 'motif' => 'autre'])->assertStatus(422);

        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->renvoyer(true, [$cible->id])->assertForbidden();
        $this->getJson('/api/cli/rendez-vous/convocations/adresses-corrigees')->assertOk();
        $this->assertSame(StatutConvocationRdv::Envoyee, $cible->fresh()->convocation_statut);
    }

    private function renvoyer(bool $executer, array $ids): TestResponse
    {
        return $this->postJson(self::ROUTE, ['execute' => $executer, 'reservations' => $ids, 'motif' => 'adresse_corrigee']);
    }

    /** @param  array<string, mixed>  $autres */
    private function reservation(string $email, StatutConvocationRdv $convocation, string $statut = 'confirmee', int $dansJours = 3, array $autres = []): ESBTPRdvReservation
    {
        $n = ++$this->numero;
        $candidature = ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+22507010203'.sprintf('%02d', $n),
            'email' => $email, 'annee_universitaire_id' => $this->annee->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
        // Un creneau par reservation : (annee, date, heure de debut) est unique.
        $debut = sprintf('%02d:%02d:00', 8 + intdiv($n, 2), ($n % 2) * 30);
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee->id, 'date' => now()->addDays($dansJours)->toDateString(),
            'heure_debut' => $debut, 'heure_fin' => sprintf('%02d:%02d:00', 8 + intdiv($n, 2), ($n % 2) * 30 + 20), 'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create($autres + [
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => $statut,
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $candidature->telephone, 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => $convocation, 'convocation_action' => 'confirme',
            'convocation_envoyee_at' => now()->subDay(),
        ]);
    }
}
