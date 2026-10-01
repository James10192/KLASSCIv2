<?php

namespace Tests\Feature\Care;

use App\Domain\Support\Models\CurseurSupport;
use App\Mail\Support\ReponseDuSupportMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Le support répond ou clôture : le rapporteur en est averti une fois, dans
 * l'application, et par e-mail si son adresse est confirmée.
 */
class SuiviDemandesSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();
        config()->set('services.master.api_url', 'https://master.test/api');
        config()->set('services.master.support_token', 'kc_abcdefghijkl_'.str_repeat('A', 40));
    }

    private function utilisateur(bool $verifie): User
    {
        return User::factory()->create([
            'username' => 'u_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(6)).'@ecole.test',
            'email_verified_at' => $verifie ? now() : null,
        ]);
    }

    private function resume(User $rapporteur, string $statut = 'EN_ANALYSE', ?string $reponseLe = null, string $majLe = '2026-10-01T09:00:00+00:00'): array
    {
        return [
            'reference' => 'KC-2026-000042',
            'titre' => 'Le bouton Valider ne répond plus',
            'statut' => ['code' => $statut, 'libelle' => $statut === 'RESOLU' ? 'Résolue' : 'En analyse'],
            'rapporteur' => ['id' => $rapporteur->id, 'nom' => $rapporteur->name],
            'mis_a_jour_le' => $majLe,
            'derniere_reponse_support_le' => $reponseLe,
            'derniere_reponse' => $reponseLe ? ['auteur' => 'SUPPORT', 'nom' => 'Support KLASSCI', 'corps' => 'Corrigé, rechargez la page.', 'le' => $reponseLe] : null,
        ];
    }

    /** Le Master simulé : les fonctionnalités, puis une liste de demandes par passage. */
    private function master(array ...$passages): void
    {
        $sequence = Http::sequence();
        foreach ($passages as $data) {
            $sequence->push(['data' => $data, 'meta' => ['page' => 1, 'pages' => 1, 'total' => count($data)]]);
        }
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_widget' => true, 'support_customer_portal' => true], 'portees' => ['support:read']]),
            'master.test/api/v1/support/tickets*' => $sequence,
        ]);
    }

    public function test_le_premier_passage_releve_l_historique_sans_avertir(): void
    {
        $user = $this->utilisateur(true);
        $this->master([$this->resume($user, 'RESOLU', '2026-09-20T10:00:00+00:00')]);

        $this->artisan('support:suivre-demandes')->assertSuccessful();

        $this->assertSame(0, Notification::count());
        Mail::assertNothingSent();
        $this->assertTrue(CurseurSupport::existe(CurseurSupport::SUIVI_DEMANDES));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'scope=school') && ! str_contains($r->url(), 'mis_a_jour_depuis'));
    }

    public function test_une_reponse_du_support_avertit_une_fois_dans_l_application_et_par_e_mail(): void
    {
        $user = $this->utilisateur(true);
        $avant = $this->resume($user);
        $apres = $this->resume($user, 'EN_ANALYSE', '2026-10-01T09:10:00+00:00', '2026-10-01T09:10:00+00:00');
        $this->master([$avant], [$apres], [$apres]);

        $this->artisan('support:suivre-demandes')->assertSuccessful();
        $this->artisan('support:suivre-demandes')->assertSuccessful();
        $this->artisan('support:suivre-demandes')->assertSuccessful();

        $notification = Notification::sole();
        $this->assertSame($user->id, (int) $notification->user_id);
        $this->assertStringContainsString('KC-2026-000042', $notification->title);
        $this->assertStringContainsString('Corrigé, rechargez la page.', $notification->message);
        $this->assertSame(route('support.demandes.show', 'KC-2026-000042', false), $notification->link);
        Mail::assertSent(ReponseDuSupportMail::class, 1);
        Mail::assertSent(ReponseDuSupportMail::class, fn ($m) => $m->hasTo($user->email));

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'mis_a_jour_depuis='));
    }

    public function test_une_cloture_avertit_et_une_adresse_non_confirmee_ne_recoit_pas_d_e_mail(): void
    {
        $user = $this->utilisateur(false);
        $this->master([$this->resume($user)], [$this->resume($user, 'RESOLU', null, '2026-10-01T09:30:00+00:00')]);

        $this->artisan('support:suivre-demandes')->assertSuccessful();
        $this->artisan('support:suivre-demandes')->assertSuccessful();

        $this->assertSame('success', Notification::sole()->type);
        Mail::assertNothingSent();
    }

    public function test_master_injoignable_le_curseur_ne_bouge_pas(): void
    {
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_customer_portal' => true]]),
            'master.test/api/v1/support/tickets*' => Http::response([], 503),
        ]);

        $this->artisan('support:suivre-demandes')->assertSuccessful();

        $this->assertFalse(CurseurSupport::existe(CurseurSupport::SUIVI_DEMANDES));
    }

    public function test_sans_suivi_ouvert_le_master_n_est_pas_interroge(): void
    {
        Http::fake(['master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_customer_portal' => false]])]);

        $this->artisan('support:suivre-demandes')->assertSuccessful();

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/tickets'));
    }
}
