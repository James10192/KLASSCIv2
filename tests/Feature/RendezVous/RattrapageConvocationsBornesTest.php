<?php

namespace Tests\Feature\RendezVous;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les bornes du rattrapage aux limites : seconde du dernier envoi, coupure
 * fournie par l'appelant, reservation precedente du meme dossier, statut, et
 * coupure qui ne recule pas apres un premier rattrapage.
 */
class RattrapageConvocationsBornesTest extends TestCase
{
    use ConstruitRattrapage;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerRattrapage();
    }

    public function test_un_deplacement_dans_la_seconde_du_dernier_envoi_l_emporte_sur_la_confirmation(): void
    {
        // rdv_invite_at est tronque a la seconde ; le createdAt MailPulse du
        // deplacement tombe dans cette seconde, apres elle.
        $r = $this->reservation('awa@gmail.com', ['invite_le' => '2026-09-12 10:00:00']);
        $ref = $this->reference($r);

        $this->rattraper(true, [
            $this->message($ref, 'msg_confirme', 'awa@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($ref, 'msg_deplace', 'awa@gmail.com', '2026-09-12T10:00:00.400Z', 'deplace'),
        ])->assertOk()->assertJsonPath('data.ecrites', 1);

        $this->assertSame('msg_deplace', $r->fresh()->convocation_message_id);
    }

    public function test_la_coupure_fournie_ne_peut_qu_avancer_la_coupure_calculee(): void
    {
        $r = $this->reservation('awa@gmail.com');
        $ref = $this->reference($r);

        $this->rattraper(false, [$this->message($ref, 'msg_tardif', 'awa@gmail.com', '2026-09-21T08:00:00Z')], '2026-09-20T00:00:00Z')
            ->assertOk()
            ->assertJsonPath('data.coupure', '2026-09-20T00:00:00+00:00')
            ->assertJsonPath('data.coupure_source', 'coupure_max')
            ->assertJsonPath('data.sans_message', 1);
        $this->rattraper(false, [$this->message($ref, 'msg_avant', 'awa@gmail.com', '2026-09-19T08:00:00Z')], '2026-09-20T00:00:00Z')
            ->assertOk()->assertJsonPath('data.appariees', 1);
    }

    public function test_une_coupure_fournie_plus_tardive_que_le_premier_envoi_suivi_est_ignoree(): void
    {
        $suivie = $this->reservation('suivie@gmail.com');
        $suivie->forceFill(['convocation_message_id' => 'msg_suivi', 'convocation_envoyee_at' => Carbon::parse('2026-09-22 23:00:00', 'UTC')])->save();
        $this->reservation('awa@gmail.com');

        $this->rattraper(false, [$this->message('ABCD-EFGH-IJKL', 'msg_x', 'awa@gmail.com', '2026-09-10T09:00:00Z')], '2026-09-23T12:00:00Z')
            ->assertOk()
            ->assertJsonPath('data.coupure', '2026-09-22T23:00:00+00:00')
            ->assertJsonPath('data.coupure_source', 'premier_envoi_suivi');
    }

    public function test_avec_une_reservation_precedente_un_courriel_anterieur_a_la_creation_est_ambigu(): void
    {
        $precedente = $this->reservation('awa@gmail.com', ['statut' => 'annulee', 'cree_le' => '2026-09-01 08:00:00']);
        $r = $this->reservation('awa@gmail.com', ['candidature' => $precedente->candidature]);
        $ref = $this->reference($r);

        $this->rattraper(false, [$this->message($ref, 'msg_juste_avant', 'awa@gmail.com', '2026-09-05T07:59:30Z')])
            ->assertOk()->assertJsonPath('data.ambigues', 1);
        $this->rattraper(false, [$this->message($ref, 'msg_a_la_creation', 'awa@gmail.com', '2026-09-05T08:00:00Z')])
            ->assertOk()->assertJsonPath('data.appariees', 1);
    }

    public function test_une_reservation_non_confirmee_n_est_pas_eligible(): void
    {
        $r = $this->reservation('awa@gmail.com', ['statut' => 'honoree']);

        $this->rattraper(true, [$this->message($this->reference($r), 'msg_bon', 'awa@gmail.com', '2026-09-10T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.eligibles', 0)->assertJsonPath('data.ecrites', 0);

        $this->assertNull($r->fresh()->convocation_message_id);
    }

    public function test_une_ligne_rattrapee_ne_fait_pas_reculer_la_coupure(): void
    {
        $suivie = $this->reservation('suivie@gmail.com');
        $suivie->forceFill(['convocation_message_id' => 'msg_suivi', 'convocation_envoyee_at' => Carbon::parse('2026-09-22 23:00:00', 'UTC')])->save();
        $premiere = $this->reservation('a@gmail.com');
        $seconde = $this->reservation('b@gmail.com');

        $this->rattraper(true, [$this->message($this->reference($premiere), 'msg_a', 'a@gmail.com', '2026-09-06T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.ecrites', 1);

        // Premiere porte desormais un identifiant date du 06/09 : la coupure reste le 22/09.
        $this->rattraper(true, [$this->message($this->reference($seconde), 'msg_b', 'b@gmail.com', '2026-09-15T09:00:00Z')])
            ->assertOk()
            ->assertJsonPath('data.coupure', '2026-09-22T23:00:00+00:00')
            ->assertJsonPath('data.ecrites', 1);
    }
}
