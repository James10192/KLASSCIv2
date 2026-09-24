<?php

namespace Tests\Feature\RendezVous;

use App\Models\User;
use App\Services\RendezVous\Rattrapage\JournalRattrapage;
use App\Services\RendezVous\Rattrapage\MessageConvocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Le contrat de `POST /api/cli/rendez-vous/rattrapage-convocations` :
 * simulation, ecriture tracee, relance sans effet, refus du lot mal forme.
 * Les regles d'appariement sont dans RattrapageConvocationsAppariementTest.
 */
class RattrapageConvocationsTest extends TestCase
{
    use ConstruitRattrapage;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerRattrapage();
    }

    public function test_la_simulation_apparie_par_l_adresse_et_n_ecrit_rien(): void
    {
        $r = $this->reservation('awa@gmail.com');
        $ref = $this->reference($r);

        $this->rattraper(false, [
            $this->message($ref, 'msg_bon', 'awa@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($ref, 'msg_autre_adresse', 'autre@gmail.com', '2026-09-11T09:00:00Z'),
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
        $this->assertSame(['convocation_message_id' => null, 'convocation_envoyee_at' => null], $audit->old_values);
        $this->assertSame('rattrapage_mailpulse', $audit->new_values['source']);

        $this->rattraper(true, $messages)->assertOk()
            ->assertJsonPath('data.eligibles', 0)
            ->assertJsonPath('data.deja_renseignees', 1)
            ->assertJsonPath('data.ecrites', 0);
        $this->assertSame(1, Audit::query()->where('event', 'rattrapage_convocation')->count());
    }

    public function test_sans_trace_d_audit_la_ligne_n_est_pas_ecrite(): void
    {
        $this->app->instance(JournalRattrapage::class, new class extends JournalRattrapage
        {
            public function ligneEcrite(int $reservationId, MessageConvocation $message, ?Model $auteur): void
            {
                throw new \RuntimeException('audit indisponible');
            }
        });
        $r = $this->reservation('awa@gmail.com');

        $this->rattraper(true, [$this->message($this->reference($r), 'msg_bon', 'awa@gmail.com', '2026-09-10T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.appariees', 1)->assertJsonPath('data.ecrites', 0);

        $this->assertNull($r->fresh()->convocation_message_id);
    }

    public function test_un_courriel_sans_reference_est_ecarte_seul(): void
    {
        $r = $this->reservation('awa@gmail.com');

        $this->rattraper(true, [
            $this->message(null, 'msg_orphelin', 'awa@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($this->reference($r), 'msg_bon', 'awa@gmail.com', '2026-09-10T09:00:00Z'),
        ])->assertOk()->assertJsonPath('data.sans_reference', 1)->assertJsonPath('data.ecrites', 1);
    }

    public function test_les_references_sont_masquees_a_deux_plus_deux_caracteres(): void
    {
        $r = $this->reservation('awa@gmail.com');
        $ref = str_replace('-', '', $this->reference($r));

        $exemple =$this->rattraper(false, [$this->message('AUTR-EDOS-SIER', 'msg_x', 'awa@gmail.com', '2026-09-10T09:00:00Z')])
            ->assertOk()->json('data.exemples.0');

        $this->assertSame('sans_message', $exemple['motif']);
        $this->assertSame(substr($ref, 0, 2).'**-****-**'.substr($ref, -2), $exemple['reference_masquee']);
    }

    public function test_un_jeton_de_lecture_ne_peut_pas_rattraper(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);

        $this->rattraper(false, [])->assertForbidden()->assertJsonPath('success', false);
    }

    public function test_les_lots_mal_formes_sont_refuses(): void
    {
        $bon = $this->message('ABCD-EFGH-IJKL', 'msg_1', 'awa@gmail.com', '2026-09-10T09:00:00Z');

        $this->postJson('/api/cli/rendez-vous/rattrapage-convocations', ['execute' => 'true', 'messages' => [$bon]])->assertStatus(422);
        $this->rattraper(false, [$bon, $bon])->assertStatus(422)->assertJsonPath('success', false);
        $this->rattraper(false, [['envoye_at' => '10/09/2026 09:00'] + $bon])->assertStatus(422);
        $this->rattraper(false, [['envoye_at' => now()->addDay()->toIso8601String()] + $bon])->assertStatus(422);

        $trop = [];
        for ($i = 0; $i <= 2000; $i++) {
            $trop[] = ['message_id' => 'msg_'.$i] + $bon;
        }
        $this->rattraper(false, $trop)->assertStatus(422);
    }
}
