<?php

namespace Tests\Feature\Annonces;

use App\Jobs\Annonces\EnvoyerAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use App\Services\Annonces\CorrigerCourrielAnnonce;
use App\Services\Annonces\ReferenceCourrielAnnonce;
use App\Services\Annonces\VerifierDestinataireMailPulse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class CorrigerCourrielAnnonceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.tenant_code' => 'esbtp-abidjan',
            'queue.default' => 'database',
            'services.mailpulse.announcement_retries_enabled' => true,
        ]);
        Bus::fake([EnvoyerAnnonceCourriel::class]);
    }

    private function campagne(): ESBTPAnnonce
    {
        return ESBTPAnnonce::create([
            'titre' => 'Information étudiants',
            'contenu' => 'Informations scolaires',
            'type' => 'general', 'priorite' => 0,
            'date_publication' => now()->subHour(), 'date_expiration' => now()->addDay(),
            'is_published' => true, 'email_notification_enabled' => true,
            'email_audience_scope' => 'all',
        ]);
    }

    private function rebond(ESBTPAnnonce $annonce, string $adresse): int
    {
        return DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => $adresse,
            'status' => 'failed', 'error_code' => 'email_bounced', 'attempts' => 1,
            'mailpulse_request_id' => ReferenceCourrielAnnonce::nouvelle(
                'esbtp-abidjan', $annonce->id, 1
            ),
            'mailpulse_message_id' => 'provider-old',
            'provider_failed_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_un_rebond_reste_immuable_et_la_correction_cree_une_nouvelle_tentative(): void
    {
        $annonce = $this->campagne();
        $etudiant = ESBTPEtudiant::factory()->create([
            'email' => 'ancien@ecole.test', 'email_personnel' => 'corrige@example.test',
        ]);
        $acteur = User::factory()->create();
        $ancien = $this->rebond($annonce, 'ancien@ecole.test');
        // Recalculate the original reference using the ID actually assigned.
        DB::table('esbtp_annonce_email_deliveries')->where('id', $ancien)->update([
            'mailpulse_request_id' => ReferenceCourrielAnnonce::nouvelle(
                'esbtp-abidjan', $annonce->id, $ancien
            ),
        ]);
        $verification = Mockery::mock(VerifierDestinataireMailPulse::class);
        $verification->shouldReceive('estAutorise')->once()
            ->with('corrige@example.test', 'esbtp-abidjan')->andReturn(true);
        $this->app->instance(VerifierDestinataireMailPulse::class, $verification);

        $result = app(CorrigerCourrielAnnonce::class)->executer(
            $annonce, $ancien, $etudiant->id,
            'corrige@example.test', 'Adresse personnelle corrigée par la scolarité', $acteur->id
        );

        $this->assertNotEquals($ancien, $result['delivery_id']);
        $this->assertSame('failed', DB::table('esbtp_annonce_email_deliveries')
            ->where('id', $ancien)->value('status'));
        $this->assertSame('pending', DB::table('esbtp_annonce_email_deliveries')
            ->where('id', $result['delivery_id'])->value('status'));
        $this->assertSame('klassci-v3-esbtp-abidjan-annonce-'.$annonce->id
            .'-courriel-'.$result['delivery_id'].'-essai-2', $result['reference']);
        $this->assertSame(2, DB::table('esbtp_annonce_email_attempts')->count());
        $this->assertSame(1, DB::table('esbtp_annonce_email_attempts')
            ->where('replaces_delivery_id', $ancien)->count());
        Bus::assertDispatchedTimes(EnvoyerAnnonceCourriel::class, 1);

        try {
            app(CorrigerCourrielAnnonce::class)->executer(
                $annonce, $ancien, $etudiant->id,
                'corrige@example.test', 'Même demande répétée', $acteur->id
            );
            $this->fail('La seconde soumission devait être rejetée.');
        } catch (ValidationException) {
            $this->assertSame(2, DB::table('esbtp_annonce_email_attempts')->count());
            Bus::assertDispatchedTimes(EnvoyerAnnonceCourriel::class, 1);
        }
    }

    public function test_une_suppression_incertaine_ou_une_queue_sync_refusent_la_relance(): void
    {
        $annonce = $this->campagne();
        $etudiant = ESBTPEtudiant::factory()->create([
            'email' => 'ancien@ecole.test', 'email_personnel' => 'nouveau@example.test',
        ]);
        $acteur = User::factory()->create();
        $ancien = $this->rebond($annonce, 'ancien@ecole.test');
        $verification = Mockery::mock(VerifierDestinataireMailPulse::class);
        $verification->shouldReceive('estAutorise')->once()->andReturn(false);
        $this->app->instance(VerifierDestinataireMailPulse::class, $verification);
        try {
            app(CorrigerCourrielAnnonce::class)->executer($annonce,$ancien,$etudiant->id,
                'nouveau@example.test','Adresse corrigée et vérifiée',$acteur->id);
            $this->fail('Suppression inconnue autorisée.');
        } catch (ValidationException) {
            $this->assertSame(1, DB::table('esbtp_annonce_email_deliveries')->count());
        }
        config(['queue.default' => 'sync']);
        try {
            app(CorrigerCourrielAnnonce::class)->executer($annonce,$ancien,$etudiant->id,
                'nouveau@example.test','Adresse corrigée et vérifiée',$acteur->id);
            $this->fail('Queue sync autorisée.');
        } catch (ValidationException) {
            Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
        }
    }

    public function test_reference_v3_differencie_chaque_tentative_et_rejette_autre_tenant(): void
    {
        $a = ReferenceCourrielAnnonce::tentative('esbtp-abidjan', 2, 5, 2);
        $b = ReferenceCourrielAnnonce::tentative('esbtp-abidjan', 2, 5, 3);
        $this->assertNotSame($a, $b);
        $this->assertSame(2, ReferenceCourrielAnnonce::extraire($a, 'esbtp-abidjan')['attempt_number']);
        $this->assertNull(ReferenceCourrielAnnonce::extraire($a, 'esbtp-yakro'));
        $this->assertNull(ReferenceCourrielAnnonce::tentative('default', 2, 5, 2));
    }
}
