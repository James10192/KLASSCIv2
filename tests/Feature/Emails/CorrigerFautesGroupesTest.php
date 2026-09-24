<?php

namespace Tests\Feature\Emails;

use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Les lignes d'un meme dossier se corrigent ensemble ou pas du tout, et
 * aucun echec ne laisse une correction a moitie faite.
 */
class CorrigerFautesGroupesTest extends TestCase
{
    use ConstruitCorrections;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerCorrections();
    }

    public function test_une_cle_dont_les_lignes_liees_manquent_est_refusee(): void
    {
        $c = $this->candidature('kone@gmai.com');
        $r = $this->reservation(['candidature_id' => $c->id], 'kone@gmai.com');

        $this->corriger([$this->cle('esbtp_candidatures', $c->id)])
            ->assertOk()->assertJsonPath('data.corrigees', 0)->assertJsonPath('data.ignorees.0.motif', 'lies_non_valides');

        $this->assertSame('kone@gmai.com', $c->fresh()->email);
        $this->assertSame('kone@gmai.com', $r->fresh()->email);
    }

    public function test_une_cle_refusee_refuse_tout_son_groupe(): void
    {
        $c = $this->candidature('kone@gmai.com');
        $r = $this->reservation(['candidature_id' => $c->id], 'kone@gmai.com');

        $this->corriger([
            $this->cle('esbtp_candidatures', $c->id, 'yahoo.com'),
            $this->cle('esbtp_rdv_reservations', $r->id),
        ])->assertOk()->assertJsonPath('data.corrigees', 0)
            ->assertJsonPath('data.ignorees.0.motif', 'domaine_non_canonique')
            ->assertJsonPath('data.ignorees.1.motif', 'groupe_refuse');

        $this->assertSame('kone@gmai.com', $r->fresh()->email);
    }

    public function test_une_adresse_deja_utilisee_annule_tout_le_groupe_sans_trace(): void
    {
        [$etudiant, $reservation] = $this->etudiantAvecReservation('kone@gmai.com');
        ESBTPEtudiant::factory()->create(['email' => 'kone@gmail.com']);

        // La reservation passe la premiere ; l'etudiant heurte l'unicite et annule tout.
        $this->corriger([
            $this->cle('esbtp_rdv_reservations', $reservation->id),
            $this->cle('esbtp_etudiants', $etudiant->id),
        ])->assertOk()->assertJsonPath('data.corrigees', 0)
            ->assertJsonPath('data.ignorees.0.motif', 'adresse_deja_utilisee')
            ->assertJsonPath('data.ignorees.1.motif', 'adresse_deja_utilisee');

        $this->assertSame('kone@gmai.com', $reservation->fresh()->email);
        $this->assertSame('kone@gmai.com', $etudiant->fresh()->email);
        $this->assertSame(0, Audit::query()->where('event', 'correction_faute_email')->count());
    }

    public function test_un_groupe_en_echec_n_empeche_pas_les_suivants(): void
    {
        [$etudiant, $reservation] = $this->etudiantAvecReservation('kone@gmai.com');
        ESBTPEtudiant::factory()->create(['email' => 'kone@gmail.com']);
        $autre = $this->candidature('awa@gmail.con');

        $this->corriger([
            $this->cle('esbtp_rdv_reservations', $reservation->id),
            $this->cle('esbtp_etudiants', $etudiant->id),
            $this->cle('esbtp_candidatures', $autre->id),
        ])->assertOk()->assertJsonPath('data.corrigees', 1);

        $this->assertSame('awa@gmail.com', $autre->fresh()->email);
    }

    public function test_le_compte_d_un_etudiant_fait_partie_de_son_groupe(): void
    {
        $compte = User::factory()->create(['email' => 'kone@gmai.com']);
        $etudiant = ESBTPEtudiant::factory()->create(['email' => 'kone@gmai.com', 'email_personnel' => null, 'user_id' => $compte->id]);

        $proposition = collect($this->postJson(self::ROUTE, ['execute' => false])->assertOk()->json('data.propositions'))
            ->firstWhere('cle', 'esbtp_etudiants:email:'.$etudiant->id);
        $this->assertSame(['users:email:'.$compte->id], array_column($proposition['lie_a'], 'cle'));

        $this->corriger([$this->cle('esbtp_etudiants', $etudiant->id)])->assertOk()->assertJsonPath('data.ignorees.0.motif', 'lies_non_valides');
        $this->corriger([$this->cle('esbtp_etudiants', $etudiant->id), $this->cle('users', $compte->id)], ['inclure_comptes' => true])
            ->assertOk()->assertJsonPath('data.corrigees', 2);
        $this->assertSame('kone@gmail.com', $compte->fresh()->email);
    }

    public function test_une_sauvegarde_illisible_n_ecrit_rien(): void
    {
        $c = $this->candidature('kone@gmai.com');
        $disque = Mockery::mock(FilesystemAdapter::class);
        $disque->shouldReceive('exists')->andReturn(false);
        $disque->shouldReceive('put')->andReturn(true);
        $disque->shouldReceive('get')->andReturn('contenu altere');
        Storage::shouldReceive('disk')->with('local')->andReturn($disque);

        $reponse = $this->corriger([$this->cle('esbtp_candidatures', $c->id)])->assertStatus(500)->assertJsonPath('success', false);

        $this->assertStringNotContainsString('backups', $reponse->getContent(), 'Ni chemin ni detail technique.');
        $this->assertSame('kone@gmai.com', $c->fresh()->email);
        $this->assertSame(0, Audit::query()->where('event', 'correction_faute_email')->count());
    }

    public function test_une_ligne_modifiee_avant_le_verrou_est_ignoree(): void
    {
        // La verification stricte d'une faute probable laisse une fenetre : on y ecrit.
        $c = $this->candidature('kone@gmaill.com');
        $this->dns->inexistants['gmaill.com'] = true;
        $this->dns->pendantVerification = fn () => DB::table('esbtp_candidatures')->where('id', $c->id)->update(['email' => 'autre@gmaill.com']);

        $this->corriger([$this->cle('esbtp_candidatures', $c->id)], ['inclure_probables' => true])
            ->assertOk()->assertJsonPath('data.corrigees', 0)->assertJsonPath('data.ignorees.0.motif', 'modifiee_entre_temps');

        $this->assertSame('autre@gmaill.com', $c->fresh()->email);
    }
}
