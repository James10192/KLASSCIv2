<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\RendezVous\ReservateurRdv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Rejeter un dossier rend son creneau a venir.
 *
 * Avant, la reservation d'un candidat refuse restait « confirmee » : elle
 * comptait dans les places prises, et personne d'autre ne pouvait s'en servir.
 */
class LiberationAuRejetTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private int $annee;

    private int $numero = 0;

    /** Lundi 5 octobre 2026, 09:10 */
    private const MAINTENANT = '2026-10-05 09:10:00';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(self::MAINTENANT);

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();

        foreach (['admin.access', 'inscriptions.candidatures.view', 'inscriptions.candidatures.process'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['admin.access', 'inscriptions.candidatures.view', 'inscriptions.candidatures.process']);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_rejeter_une_candidature_libere_son_creneau_a_venir(): void
    {
        $creneau = $this->creneau('10:00', '10:30', 2, 1);
        $r = $this->reservation($creneau);

        $this->actingAs($this->agent)
            ->post(route('esbtp.candidatures.rejeter', $r->candidature_id), ['motif_rejet' => 'Dossier incomplet, bac non obtenu.'])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $m) => str_contains($m, 'est libéré'));

        $r->refresh();
        $this->assertSame(StatutReservationRdv::Liberee, $r->statut);
        $this->assertSame(self::MAINTENANT, $r->libere_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, $creneau->fresh()->placesPrises(), 'La place revient à la campagne.');
    }

    public function test_un_creneau_deja_commence_n_est_pas_libere(): void
    {
        // 09:10 : le creneau de 09:00 a commence. Personne ne peut plus le reserver,
        // et la famille est peut-etre deja dans la salle d'attente.
        $commence = $this->reservation($this->creneau('09:00', '09:30'));
        $plusTard = $this->reservation($this->creneau('10:00', '10:30'));

        $this->assertNull($this->reservateur()->liberer($this->candidature($commence)));
        $this->assertNotNull($this->reservateur()->liberer($this->candidature($plusTard)));

        $this->assertSame(StatutReservationRdv::Confirmee, $commence->fresh()->statut);
        $this->assertSame(StatutReservationRdv::Liberee, $plusTard->fresh()->statut);
    }

    public function test_l_historique_et_les_faits_ne_sont_pas_reecrits(): void
    {
        $passe = $this->reservation($this->creneau('08:00', '08:30'));
        $recue = $this->reservation($this->creneau('11:00', '11:30', 1), ['statut' => 'honoree']);

        $this->reservateur()->liberer($this->candidature($passe));
        $this->reservateur()->liberer($this->candidature($recue));

        $this->assertSame(StatutReservationRdv::Confirmee, $passe->fresh()->statut, 'Un créneau passé ne rend de place à personne.');
        $this->assertNull($passe->fresh()->libere_at);
        $this->assertSame(StatutReservationRdv::Honoree, $recue->fresh()->statut, 'Une famille reçue reste reçue.');
    }

    public function test_on_ne_touche_qu_aux_reservations_du_dossier_rejete(): void
    {
        $creneau = $this->creneau('10:00', '10:30', 2);
        $rejetee = $this->reservation($creneau);
        $voisine = $this->reservation($creneau);

        $this->reservateur()->liberer($this->candidature($rejetee));

        $this->assertSame(StatutReservationRdv::Liberee, $rejetee->fresh()->statut);
        $this->assertSame(StatutReservationRdv::Confirmee, $voisine->fresh()->statut);
    }

    public function test_une_famille_rejetee_ne_reprend_pas_de_place_depuis_le_portail(): void
    {
        $this->mock(\App\Services\RendezVous\CatalogueCreneaux::class, fn ($m) => $m->shouldReceive('publier')->andReturn([]));
        $r = $this->reservation($this->creneau('10:00', '10:30', 2, 1));
        $autre = $this->creneau('11:00', '11:30', 3, 1);
        $candidature = $this->candidature($r);

        $this->actingAs($this->agent)
            ->post(route('esbtp.candidatures.rejeter', $candidature->id), ['motif_rejet' => 'Dossier incomplet, bac non obtenu.']);

        $reference = $candidature->fresh()->assurerReferencePublique();
        $resultat = $this->reservateur()->reserver($reference, '2007-03-12', $autre->id);

        $this->assertFalse($resultat['ok']);
        $this->assertSame('introuvable', $resultat['code']);
        $this->assertSame(0, $autre->fresh()->placesPrises(), 'La place libérée reste aux autres familles.');
    }

    public function test_une_convocation_encore_en_file_ne_part_plus(): void
    {
        $r = $this->reservation($this->creneau('10:00', '10:30', 2), ['convocation_statut' => 'en_attente', 'convocation_action' => 'confirme']);

        $this->reservateur()->liberer($this->candidature($r));

        $this->assertSame(\App\Enums\StatutConvocationRdv::SansObjet, $r->fresh()->convocation_statut);
    }

    public function test_la_messagerie_ne_confirme_pas_une_place_qui_n_est_plus_tenue(): void
    {
        $this->mock(\App\Services\RendezVous\CourrielConvocationRdv::class, fn ($m) => $m->shouldNotReceive('expedier'));
        $r = $this->reservation($this->creneau('10:00', '10:30', 2), [
            'statut' => 'liberee', 'convocation_statut' => 'echec', 'convocation_action' => 'confirme',
        ]);

        $this->assertNull(app(\App\Services\RendezVous\MessagerieRdv::class)->envoyer($r->fresh()));
        $this->assertSame(\App\Enums\StatutConvocationRdv::SansObjet, $r->fresh()->convocation_statut);
    }

    public function test_sans_rendez_vous_le_message_ne_dit_rien_de_plus(): void
    {
        $candidature = DB::table('esbtp_candidatures')->insertGetId($this->ligneCandidature());

        $this->actingAs($this->agent)
            ->post(route('esbtp.candidatures.rejeter', $candidature), ['motif_rejet' => 'Dossier incomplet, bac non obtenu.'])
            ->assertSessionHas('success', 'Candidature rejetée.');
    }

    private function reservateur(): ReservateurRdv
    {
        return app(ReservateurRdv::class);
    }

    private function candidature(ESBTPRdvReservation $r): ESBTPCandidature
    {
        return ESBTPCandidature::findOrFail($r->candidature_id);
    }

    private function creneau(string $debut, string $fin, int $dansJours = 0, int $capacite = 4): ESBTPRdvCreneau
    {
        return ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee,
            'date' => Carbon::today()->addDays($dansJours)->toDateString(),
            'heure_debut' => $debut.':00', 'heure_fin' => $fin.':00',
            'capacite' => $capacite, 'ouvert' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function ligneCandidature(): array
    {
        return [
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'date_naissance' => '2007-03-12',
            'telephone' => '+22507'.sprintf('%08d', ++$this->numero), 'email' => 'famille'.$this->numero.'@exemple.ci',
            'annee_universitaire_id' => $this->annee, 'consentement_at' => now(), 'statut' => 'en_attente',
            'tuteur_nom' => 'Kouassi Paul', 'tuteur_telephone' => '+2250505050505', 'tuteur_lien' => 'Père',
            'created_at' => now(), 'updated_at' => now(),
        ];
    }

    private function reservation(ESBTPRdvCreneau $creneau, array $attributs = []): ESBTPRdvReservation
    {
        $ligne = $this->ligneCandidature();
        $candidature = DB::table('esbtp_candidatures')->insertGetId($ligne);

        return ESBTPRdvReservation::create(array_merge([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature, 'statut' => 'confirmee',
            'nom' => $ligne['nom'], 'prenoms' => 'Ama', 'telephone' => $ligne['telephone'],
            'date_naissance' => '2007-03-12', 'email' => $ligne['email'],
        ], $attributs));
    }
}
