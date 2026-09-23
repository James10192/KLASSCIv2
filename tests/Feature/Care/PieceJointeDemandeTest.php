<?php

namespace Tests\Feature\Care;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Joindre un fichier a sa demande, puis le relire : le relais cote instance,
 * Master simule. Le navigateur ne parle jamais au Master.
 */
class PieceJointeDemandeTest extends TestCase
{
    use RefreshDatabase;

    private const CLE = '7a1c2e3f-4b5d-4e6f-8a9b-0c1d2e3f4a5b';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class, PaywallMiddleware::class]);
        Cache::flush();
        config()->set('services.master.api_url', 'https://master.test/api');
        config()->set('services.master.support_token', 'kc_abcdefghijkl_'.str_repeat('A', 40));
    }

    private function utilisateur(): User
    {
        return User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8)), 'name' => 'Awa Koné']);
    }

    private function master(array $routes, array $portees = ['support:create', 'support:read', 'support:update']): void
    {
        Http::fake(['master.test/api/v1/support/bootstrap' => Http::response([
            'fonctionnalites' => ['support_widget' => true, 'support_customer_portal' => true],
            'portees' => $portees,
        ])] + $routes);
    }

    private function detail(int $rapporteurId, array $pieces = []): array
    {
        return [
            'reference' => 'KC-2026-000042', 'titre' => 'Le bouton Valider ne répond plus',
            'description' => 'Le bouton Valider les notes ne répond plus.',
            'categorie' => ['code' => 'PROBLEME', 'libelle' => 'Quelque chose ne fonctionne pas'],
            'statut' => ['code' => 'EN_ANALYSE', 'libelle' => 'En analyse'],
            'rapporteur' => ['id' => $rapporteurId, 'nom' => 'Awa Koné'],
            'cree_le' => now()->toIso8601String(), 'mis_a_jour_le' => now()->toIso8601String(),
            'derniere_reponse' => null, 'messages' => [], 'pieces_jointes' => $pieces,
        ];
    }

    private function piece(): array
    {
        return ['id' => 7, 'nom' => 'ecran.png', 'type' => 'image/png', 'taille' => 20480, 'auteur' => 'ECOLE', 'le' => now()->toIso8601String()];
    }

    private function joindre(User $user, UploadedFile $fichier, string $cle = self::CLE)
    {
        return $this->actingAs($user)->post(route('support.demandes.pieces.store', 'KC-2026-000042'),
            ['fichier' => $fichier, 'cle' => $cle], ['Accept' => 'application/json']);
    }

    /** @test */
    public function le_fichier_part_au_master_en_portee_mine_avec_la_cle_et_revient_dans_la_liste(): void
    {
        $user = $this->utilisateur();
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response($this->detail($user->id, [$this->piece()]), 201)]);

        $this->joindre($user, UploadedFile::fake()->image('ecran.png', 20, 20))
            ->assertOk()
            ->assertJsonPath('peut_joindre', true)
            ->assertJsonPath('pieces', fn ($html) => str_contains($html, 'ecran.png')
                && str_contains($html, route('support.demandes.pieces.show', ['KC-2026-000042', 7])));

        Http::assertSent(function (Request $req) use ($user) {
            return $req->method() === 'POST'
                && str_contains($req->url(), '/tickets/KC-2026-000042/attachments')
                && str_contains($req->url(), 'reporter='.$user->getKey())
                && str_contains($req->url(), 'scope=mine')
                && $req->header('Idempotency-Key') === [self::CLE]
                && $req->isMultipart();
        });
    }

    /** @test */
    public function un_type_refuse_ne_part_pas_au_master(): void
    {
        $this->master(['master.test/*' => Http::response([], 500)]);

        $this->joindre($this->utilisateur(), UploadedFile::fake()->createWithContent('page.html', '<html></html>'))
            ->assertStatus(422)->assertJsonValidationErrors(['fichier']);

        Http::assertNotSent(fn (Request $req) => str_contains($req->url(), '/attachments'));
    }

    /** @test */
    public function le_refus_du_master_est_montre_tel_quel(): void
    {
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response(['error' => 'attachment_rejected', 'message' => 'Cette demande a déjà 10 pièces jointes.'], 422)]);

        $this->joindre($this->utilisateur(), UploadedFile::fake()->image('ecran.png'))
            ->assertStatus(422)->assertJsonPath('message', 'Cette demande a déjà 10 pièces jointes.');
    }

    /** @test */
    public function une_demande_fermee_ou_une_portee_absente_refusent_l_envoi(): void
    {
        $user = $this->utilisateur();
        $ferme = ['statut' => ['code' => 'FERME', 'libelle' => 'Fermée']] + $this->detail($user->id);
        $this->master([
            'master.test/api/v1/support/tickets/*/attachments*' => Http::response(['error' => 'ticket_closed', 'message' => 'Fermée.'], 409),
            'master.test/api/v1/support/tickets/*' => Http::response($ferme),
        ]);
        $this->joindre($user, UploadedFile::fake()->image('ecran.png'))->assertStatus(409)
            ->assertJsonPath('peut_joindre', false)
            ->assertJsonPath('statut', fn ($html) => str_contains($html, 'Fermée'));

        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response(['error' => 'insufficient_scope', 'message' => 'Portée requise.'], 403)]);
        $this->joindre($user, UploadedFile::fake()->image('ecran.png'))->assertStatus(403)->assertJsonPath('peut_joindre', false);
        $this->assertFalse(app(\App\Services\Care\ClientMasterSupport::class)->coupeCircuitOuvert());
    }

    /** @test */
    public function la_piece_est_relayee_sans_etre_interpretee_par_le_navigateur(): void
    {
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $this->actingAs($this->utilisateur())->get(route('support.demandes.pieces.show', ['KC-2026-000042', 7]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename="piece-7.png"');
    }

    /** @test */
    public function un_type_inattendu_se_telecharge_et_une_piece_hors_de_portee_n_existe_pas(): void
    {
        $user = $this->utilisateur();
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response('<html>', 200, ['Content-Type' => 'text/html'])]);
        $this->actingAs($user)->get(route('support.demandes.pieces.show', ['KC-2026-000042', 7]))
            ->assertOk()->assertHeader('Content-Type', 'application/octet-stream')
            // Aucun type admis : aucune extension devinee.
            ->assertHeader('Content-Disposition', 'attachment; filename="piece-7"');

        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response(['error' => 'not_found'], 404)]);
        $this->actingAs($user)->get(route('support.demandes.pieces.show', ['KC-2026-000042', 7]))->assertNotFound();
    }

    /** @test */
    public function la_page_liste_les_pieces_et_ne_propose_l_envoi_qu_a_l_auteur(): void
    {
        $user = $this->utilisateur();
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response($this->detail($user->id, [$this->piece()]))]);
        $this->actingAs($user)->get(route('support.demandes.show', 'KC-2026-000042'))
            ->assertSee('ecran.png')->assertSee('id="sd-joindre"', false);

        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response($this->detail($user->id + 1000, [$this->piece()]))]);
        $this->actingAs($user)->get(route('support.demandes.show', 'KC-2026-000042'))
            ->assertSee('ecran.png')->assertDontSee('id="sd-joindre"', false);
    }

    /** @test */
    public function un_pdf_se_telecharge_avec_son_extension(): void
    {
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf'])]);

        $this->actingAs($this->utilisateur())->get(route('support.demandes.pieces.show', ['KC-2026-000042', 7]))
            ->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="piece-7.pdf"');
    }

    /** @test */
    public function trop_d_envois_se_dit_sans_fermer_le_support(): void
    {
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response(['error' => 'too_many_requests'], 429)]);

        $this->joindre($this->utilisateur(), UploadedFile::fake()->image('ecran.png'))
            ->assertStatus(429)->assertJsonPath('message', fn ($m) => str_contains($m, 'une minute'));
        $this->assertFalse(app(\App\Services\Care\ClientMasterSupport::class)->coupeCircuitOuvert());
    }

    /** @test */
    public function le_plafond_de_pieces_retire_le_formulaire(): void
    {
        $user = $this->utilisateur();
        $this->master(['master.test/api/v1/support/tickets/*' => Http::response($this->detail($user->id, array_fill(0, 10, $this->piece())))]);

        $this->actingAs($user)->get(route('support.demandes.show', 'KC-2026-000042'))->assertDontSee('id="sd-joindre"', false);
    }
}
