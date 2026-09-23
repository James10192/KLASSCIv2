<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\RendezVous\AccueilRdv;
use App\Services\RendezVous\FamillesAPrevenirRdv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Le guichet peut dire, en fin de journee, qui est venu et qui n'a pas ete pris
 * en charge. Avant, les statuts « honoree » et « manquee » existaient sans que
 * rien ne les pose : venue ou absente, une famille restait « confirmee ».
 */
class AccueilDuJourTest extends TestCase
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

        foreach (['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.accueil'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.accueil']);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_une_famille_cochee_est_recue_par_l_agent_a_l_heure_dite(): void
    {
        $r = $this->reservation($this->creneau('09:00', '09:30'));

        $this->assertNull($this->accueil()->marquerRecu($r, $this->agent->id));

        $r->refresh();
        $this->assertSame(StatutReservationRdv::Honoree, $r->statut);
        $this->assertSame($this->agent->id, (int) $r->accueilli_par);
        $this->assertSame(self::MAINTENANT, $r->accueilli_at->format('Y-m-d H:i:s'));
    }

    public function test_on_ne_coche_pas_un_rendez_vous_d_un_autre_jour(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30', 1));

        $this->assertSame('Ce rendez-vous est pour un autre jour.', $this->accueil()->marquerRecu($r, $this->agent->id));
        $this->assertSame(StatutReservationRdv::Confirmee, $r->fresh()->statut);
    }

    public function test_absente_seulement_une_fois_le_creneau_commence_et_l_absence_est_comptee(): void
    {
        $aVenir = $this->reservation($this->creneau('11:00', '11:30'));
        $this->assertNotNull($this->accueil()->marquerAbsent($aVenir, $this->agent->id));
        $this->assertSame(StatutReservationRdv::Confirmee, $aVenir->fresh()->statut);

        $creneau = $this->creneau('08:00', '08:30');
        $r = $this->reservation($creneau);
        $this->assertNull($this->accueil()->marquerAbsent($r, $this->agent->id));

        $r->refresh();
        $this->assertSame(StatutReservationRdv::Manquee, $r->statut);
        $this->assertSame(1, $r->absences);
        $this->assertSame($creneau->id, (int) $r->dernier_creneau_manque_id);
    }

    public function test_annuler_une_absence_rend_la_famille_attendue_et_decompte_l_absence(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30'));
        $this->accueil()->marquerAbsent($r, $this->agent->id);

        $this->assertNull($this->accueil()->annulerMarque($r));

        $r->refresh();
        $this->assertSame(StatutReservationRdv::Confirmee, $r->statut);
        $this->assertSame(0, $r->absences);
        $this->assertNull($r->dernier_creneau_manque_id);
        $this->assertNull($r->accueilli_at);
    }

    public function test_cloturer_ne_touche_que_les_familles_attendues_des_creneaux_termines(): void
    {
        $fini = $this->creneau('08:00', '08:30');
        $absente = $this->reservation($fini);
        $recue = $this->reservation($fini, ['nom' => 'VENUE']);
        $this->accueil()->marquerRecu($recue, $this->agent->id);
        $enCours = $this->reservation($this->creneau('09:00', '09:30'));

        $this->assertSame(1, $this->accueil()->cloturer(Carbon::today(), $this->agent->id));

        $this->assertSame(StatutReservationRdv::Manquee, $absente->fresh()->statut);
        $this->assertSame(StatutReservationRdv::Honoree, $recue->fresh()->statut);
        $this->assertSame(StatutReservationRdv::Confirmee, $enCours->fresh()->statut, 'Un créneau en cours ne se clôture pas.');
    }

    public function test_reprogrammer_deplace_renvoie_la_convocation_et_garde_la_trace_de_l_absence(): void
    {
        $manque = $this->creneau('08:00', '08:30');
        $r = $this->reservation($manque);
        $this->accueil()->marquerAbsent($r, $this->agent->id);
        $demain = $this->creneau('08:00', '08:30', 1);

        $this->assertNull($this->accueil()->reprogrammer($r->fresh(), $demain->id));

        $r->refresh();
        $this->assertSame($demain->id, (int) $r->creneau_id);
        $this->assertSame(StatutReservationRdv::Confirmee, $r->statut);
        $this->assertSame(1, $r->absences, 'L\'absence reste comptée après reprogrammation.');
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->convocation_statut);
        $this->assertSame('deplace', $r->convocation_action);

        $journee = $this->accueil()->journee(Carbon::today());
        $this->assertSame([$r->id], $journee['reprogrammees']->pluck('id')->all(), 'Le jour de l\'absence garde la trace de la famille.');
        $this->assertSame(1, $journee['compteurs']['reprogrammees']);
    }

    public function test_reprogrammee_plus_tard_le_meme_jour_n_apparait_qu_une_fois(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30'));
        $this->accueil()->marquerAbsent($r, $this->agent->id);
        $this->accueil()->reprogrammer($r->fresh(), $this->creneau('11:00', '11:30')->id);

        $journee = $this->accueil()->journee(Carbon::today());

        $this->assertTrue($journee['reprogrammees']->isEmpty(), 'Toujours attendue ce jour-là : elle est dans son nouveau créneau, pas dans la trace.');
        $this->assertSame([$r->id], $journee['creneaux']->flatMap->reservations->pluck('id')->all());
    }

    public function test_reprogrammer_sur_un_creneau_plein_est_refuse_sans_rien_changer(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30'));
        $plein = $this->creneau('09:00', '09:30', 1, 1);
        $this->reservation($plein, ['nom' => 'OCCUPANT']);

        $this->assertSame('Ce créneau vient d\'être rempli. Choisissez-en un autre.', $this->accueil()->reprogrammer($r, $plein->id));
        $this->assertSame((int) $r->creneau_id, (int) $r->fresh()->creneau_id);
    }

    public function test_en_retard_au_dela_de_la_tolerance(): void
    {
        $r = $this->reservation($this->creneau('09:00', '09:30'));
        $this->assertFalse($this->accueil()->enRetard($r->fresh()), '10 minutes après le début : dans la tolérance de 15.');

        Carbon::setTestNow('2026-10-05 09:16:00');
        $this->assertTrue($this->accueil()->enRetard($r->fresh()));
    }

    public function test_familles_a_prevenir_ne_retient_que_les_rendez_vous_a_venir_sans_courriel_recu(): void
    {
        $demain = $this->creneau('08:00', '08:30', 1);
        $this->reservation($demain, ['nom' => 'SANSMAIL', 'convocation_statut' => 'sans_email']);
        $this->reservation($demain, ['nom' => 'ECHEC', 'convocation_statut' => 'echec', 'convocation_erreur' => 'Adresse refusée']);
        $this->reservation($demain, ['nom' => 'ANCIENNE', 'convocation_statut' => null]);
        $this->reservation($demain, ['nom' => 'PARTIE', 'convocation_statut' => 'envoyee']);
        $this->reservation($demain, ['nom' => 'ATTENTE', 'convocation_statut' => 'en_attente']);
        $this->reservation($this->creneau('09:00', '09:30'), ['nom' => 'COMMENCE', 'convocation_statut' => 'sans_email']);
        $this->reservation($this->creneau('11:00', '11:30'), ['nom' => 'CETAPRESMIDI', 'convocation_statut' => 'sans_email']);

        $lignes = app(FamillesAPrevenirRdv::class)->lignes();

        $this->assertSame(['CETAPRESMIDI', 'ANCIENNE', 'ECHEC', 'SANSMAIL'], array_column($lignes, 'nom'));
        $this->assertSame('Envoi refusé : Adresse refusée', $lignes[2]['motif']);
        $this->assertSame('+2250505050505', $lignes[3]['contact2_telephone'], 'Le tuteur de la candidature est le second contact.');
    }

    public function test_l_ecran_repond_en_json_et_coche_sans_rechargement(): void
    {
        $r = $this->reservation($this->creneau('09:00', '09:30'));

        $this->actingAs($this->agent)->getJson(route('esbtp.rendez-vous.accueil.index', ['fragment' => 1]))
            ->assertOk()->assertJsonStructure(['kpis', 'liste']);

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.accueil.recu', $r))
            ->assertOk()->assertJson(['message' => 'Famille reçue.']);

        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.accueil.index'))
            ->assertOk()->assertSee('Accueil du jour')->assertSee('Reçue à 09:10');
    }

    public function test_sans_la_permission_accueil_l_ecran_est_ferme(): void
    {
        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo(['admin.access', 'inscriptions.rdv.view']);
        $r = $this->reservation($this->creneau('09:00', '09:30'));

        $this->actingAs($lecteur)->get(route('esbtp.rendez-vous.accueil.index'))->assertForbidden();
        $this->actingAs($lecteur)->postJson(route('esbtp.rendez-vous.accueil.recu', $r))->assertForbidden();
        $this->assertSame(StatutReservationRdv::Confirmee, $r->fresh()->statut);
    }

    public function test_l_export_des_familles_a_prevenir_se_telecharge(): void
    {
        $this->reservation($this->creneau('08:00', '08:30', 1), ['convocation_statut' => 'sans_email']);

        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.familles.apercu'))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.familles.excel'))->assertOk();
    }

    private function accueil(): AccueilRdv
    {
        return app(AccueilRdv::class);
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

    private function reservation(ESBTPRdvCreneau $creneau, array $attributs = []): ESBTPRdvReservation
    {
        $nom = $attributs['nom'] ?? 'KOUASSI';
        $telephone = '+22507'.sprintf('%08d', ++$this->numero);
        $candidature = DB::table('esbtp_candidatures')->insertGetId([
            'nom' => $nom, 'prenoms' => 'Ama', 'date_naissance' => '2007-03-12',
            'telephone' => $telephone, 'email' => 'famille'.$this->numero.'@exemple.ci',
            'annee_universitaire_id' => $this->annee, 'consentement_at' => now(), 'statut' => 'en_attente',
            'tuteur_nom' => 'Kouassi Paul', 'tuteur_telephone' => '+2250505050505', 'tuteur_lien' => 'Père',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ESBTPRdvReservation::create(array_merge([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature, 'statut' => 'confirmee',
            'nom' => $nom, 'prenoms' => 'Ama', 'telephone' => $telephone,
            'date_naissance' => '2007-03-12', 'email' => 'famille@exemple.ci',
            'convocation_statut' => 'envoyee', 'convocation_action' => 'confirme',
        ], $attributs));
    }
}
