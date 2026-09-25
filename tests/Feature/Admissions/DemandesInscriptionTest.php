<?php

namespace Tests\Feature\Admissions;

use App\Domain\Admissions\DemandeDInscription;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Une seule file pour les candidatures et les demandes de reinscription, et un
 * chemin qui va jusqu'a l'inscription sans quitter l'ecran.
 */
class DemandesInscriptionTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        'admin.access', 'inscriptions.view', 'inscriptions.create',
        'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
        'reinscriptions.demandes.view', 'reinscriptions.demandes.process',
        'inscriptions.rdv.view', 'inscriptions.rdv.manage', 'inscriptions.rdv.accueil', 'students.view',
    ];

    private User $agent;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $niveau;

    private int $numero = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:15:00');
        Mail::fake();
        Notification::fake();
        Cache::flush();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        // `paiements.create` est lue par le flux d'inscription (evenement de workflow).
        foreach ([...self::PERMISSIONS, 'paiements.create'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(self::PERMISSIONS);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2026-2027', 'is_current' => true]);
        $this->filiere = ESBTPFiliere::factory()->create(['name' => 'Génie civil']);
        $this->niveau = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 1', 'year' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_file_montre_les_deux_sortes_de_demandes(): void
    {
        $this->candidature(['nom' => 'TRAORE']);
        $this->demande('YAO');

        $this->actingAs($this->agent)->get(route('esbtp.demandes.index'))
            ->assertOk()
            ->assertSee('TRAORE')
            ->assertSee('YAO')
            ->assertSee('Nouvelle')
            ->assertSee('Réinscription');
    }

    public function test_un_agent_ne_voit_que_les_demandes_qu_il_a_le_droit_de_lire(): void
    {
        $this->candidature(['nom' => 'TRAORE']);
        $this->demande('YAO');
        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo(['admin.access', 'inscriptions.candidatures.view']);

        $this->actingAs($lecteur)->get(route('esbtp.demandes.index'))
            ->assertOk()->assertSee('TRAORE')->assertDontSee('YAO');

        $demande = ESBTPReinscriptionDemande::first();
        $this->actingAs($lecteur)->getJson(route('esbtp.demandes.dossier', ['reinscription', $demande->id]))->assertForbidden();
    }

    public function test_une_candidature_acceptee_reste_a_traiter_et_une_inscrite_n_y_est_plus(): void
    {
        $this->candidature(['nom' => 'ACCEPTEE', 'statut' => 'acceptee']);
        $this->candidature(['nom' => 'INSCRITE', 'statut' => 'convertie', 'traite_at' => now()]);

        $this->actingAs($this->agent)->get(route('esbtp.demandes.index'))
            ->assertSee('ACCEPTEE')->assertDontSee('INSCRITE');

        $this->actingAs($this->agent)->get(route('esbtp.demandes.index', ['etat' => 'inscrites']))
            ->assertSee('INSCRITE')->assertDontSee('ACCEPTEE');
    }

    public function test_la_recherche_trouve_une_reinscription_par_matricule_et_une_candidature_par_reference(): void
    {
        $c = $this->candidature(['nom' => 'TRAORE']);
        $this->candidature(['nom' => 'AUTRE']);
        $this->demande('YAO', 'MESBTP24-0112');
        $reference = $c->assurerReferencePublique();

        $this->actingAs($this->agent)->getJson(route('esbtp.demandes.index', ['q' => 'MESBTP24-0112', 'fragment' => 1]))
            ->assertOk()->assertSee('YAO')->assertDontSee('TRAORE');
        $this->actingAs($this->agent)->getJson(route('esbtp.demandes.index', ['q' => strtolower($reference), 'fragment' => 1]))
            ->assertOk()->assertSee('TRAORE')->assertDontSee('AUTRE');
    }

    public function test_une_famille_recue_au_guichet_passe_en_tete_et_se_compte(): void
    {
        $this->candidature(['nom' => 'ATTEND']);
        $recue = $this->candidature(['nom' => 'RECUE']);
        $this->reserver($recue, 0, StatutReservationRdv::Honoree);

        $fragment = $this->actingAs($this->agent)->getJson(route('esbtp.demandes.index', ['fragment' => 1, 'compteurs' => 1]))->assertOk()->json();

        $this->assertSame(1, $fragment['compteurs']['recues']);
        $this->assertLessThan(strpos($fragment['liste'], 'ATTEND'), strpos($fragment['liste'], 'RECUE'));
    }

    public function test_la_liste_se_charge_par_tranches(): void
    {
        foreach (range(1, 27) as $i) {
            $this->candidature(['nom' => 'C'.$i]);
        }

        $this->actingAs($this->agent)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.demandes.index', ['page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.total', 27);
    }

    public function test_la_preparation_met_la_classe_du_voeu_en_tete_avec_ses_places(): void
    {
        $voeu = $this->classe('1A BTS Génie civil', 30);
        $this->classe('AAA Autre classe', 30, ESBTPFiliere::factory()->create()->id);
        $c = $this->candidature(['nom' => 'TRAORE', 'filiere_id' => $this->filiere->id, 'niveau_id' => $this->niveau->id]);

        $prep = $this->actingAs($this->agent)->getJson(route('esbtp.demandes.preparer-inscription', $c))->assertOk()->json();

        $this->assertSame($voeu->id, $prep['classes'][0]['id']);
        $this->assertTrue($prep['classes'][0]['voeu']);
        $this->assertSame(30, $prep['classes'][0]['places_libres']);
        $this->assertSame('TRAORE', $prep['identite']['nom']);
    }

    public function test_accepter_et_inscrire_cree_l_inscription_ferme_la_candidature_et_honore_le_rendez_vous(): void
    {
        $classe = $this->classe('1A BTS Génie civil', 30);
        // En attente : inscrire vaut acceptation, dans la meme requete.
        $c = $this->candidature(['nom' => 'TRAORE', 'prenoms' => 'Issouf']);
        $rdv = $this->reserver($c, 0);

        $reponse = $this->actingAs($this->agent)->postJson(route('esbtp.inscriptions.store'), [
            'nom' => 'TRAORE', 'prenoms' => 'Issouf', 'sexe' => 'M', 'date_naissance' => '2007-03-12',
            'telephone' => '+225 07 07 12 34 56', 'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id, 'candidature_id' => $c->id, 'duplicate_override' => 0,
            'parents' => [['type' => 'nouveau', 'nom' => 'TRAORE', 'prenoms' => 'Mamadou', 'telephone' => '+225 05 05 44 21 10', 'relation' => 'Père']],
        ])->assertOk()->assertJsonPath('ok', true);

        $c->refresh();
        $this->assertSame('convertie', $c->statut);
        $this->assertNotNull($c->inscription_id);
        $this->assertSame(route('esbtp.inscriptions.show', $c->inscription_id), $reponse->json('redirect'));
        $this->assertSame(StatutReservationRdv::Honoree, $rdv->fresh()->statut);
    }

    public function test_un_refus_de_l_inscription_revient_en_json_sans_laisser_de_message_en_session(): void
    {
        $classe = $this->classe('1A BTS Génie civil', 1);
        ESBTPEtudiant::factory()->create(['nom' => 'TRAORE', 'prenoms' => 'Issouf', 'date_naissance' => '2007-03-12', 'sexe' => 'M']);
        $c = $this->candidature(['nom' => 'TRAORE', 'prenoms' => 'Issouf']);

        $this->actingAs($this->agent)->postJson(route('esbtp.inscriptions.store'), [
            'nom' => 'TRAORE', 'prenoms' => 'Issouf', 'sexe' => 'M', 'date_naissance' => '2007-03-12',
            'telephone' => '0707123456', 'classe_id' => $classe->id, 'candidature_id' => $c->id,
        ])->assertStatus(422)->assertJsonPath('ok', false)->assertJsonStructure(['errors' => ['duplicate'], 'doublons']);

        $this->assertFalse(session()->has('errors'));
        // Refusee, l'inscription ne laisse pas la candidature a moitie decidee.
        $this->assertSame('en_attente', $c->fresh()->statut);
    }

    public function test_une_inscription_libere_un_rendez_vous_encore_a_venir(): void
    {
        $classe = $this->classe('1A BTS Génie civil', 30);
        $c = $this->candidature(['nom' => 'TRAORE', 'prenoms' => 'Issouf', 'statut' => 'acceptee']);
        $rdv = $this->reserver($c, 3);

        $this->actingAs($this->agent)->postJson(route('esbtp.inscriptions.store'), [
            'nom' => 'TRAORE', 'prenoms' => 'Issouf', 'sexe' => 'M', 'date_naissance' => '2007-03-12',
            'telephone' => '0707123456', 'classe_id' => $classe->id, 'candidature_id' => $c->id,
        ])->assertOk();

        $this->assertSame(StatutReservationRdv::Liberee, $rdv->fresh()->statut);
    }

    public function test_accepter_et_rejeter_repondent_en_json(): void
    {
        $a = $this->candidature(['nom' => 'A']);
        $b = $this->candidature(['nom' => 'B']);

        $this->actingAs($this->agent)->postJson(route('esbtp.candidatures.accepter', $a))->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('acceptee', $a->fresh()->statut);

        $this->actingAs($this->agent)->postJson(route('esbtp.candidatures.rejeter', $b), ['motif_rejet' => 'court'])->assertStatus(422);
        $this->actingAs($this->agent)->postJson(route('esbtp.candidatures.rejeter', $b), ['motif_rejet' => 'Relevé du bac manquant'])->assertOk();
        $this->assertSame('rejetee', $b->fresh()->statut);

        $this->actingAs($this->agent)->postJson(route('esbtp.candidatures.accepter', $b))->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_une_candidature_acceptee_se_rejette_encore(): void
    {
        $c = $this->candidature(['nom' => 'RENONCE', 'statut' => 'acceptee']);

        $this->actingAs($this->agent)->postJson(route('esbtp.candidatures.rejeter', $c), ['motif_rejet' => 'La famille a renoncé à l\'inscription'])
            ->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('rejetee', $c->fresh()->statut);
    }

    public function test_une_classe_sans_capacite_reglee_est_complete_comme_a_l_enregistrement(): void
    {
        $sansCapacite = $this->classe('Sans capacité', 0);
        $c = $this->candidature(['nom' => 'TRAORE', 'filiere_id' => $this->filiere->id, 'niveau_id' => $this->niveau->id]);

        $prep = $this->actingAs($this->agent)->getJson(route('esbtp.demandes.preparer-inscription', $c))->assertOk()->json();
        $ligne = collect($prep['classes'])->firstWhere('id', $sansCapacite->id);

        $this->assertTrue($ligne['complete']);
        $this->assertSame(0, $sansCapacite->places_disponibles);
    }

    public function test_le_compte_du_menu_se_relit_des_qu_une_demande_arrive_ou_change(): void
    {
        $c = $this->candidature(['nom' => 'PREMIERE']);
        $this->assertSame(1, \App\Domain\Admissions\FileDesDemandes::aTraiter($this->agent));

        $this->candidature(['nom' => 'SECONDE']);
        $this->assertSame(2, \App\Domain\Admissions\FileDesDemandes::aTraiter($this->agent));

        $c->update(['statut' => 'rejetee', 'motif_rejet' => 'Dossier incomplet ce jour']);
        $this->assertSame(1, \App\Domain\Admissions\FileDesDemandes::aTraiter($this->agent));
    }

    public function test_rejeter_une_reinscription_remet_le_compte_du_menu_a_jour(): void
    {
        $demande = $this->demande('YAO');
        $this->assertSame(1, \App\Domain\Admissions\FileDesDemandes::aTraiter($this->agent));

        $this->actingAs($this->agent)->postJson(route('esbtp.reinscription-demandes.rejeter', $demande), ['motif_rejet' => 'Demande déposée en double'])
            ->assertOk();

        $this->assertSame(0, \App\Domain\Admissions\FileDesDemandes::aTraiter($this->agent));
    }

    public function test_reinscrire_depuis_la_file_repond_en_json_et_honore_le_rendez_vous(): void
    {
        $passee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'is_current' => false]);
        $demande = $this->demande('YAO');
        \App\Models\ESBTPInscription::factory()->create([
            'etudiant_id' => $demande->etudiant_id, 'classe_id' => $this->classe('1A BTS', 30)->id,
            'filiere_id' => $this->filiere->id, 'niveau_id' => $this->niveau->id,
            'annee_universitaire_id' => $passee->id, 'status' => 'active',
        ]);
        $cible = $this->classe('2A BTS', 30);
        $rdv = ESBTPRdvReservation::create([
            'creneau_id' => $this->creneau(0)->id, 'reinscription_demande_id' => $demande->id, 'statut' => 'confirmee',
            'nom' => 'YAO', 'prenoms' => 'Serge', 'telephone' => '+2250700000000', 'date_naissance' => '2006-01-01',
        ]);

        $this->assertSame(1, \App\Domain\Admissions\FileDesDemandes::aTraiter($this->agent));

        $this->actingAs($this->agent)->postJson(route('esbtp.reinscription-demandes.convertir', $demande), [
            'classe_id' => $cible->id, 'decision' => 'passage',
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertSame('convertie', $demande->fresh()->statut);
        // Le statut change par requete directe : le compte du menu suit quand meme.
        $this->assertSame(0, \App\Domain\Admissions\FileDesDemandes::aTraiter($this->agent));
        $this->assertSame(StatutReservationRdv::Honoree, $rdv->fresh()->statut);
    }

    public function test_proposer_un_creneau_a_une_famille_sans_rendez_vous(): void
    {
        $c = $this->candidature(['nom' => 'SANSRDV']);
        $creneau = $this->creneau(2);

        $this->actingAs($this->agent)->postJson(route('esbtp.demandes.rendez-vous', ['nouvelle', $c->id]), ['creneau_id' => $creneau->id])
            ->assertOk();

        $this->assertSame(1, ESBTPRdvReservation::where('candidature_id', $c->id)->where('creneau_id', $creneau->id)->count());
        $this->actingAs($this->agent)->postJson(route('esbtp.demandes.rendez-vous', ['nouvelle', $c->id]), ['creneau_id' => $creneau->id])
            ->assertStatus(422)->assertJsonPath('message', 'Cette famille a déjà un rendez-vous.');
    }

    public function test_le_panneau_raconte_le_parcours_du_dossier(): void
    {
        $c = $this->candidature(['nom' => 'TRAORE']);
        $this->reserver($c, 0, StatutReservationRdv::Honoree);

        $html = $this->actingAs($this->agent)->getJson(route('esbtp.demandes.dossier', ['nouvelle', $c->id]))->assertOk()->json('html');

        $this->assertStringContainsString('Reçue au guichet', $html);
        $this->assertStringContainsString('Accepter et inscrire', $html);
    }

    public function test_le_lien_de_l_accueil_ouvre_le_dossier_dans_la_file(): void
    {
        $c = $this->candidature(['nom' => 'TRAORE']);

        $this->assertStringContainsString('ouvrir=nouvelle-'.$c->id, DemandeDInscription::lien($c->id, null));
        $this->actingAs($this->agent)->get(DemandeDInscription::lien($c->id, null, true))->assertOk()->assertSee('nouvelle-'.$c->id, false);
    }

    /* ─────────────── Donnees ─────────────── */

    private function candidature(array $valeurs = []): ESBTPCandidature
    {
        $n = ++$this->numero;

        return ESBTPCandidature::forceCreate(array_merge([
            'nom' => 'CANDIDAT'.$n, 'prenoms' => 'Ama', 'date_naissance' => '2007-03-12', 'sexe' => 'M',
            'telephone' => '+22507'.sprintf('%08d', $n), 'email' => 'famille'.$n.'@exemple.ci',
            'annee_universitaire_id' => $this->annee->id, 'consentement_at' => now(), 'statut' => 'en_attente',
        ], $valeurs));
    }

    private function demande(string $nom, ?string $matricule = null): ESBTPReinscriptionDemande
    {
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => $nom, 'prenoms' => 'Serge', 'matricule' => $matricule ?? 'MAT-'.(++$this->numero)]);

        return ESBTPReinscriptionDemande::forceCreate([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $this->annee->id,
            'statut' => 'en_attente', 'consentement_at' => now(),
        ]);
    }

    private function classe(string $nom, int $places, ?int $filiere = null): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'name' => $nom, 'filiere_id' => $filiere ?? $this->filiere->id, 'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id, 'places_totales' => $places, 'is_active' => true,
        ]);
    }

    private function creneau(int $dansJours): ESBTPRdvCreneau
    {
        return ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee->id, 'date' => Carbon::today()->addDays($dansJours)->toDateString(),
            'heure_debut' => '10:00:00', 'heure_fin' => '10:30:00', 'capacite' => 4, 'ouvert' => true,
        ]);
    }

    private function reserver(ESBTPCandidature $c, int $dansJours, StatutReservationRdv $statut = StatutReservationRdv::Confirmee): ESBTPRdvReservation
    {
        return ESBTPRdvReservation::create([
            'creneau_id' => $this->creneau($dansJours)->id, 'candidature_id' => $c->id, 'statut' => $statut->value,
            'nom' => $c->nom, 'prenoms' => $c->prenoms, 'telephone' => $c->telephone, 'date_naissance' => '2007-03-12',
            'accueilli_at' => $statut === StatutReservationRdv::Honoree ? now() : null,
        ]);
    }
}
