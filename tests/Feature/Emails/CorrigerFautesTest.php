<?php

namespace Tests\Feature\Emails;

use App\Enums\StatutConvocationRdv;
use App\Models\User;
use App\Services\Verification\MasqueContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Le contrat de `POST /api/cli/emails/corriger-fautes` pour les fautes
 * CONNUES : propositions masquees, correction limitee aux cles validees,
 * sauvegarde, refus d'un domaine non canonique, comptes sur demande expresse.
 */
class CorrigerFautesTest extends TestCase
{
    use ConstruitCorrections;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerCorrections();
    }

    public function test_les_propositions_sont_masquees_et_relient_le_dossier(): void
    {
        $c = $this->candidature('kone@gmai.com');
        $r = $this->reservation(['candidature_id' => $c->id], 'kone@gmai.com');
        User::factory()->create(['email' => 'secretaire@gmail.con']);

        $reponse = $this->postJson(self::ROUTE, ['execute' => false])->assertOk()
            ->assertJsonPath('data.execute', false)
            ->assertJsonPath('data.propositions_total', 2)
            ->assertJsonPath('data.corrigees', 0);

        $proposition = collect($reponse->json('data.propositions'))->firstWhere('cle', 'esbtp_candidatures:email:'.$c->id);
        $this->assertSame('gmai.com', $proposition['domaine_actuel']);
        $this->assertSame('gmail.com', $proposition['domaine_propose']);
        $this->assertSame('connue', $proposition['nature']);
        $this->assertFalse($proposition['compte_de_connexion']);
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
        $r = $this->reservation(['candidature_id' => $c->id], 'Kone.A+x@GMAI.com', StatutConvocationRdv::Echec);
        $autre = $this->candidature('awa@gmail.con');

        $reponse = $this->corriger([
            ['domaine_actuel' => 'gmai.com'] + $this->cle('esbtp_candidatures', $c->id),
            $this->cle('esbtp_rdv_reservations', $r->id),
        ])->assertOk()->assertJsonPath('data.corrigees', 2)->assertJsonPath('data.convocations_a_renvoyer', 1);

        $this->assertSame('Kone.A+x@gmail.com', $c->fresh()->email, 'La partie locale est gardee telle quelle.');
        $this->assertSame('Kone.A+x@gmail.com', $r->fresh()->email);
        $this->assertSame('awa@gmail.con', $autre->fresh()->email, 'Une cle non validee n\'est pas touchee.');
        $this->assertSame(StatutConvocationRdv::Echec, $r->fresh()->convocation_statut, 'Rien n\'est renvoye.');

        $nom = $reponse->json('data.sauvegarde');
        $this->assertMatchesRegularExpression('/^emails-fautes-\d{8}_\d{6}_\d{6}-[a-z0-9]{6}\.json$/', $nom);
        $sauvegarde = collect(json_decode(Storage::disk('local')->get('backups/'.$nom), true));
        $this->assertTrue($sauvegarde->contains(fn ($l) => $l['table'] === 'esbtp_candidatures' && $l['id'] === $c->id
            && $l['ancienne_valeur'] === 'Kone.A+x@GMAI.com' && $l['nouvelle_valeur'] === 'Kone.A+x@gmail.com'));
        $this->assertSame(2, Audit::query()->where('event', 'correction_faute_email')->count());
    }

    public function test_un_domaine_qui_n_est_pas_la_suggestion_canonique_est_refuse(): void
    {
        $c = $this->candidature('kone@gmai.com');

        $this->corriger([$this->cle('esbtp_candidatures', $c->id, 'yahoo.com')])
            ->assertOk()->assertJsonPath('data.corrigees', 0)
            ->assertJsonPath('data.ignorees.0.motif', 'domaine_non_canonique')
            ->assertJsonPath('data.sauvegarde', null);

        $this->assertSame('kone@gmai.com', $c->fresh()->email);
    }

    public function test_une_adresse_sans_faute_ou_d_un_autre_domaine_est_ignoree(): void
    {
        $corrigee = $this->candidature('kone@gmail.com');
        $autreFaute = $this->candidature('awa@gmail.con');

        $this->corriger([
            $this->cle('esbtp_candidatures', $corrigee->id),
            ['domaine_actuel' => 'gmai.com'] + $this->cle('esbtp_candidatures', $autreFaute->id),
            $this->cle('esbtp_candidatures', 999999),
        ])->assertOk()->assertJsonPath('data.corrigees', 0)
            ->assertJsonPath('data.ignorees.0.motif', 'modifiee_entre_temps')
            ->assertJsonPath('data.ignorees.1.motif', 'modifiee_entre_temps')
            ->assertJsonPath('data.ignorees.2.motif', 'introuvable');

        $this->assertSame('awa@gmail.con', $autreFaute->fresh()->email);
    }

    public function test_les_comptes_ne_sont_corriges_que_sur_demande_expresse_et_signales(): void
    {
        $compte = User::factory()->create(['email' => 'secretaire@gmail.con']);

        $this->corriger([$this->cle('users', $compte->id)])->assertOk()->assertJsonPath('data.ignorees.0.motif', 'cle_invalide');
        $this->assertSame('secretaire@gmail.con', $compte->fresh()->email);

        $proposition = collect($this->postJson(self::ROUTE, ['execute' => false, 'inclure_comptes' => true])->assertOk()
            ->json('data.propositions'))->firstWhere('cle', 'users:email:'.$compte->id);
        $this->assertTrue($proposition['compte_de_connexion']);
        $this->assertNotEmpty($proposition['avertissement']);

        $this->corriger([$this->cle('users', $compte->id)], ['inclure_comptes' => true])->assertOk()->assertJsonPath('data.corrigees', 1);
        $this->assertSame('secretaire@gmail.com', $compte->fresh()->email);
    }

    public function test_les_requetes_mal_formees_sont_refusees(): void
    {
        $c = $this->candidature('kone@gmai.com');

        $this->corriger([$this->cle('esbtp_candidatures', $c->id, 'gmail.com', 'telephone')])
            ->assertOk()->assertJsonPath('data.ignorees.0.motif', 'cle_invalide');
        $this->postJson(self::ROUTE, ['execute' => 'true', 'corrections' => []])->assertStatus(422);
        $this->postJson(self::ROUTE, ['execute' => true])->assertStatus(422);
        $this->postJson(self::ROUTE, ['execute' => false, 'inclure_probables' => 'oui'])->assertStatus(422);
    }
}
