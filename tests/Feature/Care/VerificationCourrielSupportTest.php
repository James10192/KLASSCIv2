<?php

namespace Tests\Feature\Care;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Mail\Support\LienVerificationCourrielMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Après un signalement, l'interface sait si l'adresse est à confirmer, et la
 * personne peut se faire envoyer un lien. Rien n'est bloqué.
 */
class VerificationCourrielSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class, PaywallMiddleware::class]);
        Cache::flush();
        Mail::fake();
        config()->set('services.master.api_url', 'https://master.test/api');
        config()->set('services.master.support_token', 'kc_abcdefghijkl_'.str_repeat('A', 40));
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_widget' => true, 'support_customer_portal' => true]]),
            'master.test/api/v1/support/tickets*' => Http::response(['reference' => 'KC-2026-000042', 'statut' => ['code' => 'RECU', 'libelle' => 'Reçue']], 201),
        ]);
    }

    private function utilisateur(bool $verifie): User
    {
        return User::factory()->create([
            'username' => 'u_'.Str::lower(Str::random(8)),
            'email' => 'awa.kone@ecole.test',
            'email_verified_at' => $verifie ? now() : null,
        ]);
    }

    private function signaler(User $user)
    {
        return $this->actingAs($user)->postJson(route('support.demandes.store'), [
            'categorie' => 'PROBLEME',
            'description' => 'Le bouton Valider les notes ne répond plus.',
            'cle' => '3f2b8c1e-5d4a-4f6b-9a7c-1e2d3c4b5a69',
        ]);
    }

    public function test_une_adresse_non_confirmee_est_signalee_a_l_interface_sans_bloquer(): void
    {
        $this->signaler($this->utilisateur(false))
            ->assertCreated()
            ->assertJsonPath('reference', 'KC-2026-000042')
            ->assertJsonPath('email_a_verifier', true)
            ->assertJsonPath('email_masque', 'a***@ecole.test')
            ->assertJsonPath('email_verification_url', route('support.courriel.lien'));
    }

    public function test_une_adresse_confirmee_n_est_pas_signalee(): void
    {
        $this->signaler($this->utilisateur(true))
            ->assertCreated()
            ->assertJsonPath('email_a_verifier', false)
            ->assertJsonPath('email_verification_url', null);
    }

    public function test_le_lien_part_puis_confirme_l_adresse(): void
    {
        $user = $this->utilisateur(false);

        $this->actingAs($user)->postJson(route('support.courriel.lien'))
            ->assertOk()->assertJsonPath('envoye', true);

        $lien = null;
        Mail::assertSent(LienVerificationCourrielMail::class, function ($mail) use ($user, &$lien) {
            $lien = $mail->lien;

            return $mail->hasTo($user->email);
        });

        $this->actingAs($user)->get($lien)->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_un_lien_pour_une_autre_adresse_ou_non_signe_ne_confirme_rien(): void
    {
        $user = $this->utilisateur(false);
        $autreAdresse = URL::temporarySignedRoute('support.courriel.confirmer', now()->addHour(), ['id' => $user->id, 'hash' => sha1('autre@ecole.test')]);

        $this->actingAs($user)->get($autreAdresse)->assertForbidden();
        $this->actingAs($user)->get(route('support.courriel.confirmer', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_une_adresse_deja_confirmee_ne_recoit_pas_de_lien(): void
    {
        $this->actingAs($this->utilisateur(true))->postJson(route('support.courriel.lien'))
            ->assertStatus(422)->assertJsonPath('deja_verifiee', true);
        Mail::assertNothingSent();
    }
}
