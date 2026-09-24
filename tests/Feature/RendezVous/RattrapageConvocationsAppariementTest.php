<?php

namespace Tests\Feature\RendezVous;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvReservation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Les regles qui empechent de rattacher un courriel a la mauvaise
 * convocation : bornes dans le temps, action, destinataire, reservation
 * suivante du meme dossier, partage, perimetre, aucun ecrasement.
 */
class RattrapageConvocationsAppariementTest extends TestCase
{
    use ConstruitRattrapage;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerRattrapage();
    }

    public function test_le_plus_recent_parti_au_plus_tard_au_dernier_envoi_l_emporte(): void
    {
        $r = $this->reservation('awa@gmail.com', ['invite_le' => '2026-09-12 10:00:00']);
        $ref = $this->reference($r);

        $this->rattraper(true, [
            $this->message($ref, 'msg_ancien', 'awa@gmail.com', '2026-09-08T10:00:00Z'),
            $this->message($ref, 'msg_dernier', 'awa@gmail.com', '2026-09-12T09:59:00Z'),
            $this->message($ref, 'msg_apres_ancre', 'awa@gmail.com', '2026-09-12T10:05:00Z'),
        ])->assertOk()->assertJsonPath('data.ecrites', 1);

        $this->assertSame('msg_dernier', $r->fresh()->convocation_message_id);
    }

    public function test_deux_courriels_au_meme_instant_sont_ambigus(): void
    {
        $r = $this->reservation('awa@gmail.com', ['invite_le' => '2026-09-12 10:00:00']);
        $ref = $this->reference($r);

        $this->rattraper(true, [
            $this->message($ref, 'msg_a', 'awa@gmail.com', '2026-09-12T09:00:00Z'),
            $this->message($ref, 'msg_b', 'awa@gmail.com', '2026-09-12T09:00:00Z'),
        ])->assertOk()->assertJsonPath('data.ambigues', 1)->assertJsonPath('data.ecrites', 0);

        $this->assertNull($r->fresh()->convocation_message_id);
    }

    public function test_un_courriel_choisi_par_deux_reservations_est_ambigu_pour_les_deux(): void
    {
        // Un dossier n'a qu'une reservation active ; deux dossiers ne partagent une
        // reference que si une candidature et une reinscription tombent sur la meme.
        $premiere = $this->reservation('awa@gmail.com');
        $seconde = $this->reservationDeReinscription('awa@gmail.com', $premiere->candidature->reference_publique);

        $this->rattraper(true, [$this->message($this->reference($premiere), 'msg_unique', 'awa@gmail.com', '2026-09-10T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.ambigues', 2)->assertJsonPath('data.ecrites', 0);

        $this->assertNull($premiere->fresh()->convocation_message_id);
        $this->assertNull($seconde->fresh()->convocation_message_id);
    }

    public function test_la_confirmation_de_la_reservation_suivante_n_est_pas_prise(): void
    {
        $ancienne = $this->reservation('awa@gmail.com');
        $this->reservation('awa@gmail.com', ['candidature' => $ancienne->candidature, 'statut' => 'annulee', 'cree_le' => '2026-09-15 08:00:00']);

        $this->rattraper(true, [$this->message($this->reference($ancienne), 'msg_de_la_suivante', 'awa@gmail.com', '2026-09-16T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.sans_message', 1)->assertJsonPath('data.ecrites', 0);
    }

    public function test_un_courriel_anterieur_a_la_reservation_n_est_pas_pris(): void
    {
        $r = $this->reservation('awa@gmail.com', ['cree_le' => '2026-09-10 08:00:00']);

        $this->rattraper(true, [$this->message($this->reference($r), 'msg_avant', 'awa@gmail.com', '2026-09-10T07:58:00Z')])
            ->assertOk()->assertJsonPath('data.sans_message', 1);
    }

    public function test_un_courriel_posterieur_au_premier_envoi_suivi_n_est_pas_pris(): void
    {
        $suivie = $this->reservation('suivie@gmail.com');
        $suivie->forceFill(['convocation_message_id' => 'msg_suivi', 'convocation_envoyee_at' => Carbon::parse('2026-09-22 23:00:00', 'UTC')])->save();
        $r = $this->reservation('awa@gmail.com');
        $ref = $this->reference($r);

        $this->rattraper(false, [$this->message($ref, 'msg_apres_coupure', 'awa@gmail.com', '2026-09-23T08:00:00Z')])
            ->assertOk()->assertJsonPath('data.sans_message', 1);
        $this->rattraper(false, [$this->message($ref, 'msg_avant_coupure', 'awa@gmail.com', '2026-09-20T08:00:00Z')])
            ->assertOk()->assertJsonPath('data.appariees', 1);
    }

    public function test_l_action_doit_etre_compatible(): void
    {
        $r = $this->reservation('awa@gmail.com');
        $ref = $this->reference($r);

        $this->rattraper(false, [$this->message($ref, 'msg_annule', 'awa@gmail.com', '2026-09-10T09:00:00Z', 'annule')])
            ->assertOk()->assertJsonPath('data.sans_message', 1);
        $this->rattraper(false, [$this->message($ref, 'msg_deplace', 'awa@gmail.com', '2026-09-10T09:00:00Z', 'deplace')])
            ->assertOk()->assertJsonPath('data.appariees', 1);
    }

    public function test_sans_empreinte_un_courriel_ne_se_rattache_pas_a_une_adresse_connue(): void
    {
        $r = $this->reservation('awa@gmail.com');

        $this->rattraper(false, [$this->message($this->reference($r), 'msg_anonyme', null, '2026-09-10T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.sans_message', 1);
    }

    public function test_une_adresse_videe_retrouve_son_courriel_par_la_sauvegarde_du_nettoyage(): void
    {
        Storage::fake('local');
        $r = $this->reservation(null);
        Storage::disk('local')->put('backups/emails-factices-20260920_100000.json', json_encode([
            ['table' => 'esbtp_rdv_reservations', 'id' => $r->id, 'colonne' => 'email', 'ancienne_valeur' => 'm22-0521@esbtp.edu.ci'],
        ]));
        $ref = $this->reference($r);

        $this->rattraper(true, [
            $this->message($ref, 'msg_autre_fabriquee', 'm22-0999@esbtp.edu.ci', '2026-09-10T09:00:00Z'),
            $this->message($ref, 'msg_la_sienne', 'm22-0521@esbtp.edu.ci', '2026-09-09T09:00:00Z'),
        ])->assertOk()->assertJsonPath('data.ecrites', 1)->assertJsonPath('data.resolues_par_sauvegarde', 1);

        $this->assertSame('msg_la_sienne', $r->fresh()->convocation_message_id);
    }

    public function test_sans_sauvegarde_une_adresse_videe_prend_le_courriel_du_domaine_fabrique(): void
    {
        Storage::fake('local');
        $r = $this->reservation(null);
        $ref = $this->reference($r);

        $this->rattraper(true, [
            $this->message($ref, 'msg_gmail', 'awa@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($ref, 'msg_fabrique', 'm22-0521@esbtp.edu.ci', '2026-09-09T09:00:00Z'),
        ])->assertOk()->assertJsonPath('data.ecrites', 1)->assertJsonPath('data.resolues_par_domaine', 1);

        $this->assertSame('msg_fabrique', $r->fresh()->convocation_message_id);
    }

    public function test_une_reinscription_se_rattache_par_sa_reference(): void
    {
        $r = $this->reservationDeReinscription('parent@gmail.com');

        $this->rattraper(true, [$this->message($this->reference($r), 'msg_reinscription', 'parent@gmail.com', '2026-09-10T09:00:00Z')])
            ->assertOk()->assertJsonPath('data.ecrites', 1);

        $this->assertSame('msg_reinscription', $r->fresh()->convocation_message_id);
    }

    public function test_rien_n_est_ecrase_ni_pris_hors_du_perimetre(): void
    {
        $avecIdentifiant = $this->reservation('a@gmail.com');
        $avecIdentifiant->forceFill(['convocation_message_id' => 'msg_existant'])->save();
        $avecDate = $this->reservation('b@gmail.com');
        $avecDate->forceFill(['convocation_envoyee_at' => Carbon::parse('2026-09-08 07:00:00', 'UTC')])->save();
        $horsPerimetre = $this->reservation('c@gmail.com', ['annee' => ESBTPAnneeUniversitaire::factory()->create(['is_current' => false])]);

        $this->rattraper(true, [
            $this->message($this->reference($avecIdentifiant), 'msg_a', 'a@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($this->reference($avecDate), 'msg_b', 'b@gmail.com', '2026-09-10T09:00:00Z'),
            $this->message($this->reference($horsPerimetre), 'msg_c', 'c@gmail.com', '2026-09-10T09:00:00Z'),
        ])->assertOk()->assertJsonPath('data.eligibles', 0)->assertJsonPath('data.ecrites', 0);

        $this->assertSame('msg_existant', $avecIdentifiant->fresh()->convocation_message_id);
        $this->assertSame('2026-09-08 07:00:00', $avecDate->fresh()->convocation_envoyee_at->utc()->format('Y-m-d H:i:s'));
        $this->assertNull($avecDate->fresh()->convocation_message_id);
        $this->assertNull($horsPerimetre->fresh()->convocation_message_id);
    }
}
