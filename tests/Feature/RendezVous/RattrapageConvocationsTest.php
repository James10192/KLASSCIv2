<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Les convocations d'avant le suivi (22/09) ont ete marquees « envoyee » sans
 * identifiant MailPulse : la synchronisation ne pouvait pas les relire. Le
 * rattrapage leur rattache le courriel extrait de MailPulse, sans jamais
 * deviner ni ecraser.
 */
class RattrapageConvocationsTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/api/cli/rendez-vous/rattrapage-convocations';

    private ESBTPAnneeUniversitaire $annee;

    private int $numero = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Sanctum::actingAs(User::factory()->create(), ['cli:read', 'cli:admin']);
        Cache::flush();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    public function test_la_simulation_apparie_par_l_adresse_et_n_ecrit_rien(): void
    {
        $r = $this->reservation('awa@gmail.com');
        $ref = $this->reference($r);

        $this->rattraper(false, [
            $this->message($ref, 'msg_bon', 'awa@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($ref, 'msg_autre_adresse', 'autre@gmail.com', '2026-09-10T09:00:00Z'),
        ])->assertOk()
            ->assertJsonPath('data.execute', false)
            ->assertJsonPath('data.eligibles', 1)
            ->assertJsonPath('data.appariees', 1)
            ->assertJsonPath('data.ecrites', 0);

        $this->assertNull($r->fresh()->convocation_message_id, 'Une simulation n\'ecrit rien.');
    }

    public function test_l_execution_ecrit_trace_et_se_relance_sans_effet(): void
    {
        $r = $this->reservation(' Awa@Gmail.com ');
        $messages = [$this->message($this->reference($r), 'msg_bon', 'awa@gmail.com', '2026-09-10T09:00:00Z')];

        $this->rattraper(true, $messages)->assertOk()->assertJsonPath('data.ecrites', 1);

        $r->refresh();
        $this->assertSame('msg_bon', $r->convocation_message_id);
        $this->assertSame('2026-09-10 09:00:00', $r->convocation_envoyee_at->utc()->format('Y-m-d H:i:s'));
        $audit = Audit::query()->where('event', 'rattrapage_convocation')->sole();
        $this->assertSame($r->id, (int) $audit->auditable_id);
        $this->assertSame('rattrapage_mailpulse', $audit->new_values['source']);

        $this->rattraper(true, $messages)->assertOk()
            ->assertJsonPath('data.eligibles', 0)
            ->assertJsonPath('data.deja_renseignees', 1)
            ->assertJsonPath('data.ecrites', 0);
        $this->assertSame(1, Audit::query()->where('event', 'rattrapage_convocation')->count());
    }

    public function test_une_adresse_videe_par_le_nettoyage_retrouve_le_courriel_parti_vers_le_domaine_fabrique(): void
    {
        $r = $this->reservation(null);
        $ref = $this->reference($r);

        $this->rattraper(true, [
            $this->message($ref, 'msg_gmail', 'awa@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($ref, 'msg_fabrique', 'm22-0521@esbtp.edu.ci', '2026-09-10T09:00:00Z'),
        ])->assertOk()->assertJsonPath('data.ecrites', 1);

        $this->assertSame('msg_fabrique', $r->fresh()->convocation_message_id);
    }

    public function test_le_courriel_le_plus_proche_de_l_invitation_l_emporte(): void
    {
        $r = $this->reservation('awa@gmail.com', '2026-09-12 10:00:00');
        $ref = $this->reference($r);

        $this->rattraper(true, [
            $this->message($ref, 'msg_loin', 'awa@gmail.com', '2026-09-08T10:00:00Z'),
            $this->message($ref, 'msg_proche', 'awa@gmail.com', '2026-09-12T10:05:00Z'),
        ])->assertOk()->assertJsonPath('data.ecrites', 1);

        $this->assertSame('msg_proche', $r->fresh()->convocation_message_id);
    }

    public function test_deux_courriels_a_egale_distance_sont_ambigus_et_rien_n_est_ecrit(): void
    {
        $r = $this->reservation('awa@gmail.com', '2026-09-12 10:00:00');
        $ref = $this->reference($r);

        $reponse = $this->rattraper(true, [
            $this->message($ref, 'msg_avant', 'awa@gmail.com', '2026-09-12T09:00:00Z'),
            $this->message($ref, 'msg_apres', 'awa@gmail.com', '2026-09-12T11:00:00Z'),
        ])->assertOk()->assertJsonPath('data.ambigues', 1)->assertJsonPath('data.ecrites', 0);

        $this->assertNull($r->fresh()->convocation_message_id);
        $exemple = $reponse->json('data.exemples.0');
        $this->assertSame('ambigue', $exemple['motif']);
        $this->assertStringNotContainsString($ref, $exemple['reference_masquee']);
    }

    public function test_un_identifiant_deja_present_n_est_jamais_ecrase(): void
    {
        $r = $this->reservation('awa@gmail.com');
        $r->forceFill(['convocation_message_id' => 'msg_existant'])->save();

        $this->rattraper(true, [$this->message($this->reference($r), 'msg_nouveau', 'awa@gmail.com', '2026-09-10T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.eligibles', 0)->assertJsonPath('data.ecrites', 0);

        $this->assertSame('msg_existant', $r->fresh()->convocation_message_id);
    }

    public function test_une_reservation_hors_du_perimetre_des_rendez_vous_n_est_pas_touchee(): void
    {
        $r = $this->reservation('awa@gmail.com', null, ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]));

        $this->rattraper(true, [$this->message($this->reference($r), 'msg_bon', 'awa@gmail.com', '2026-09-10T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.eligibles', 0)->assertJsonPath('data.ecrites', 0);

        $this->assertNull($r->fresh()->convocation_message_id);
    }

    public function test_execute_doit_etre_un_vrai_booleen(): void
    {
        $this->postJson(self::ROUTE, ['execute' => 'true', 'messages' => []])->assertStatus(422)->assertJsonPath('success', false);
    }

    private function rattraper(bool $executer, array $messages): TestResponse
    {
        return $this->postJson(self::ROUTE, ['execute' => $executer, 'messages' => $messages]);
    }

    /** @return array<string, mixed> */
    private function message(string $reference, string $id, string $destinataire, string $envoyeAt, string $action = 'confirme'): array
    {
        return [
            'reference' => $reference, 'message_id' => $id, 'envoye_at' => $envoyeAt,
            'destinataire_sha256' => hash('sha256', mb_strtolower(trim($destinataire))),
            'destinataire_domaine' => substr(strrchr($destinataire, '@'), 1),
            'action' => $action,
        ];
    }

    private function reference(ESBTPRdvReservation $r): string
    {
        // Telle que la famille la voit dans le lien du courriel : XXXX-XXXX-XXXX.
        return (string) $r->candidature->referencePubliqueAffichee();
    }

    private function reservation(?string $email, ?string $inviteLe = null, ?ESBTPAnneeUniversitaire $annee = null): ESBTPRdvReservation
    {
        $annee ??= $this->annee;
        $n = ++$this->numero;
        $telephone = '+22507010203'.sprintf('%02d', $n);
        $candidature = ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => $telephone,
            'email' => $email === null ? null : trim($email), 'annee_universitaire_id' => $annee->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
        $candidature->forceFill(['rdv_invite_at' => $inviteLe === null ? null : Carbon::parse($inviteLe, 'UTC')])->saveQuietly();
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee->id, 'date' => now()->addDays(3 + $n)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);

        // Comme la migration du suivi : « envoyee », sans identifiant ni date.
        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $telephone, 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => StatutConvocationRdv::Envoyee, 'convocation_action' => 'confirme',
        ]);
    }
}
