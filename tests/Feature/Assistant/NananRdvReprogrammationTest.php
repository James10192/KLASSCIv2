<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\RendezVous\ReprogrammerRendezVous;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Assistant\Outils\LireRendezVous;
use App\Enums\StatutConvocationRdv;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReprogrammation;
use App\Models\ESBTPRdvReservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\RendezVous\MessagerieRdv;
use App\Services\RendezVous\RendezVousReglages as R;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NananRdvReprogrammationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ESBTPAnneeUniversitaire $annee;
    private int $numero = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:00:00');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();

        foreach (['inscriptions.rdv.manage', 'inscriptions.rdv.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::factory()->create(['username' => 'rdv_'.Str::lower(Str::random(8))]);
        $this->admin->givePermissionTo(['inscriptions.rdv.manage', 'inscriptions.rdv.view']);

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id,
            'session_id' => (string) Str::uuid(),
            'last_activity_at' => now(),
        ]);

        $this->reglagesRdv();

        // La vraie file est conservée ; seule la sortie MailPulse est neutralisée.
        $this->mock(MessagerieRdv::class, function ($m) {
            $m->shouldReceive('planifier')->andReturnUsing(function (ESBTPRdvReservation $r, string $action = 'confirme') {
                $r->forceFill([
                    'convocation_statut' => StatutConvocationRdv::EnAttente,
                    'convocation_action' => $action,
                    'convocation_tentatives' => 0,
                    'convocation_envoyee_at' => null,
                ])->save();
            });
            $m->shouldReceive('envoyer')->andReturn(null);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_nanan_lit_un_vendredi_puis_reprogramme_tout_le_lot_apres_validation(): void
    {
        $source = $this->creneau('2026-10-09', '08:00', '08:40', 5, true);
        $a = $this->reservation($source, 'KOFFI');
        $b = $this->reservation($source, 'YAO');
        $lundi = $this->creneau('2026-10-12', '08:00', '08:40', 1, true);
        $mardi = $this->creneau('2026-10-13', '08:00', '08:40', 2, true);
        // Un vendredi futur ouvert ne doit jamais être choisi : vendredi n'est plus dans les réglages.
        $this->creneau('2026-10-16', '08:00', '08:40', 10, true);

        $lecture = app(LireRendezVous::class)->executeAuthorized(['date' => '2026-10-09'], $this->admin);
        $this->assertSame(2, $lecture['count']);
        $this->assertSameCanonicalizing([$a->id, $b->id], array_column($lecture['results'], 'reservation'));
        $this->assertStringContainsString('proposer_reprogrammation_rdv', $lecture['diagnostic']['action_si_jour_a_fermer']);

        $this->assertContains('proposer_reprogrammation_rdv', array_column(app(CatalogueOutils::class)->schemas($this->admin), 'nom'));
        $proposition = app(ReprogrammerRendezVous::class)->executeAuthorized(['date_source' => '2026-10-09'], $this->admin);

        $this->assertSame('approbation', $proposition['widget']['kind'] ?? null, json_encode($proposition, JSON_UNESCAPED_UNICODE));
        $this->assertSame($source->id, (int) $a->fresh()->creneau_id, 'rien avant Valider');
        $this->assertTrue($source->fresh()->ouvert, 'la fermeture fait partie de la proposition, pas de la préparation');

        $this->actingAs($this->admin)
            ->postJson($proposition['widget']['valider_url'], ['jeton' => $proposition['widget']['jeton']])
            ->assertOk()->assertJson(['statut' => 'executee']);

        $this->assertFalse($source->fresh()->ouvert);
        $this->assertSame($lundi->id, (int) $a->fresh()->creneau_id);
        $this->assertSame($mardi->id, (int) $b->fresh()->creneau_id);
        $this->assertSame('deplace', $a->fresh()->convocation_action);
        $this->assertSame('deplace', $b->fresh()->convocation_action);
        $this->assertSame(StatutConvocationRdv::EnAttente, $a->fresh()->convocation_statut);
        $this->assertSame(2, ESBTPRdvReprogrammation::count());
        $this->assertSame(0, ESBTPRdvReprogrammation::where('non_venue', true)->count(), 'une fermeture administrative future n’est pas une absence');
        $this->assertSame(2, ESBTPRdvReprogrammation::where('par', $this->admin->id)->count());
    }

    public function test_nanan_refuse_un_deplacement_partiel_si_les_places_ne_suffisent_pas(): void
    {
        $source = $this->creneau('2026-10-09', '08:00', '08:40', 5, false);
        $a = $this->reservation($source, 'KOFFI');
        $b = $this->reservation($source, 'YAO');
        $this->creneau('2026-10-12', '08:00', '08:40', 1, true);

        $r = app(ReprogrammerRendezVous::class)->executeAuthorized(['date_source' => '2026-10-09'], $this->admin);

        $this->assertArrayHasKey('manques', $r);
        $this->assertStringContainsString('1 famille(s) sur 2', implode(' ', $r['manques']));
        $this->assertSame($source->id, (int) $a->fresh()->creneau_id);
        $this->assertSame($source->id, (int) $b->fresh()->creneau_id);
        $this->assertSame(0, ESBTPRdvReprogrammation::count());
    }

    private function reglagesRdv(): void
    {
        foreach ([
            R::ENABLED => '1',
            R::OUVERTURE => '2026-09-08',
            R::FERMETURE => '2026-10-31',
            R::HEURE_DEBUT => '08:00',
            R::HEURE_FIN => '16:00',
            R::DUREE => '40',
            R::CAPACITE => '12',
            R::JOURS => '1,2,3,4',
            PortailCandidaturePublication::REGLAGE_PHYSIQUES => '2026-09-08',
            PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => (string) $this->annee->id,
        ] as $cle => $valeur) {
            Setting::setOrCreate($cle, $valeur);
        }
        Setting::clearCache();
        Cache::flush();
    }

    private function creneau(string $date, string $debut, string $fin, int $capacite, bool $ouvert): ESBTPRdvCreneau
    {
        return ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee->id,
            'date' => $date,
            'heure_debut' => $debut.':00',
            'heure_fin' => $fin.':00',
            'capacite' => $capacite,
            'ouvert' => $ouvert,
        ]);
    }

    private function reservation(ESBTPRdvCreneau $creneau, string $nom): ESBTPRdvReservation
    {
        $n = ++$this->numero;
        $candidature = ESBTPCandidature::create([
            'nom' => $nom,
            'prenoms' => 'Awa',
            'date_naissance' => '2007-01-01',
            'telephone' => '+225070000'.sprintf('%04d', $n),
            'email' => strtolower($nom).$n.'@example.ci',
            'annee_universitaire_id' => $this->annee->id,
            'consentement_at' => now(),
            'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id,
            'candidature_id' => $candidature->id,
            'statut' => 'confirmee',
            'nom' => $nom,
            'prenoms' => 'Awa',
            'telephone' => $candidature->telephone,
            'date_naissance' => '2007-01-01',
            'email' => $candidature->email,
            'convocation_statut' => 'envoyee',
            'convocation_action' => 'confirme',
            'convocation_envoyee_at' => now()->subDay(),
        ]);
    }
}
