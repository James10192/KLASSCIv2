<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReprogrammation;
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

    public function test_l_absence_se_deduit_sans_clic_et_le_dossier_avance_n_est_pas_absent(): void
    {
        $fini = $this->creneau('08:00', '08:30');
        $nonVenue = $this->reservation($fini);
        $traitee = $this->reservation($fini, ['nom' => 'INSCRITE'], 'convertie');
        $enCours = $this->reservation($this->creneau('09:00', '09:30'));

        $this->assertSame(AccueilRdv::NON_VENUE, $this->accueil()->etat($nonVenue->fresh()));
        $this->assertSame(AccueilRdv::TRAITEE, $this->accueil()->etat($traitee->fresh()), 'Un dossier qui a avancé n\'est pas une absence.');
        $this->assertSame(AccueilRdv::ATTENDUE, $this->accueil()->etat($enCours->fresh()));

        $compteurs = $this->accueil()->journee(Carbon::today())['compteurs'];
        $this->assertSame([1, 1, 1], [$compteurs['non_venues'], $compteurs['traitees'], $compteurs['a_recevoir']]);
    }

    public function test_une_famille_arrivee_apres_son_creneau_se_coche_encore(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30'));

        $this->assertNull($this->accueil()->marquerRecu($r, $this->agent->id));
        $this->assertSame(AccueilRdv::RECUE, $this->accueil()->etat($r->fresh()));
    }

    public function test_decocher_rend_la_famille_attendue(): void
    {
        $r = $this->reservation($this->creneau('09:00', '09:30'));
        $this->accueil()->marquerRecu($r, $this->agent->id);

        $this->assertNull($this->accueil()->annulerRecu($r->fresh()));

        $r->refresh();
        $this->assertSame(StatutReservationRdv::Confirmee, $r->statut);
        $this->assertNull($r->accueilli_at);
        $this->assertNull($r->accueilli_par);
    }

    public function test_cocher_une_famille_deplacee_par_un_autre_poste_est_refuse(): void
    {
        $r = $this->reservation($this->creneau('09:00', '09:30'));
        $vu = $r->creneau_id;
        $this->accueil()->reprogrammer($r, $this->creneau('11:00', '11:30')->id, $this->agent->id);

        $this->actingAs($this->agent)
            ->postJson(route('esbtp.rendez-vous.accueil.recu', $r), ['creneau_id' => $vu])
            ->assertStatus(422)->assertJsonFragment(['message' => 'Ce rendez-vous vient d\'être déplacé par un autre poste. La liste est rechargée.']);
        $this->assertSame(StatutReservationRdv::Confirmee, $r->fresh()->statut);
    }

    public function test_sous_verrou_une_instance_perimee_ne_coche_pas_le_nouveau_creneau(): void
    {
        $perimee = $this->reservation($this->creneau('09:00', '09:30'));
        $this->accueil()->reprogrammer(ESBTPRdvReservation::find($perimee->id), $this->creneau('11:00', '11:30')->id, $this->agent->id);

        $this->assertSame('Ce rendez-vous vient d\'être déplacé par un autre poste. La liste est rechargée.', $this->accueil()->marquerRecu($perimee, $this->agent->id));
        $this->assertSame(StatutReservationRdv::Confirmee, $perimee->fresh()->statut);
    }

    public function test_reprogrammer_une_non_venue_la_journalise_et_renvoie_la_convocation(): void
    {
        $manque = $this->creneau('08:00', '08:30');
        $r = $this->reservation($manque);
        $demain = $this->creneau('08:00', '08:30', 1);

        $this->assertNull($this->accueil()->reprogrammer($r, $demain->id, $this->agent->id));

        $r->refresh();
        $this->assertSame($demain->id, (int) $r->creneau_id);
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->convocation_statut);
        $this->assertSame('deplace', $r->convocation_action);
        $journal = ESBTPRdvReprogrammation::where('reservation_id', $r->id)->sole();
        $this->assertTrue($journal->non_venue);
        $this->assertSame($manque->id, (int) $journal->creneau_quitte_id);
        $this->assertSame($this->agent->id, (int) $journal->par);

        $journee = $this->accueil()->journee(Carbon::today());
        $this->assertSame([$r->id], $journee['reprogrammees']->pluck('id')->all(), 'Le jour de l\'absence garde la trace de la famille.');
    }

    public function test_une_seconde_absence_n_efface_pas_la_trace_du_premier_jour(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30'));
        $demain = $this->creneau('08:00', '08:30', 1);
        $this->accueil()->reprogrammer($r, $demain->id, $this->agent->id);

        Carbon::setTestNow('2026-10-06 09:00:00');
        $this->accueil()->reprogrammer($r->fresh(), $this->creneau('08:00', '08:30', 2)->id, $this->agent->id);

        $this->assertSame([$r->id], $this->accueil()->journee(Carbon::parse('2026-10-05'))['reprogrammees']->pluck('id')->all());
        $this->assertSame([$r->id], $this->accueil()->journee(Carbon::parse('2026-10-06'))['reprogrammees']->pluck('id')->all());
        $this->assertSame(2, $r->reprogrammations()->where('non_venue', true)->count());
    }

    public function test_reprogrammee_plus_tard_le_meme_jour_n_apparait_qu_une_fois(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30'));
        $this->accueil()->reprogrammer($r, $this->creneau('11:00', '11:30')->id, $this->agent->id);

        $journee = $this->accueil()->journee(Carbon::today());

        $this->assertTrue($journee['reprogrammees']->isEmpty(), 'Toujours attendue ce jour-là : elle est dans son nouveau créneau, pas dans la trace.');
        $this->assertSame([$r->id], $journee['creneaux']->flatMap->reservations->pluck('id')->all());
    }

    public function test_reprogrammer_sur_un_creneau_plein_ou_une_famille_recue_est_refuse(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30'));
        $plein = $this->creneau('09:00', '09:30', 1, 1);
        $this->reservation($plein, ['nom' => 'OCCUPANT']);

        $this->assertSame('Ce créneau vient d\'être rempli. Choisissez-en un autre.', $this->accueil()->reprogrammer($r, $plein->id));
        $this->assertSame((int) $r->creneau_id, (int) $r->fresh()->creneau_id);

        $this->accueil()->marquerRecu($r, $this->agent->id);
        $this->assertSame('Cette famille a déjà été reçue : il n\'y a rien à reprogrammer.', $this->accueil()->reprogrammer($r->fresh(), $this->creneau('10:00', '10:30', 1)->id));
        $this->assertSame(0, ESBTPRdvReprogrammation::count());
    }

    public function test_reprogrammer_les_non_venues_s_arrete_quand_il_n_y_a_plus_de_place(): void
    {
        $this->configurerCreneaux();
        $fini = $this->creneau('08:00', '08:30');
        $this->reservation($fini, ['nom' => 'UN']);
        $this->reservation($fini, ['nom' => 'DEUX']);
        $this->reservation($fini, ['nom' => 'RECUE', 'statut' => 'honoree']);
        $seule = $this->creneau('08:00', '08:30', 1, 1);

        $rapport = $this->accueil()->reprogrammerNonVenues(Carbon::today(), $this->agent->id);

        $this->assertSame(['faites' => 1, 'sans_place' => 1], $rapport);
        $this->assertSame(1, $seule->reservations()->count());
    }

    public function test_un_jour_passe_oublie_est_signale(): void
    {
        $avantHier = $this->creneau('08:00', '08:30', -2);
        $this->reservation($avantHier);
        $this->reservation($avantHier, ['nom' => 'VENUE', 'statut' => 'honoree']);
        $this->reservation($this->creneau('08:00', '08:30', -3), ['nom' => 'INSCRITE'], 'convertie');

        $jours = $this->accueil()->joursEnSouffrance(Carbon::today());

        $this->assertSame([['jour' => Carbon::today()->subDays(2)->toDateString(), 'n' => 1]], $jours->map(fn ($j) => (array) $j)->all());
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

    public function test_une_famille_prevenue_par_telephone_sort_de_la_liste_et_y_revient_si_on_annule(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30', 1), ['nom' => 'SANSMAIL', 'email' => null, 'convocation_statut' => 'sans_email']);
        $familles = app(FamillesAPrevenirRdv::class);

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.accueil.prevenue', $r))->assertOk();

        $r->refresh();
        $this->assertSame(StatutConvocationRdv::Telephone, $r->convocation_statut);
        $this->assertSame($this->agent->id, (int) $r->prevenue_par);
        $this->assertSame(0, $familles->compter());

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.accueil.prevenue.annuler', $r))->assertOk();
        $this->assertSame(StatutConvocationRdv::SansEmail, $r->fresh()->convocation_statut);
        $this->assertNull($r->fresh()->prevenue_par);
        $this->assertSame(1, $familles->compter());
    }

    public function test_une_famille_deja_convoquee_ne_se_marque_pas_prevenue(): void
    {
        $r = $this->reservation($this->creneau('08:00', '08:30', 1));

        $this->assertNotNull(app(FamillesAPrevenirRdv::class)->marquerPrevenue($r, $this->agent->id));
        $this->assertSame(StatutConvocationRdv::Envoyee, $r->fresh()->convocation_statut);
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

    /** CatalogueCreneaux ne propose des places que si les reglages sont complets. */
    private function configurerCreneaux(): void
    {
        $this->mock(\App\Services\RendezVous\CatalogueCreneaux::class, function ($m) {
            $m->shouldReceive('placesLibres')->andReturnUsing(fn () => ESBTPRdvCreneau::query()
                ->where('ouvert', true)->whereDate('date', '>', Carbon::today())
                ->withCount(['reservations as prises' => fn ($q) => $q->occupantes()])
                ->orderBy('date')->orderBy('heure_debut')->get()
                ->mapWithKeys(fn ($c) => [$c->id => $c->capacite - $c->prises])
                ->filter(fn ($libre) => $libre > 0)->all());
        });
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

    private function reservation(ESBTPRdvCreneau $creneau, array $attributs = [], string $dossier = 'en_attente'): ESBTPRdvReservation
    {
        $nom = $attributs['nom'] ?? 'KOUASSI';
        $telephone = '+22507'.sprintf('%08d', ++$this->numero);
        $candidature = DB::table('esbtp_candidatures')->insertGetId([
            'nom' => $nom, 'prenoms' => 'Ama', 'date_naissance' => '2007-03-12',
            'telephone' => $telephone, 'email' => 'famille'.$this->numero.'@exemple.ci',
            'annee_universitaire_id' => $this->annee, 'consentement_at' => now(), 'statut' => $dossier,
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
