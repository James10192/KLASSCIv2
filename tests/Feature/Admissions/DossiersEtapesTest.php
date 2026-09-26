<?php

namespace Tests\Feature\Admissions;

use App\Domain\Admissions\DemandeDInscription;
use App\Domain\Admissions\EtapeDuDossier;
use App\Domain\Admissions\FileDesDemandes;
use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Admissions\Concerns\FabriqueDeDossiers;
use Tests\TestCase;

/**
 * La page « Dossiers » : une liste pour les candidatures et les reinscriptions,
 * et une etape par dossier, deduite des statuts existants. L'etape calculee
 * ligne par ligne (EtapeDuDossier) et celle calculee en SQL (EtapesEnSql, pour
 * compter et filtrer) doivent toujours tomber d'accord.
 */
class DossiersEtapesTest extends TestCase
{
    use FabriqueDeDossiers;
    use RefreshDatabase;

    private const PERMISSIONS = [
        'admin.access', 'inscriptions.view', 'inscriptions.create',
        'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
        'reinscriptions.demandes.view', 'reinscriptions.demandes.process',
        'inscriptions.rdv.view', 'inscriptions.rdv.manage', 'inscriptions.rdv.accueil', 'students.view',
    ];

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:15:00');
        Cache::flush();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (self::PERMISSIONS as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(self::PERMISSIONS);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2026-2027', 'is_current' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Un dossier par cas, avec l'etape attendue.
     *
     * @return array<string, ?EtapeDuDossier> indexe par cle de la liste
     */
    private function unDossierParCas(): array
    {
        $cas = [];
        $cas['nouvelle-'.$this->candidature(['nom' => 'EXAMEN'])->id] = EtapeDuDossier::AExaminer;
        $cas['nouvelle-'.$this->candidature(['nom' => 'CONTACT', 'verification_contact' => 'email_non_verifie'])->id] = EtapeDuDossier::ContactAConfirmer;

        $planifie = $this->candidature(['nom' => 'PLANIFIE']);
        $this->reserver($planifie, $this->creneau(1));
        $cas['nouvelle-'.$planifie->id] = EtapeDuDossier::RdvPlanifie;

        // Le contact non prouve passe devant le rendez-vous : c'est lui qui bloque la convocation.
        $contactEtRdv = $this->candidature(['nom' => 'CONTACTRDV', 'verification_contact' => 'telephone_non_verifie']);
        $this->reserver($contactEtRdv, $this->creneau(2));
        $cas['nouvelle-'.$contactEtRdv->id] = EtapeDuDossier::ContactAConfirmer;

        $recue = $this->candidature(['nom' => 'RECUE']);
        $this->reserver($recue, $this->creneau(0, '09:00', '09:30'), StatutReservationRdv::Honoree, now()->setTime(9, 5));
        $cas['nouvelle-'.$recue->id] = EtapeDuDossier::RecuAujourdhui;

        $hier = $this->candidature(['nom' => 'HIER']);
        $this->reserver($hier, $this->creneau(-1), StatutReservationRdv::Honoree, now()->subDay()->setTime(10, 5));
        $cas['nouvelle-'.$hier->id] = EtapeDuDossier::AFinaliser;

        $cas['nouvelle-'.$this->candidature(['nom' => 'ACCEPTEE', 'statut' => 'acceptee'])->id] = EtapeDuDossier::AFinaliser;

        // Non venue a un rendez-vous passe : le dossier revient a examiner.
        $absente = $this->candidature(['nom' => 'ABSENTE']);
        $this->reserver($absente, $this->creneau(-2));
        $cas['nouvelle-'.$absente->id] = EtapeDuDossier::AExaminer;

        $cas['nouvelle-'.$this->candidature(['nom' => 'INSCRITE', 'statut' => 'convertie', 'traite_at' => now()])->id] = EtapeDuDossier::Inscrit;
        $cas['nouvelle-'.$this->candidature(['nom' => 'REJETEE', 'statut' => 'rejetee', 'traite_at' => now(), 'motif_rejet' => 'Dossier incomplet'])->id] = null;

        $reins = $this->demande('REINSRDV');
        $this->reserver($reins, $this->creneau(3));
        $cas['reinscription-'.$reins->id] = EtapeDuDossier::RdvPlanifie;
        $cas['reinscription-'.$this->demande('REINSOK', ['statut' => 'convertie', 'traite_at' => now()])->id] = EtapeDuDossier::Inscrit;

        return $cas;
    }

    public function test_chaque_dossier_recoit_l_etape_deduite_de_ses_statuts(): void
    {
        $attendu = $this->unDossierParCas();

        $lignes = app(FileDesDemandes::class)->page($this->agent, ['etat' => 'toutes'])->getCollection();

        $obtenu = $lignes->mapWithKeys(fn (DemandeDInscription $d) => [$d->cle() => $d->etapeDuDossier()])->all();
        ksort($attendu);
        ksort($obtenu);
        $this->assertEquals($attendu, $obtenu);
    }

    public function test_le_sql_compte_et_filtre_exactement_comme_la_deduction_ligne_a_ligne(): void
    {
        $attendu = $this->unDossierParCas();
        $file = app(FileDesDemandes::class);

        $comptes = $file->compteurs($this->agent)['etapes'];

        foreach (EtapeDuDossier::cases() as $etape) {
            $voulus = array_keys(array_filter($attendu, fn (?EtapeDuDossier $e) => $e === $etape));
            $trouves = $file->page($this->agent, ['etape' => $etape->value])->getCollection()->map->cle()->all();
            sort($voulus);
            sort($trouves);
            $this->assertSame($voulus, $trouves, 'filtre '.$etape->value);
            $this->assertSame(count($voulus), $comptes[$etape->value], 'compte '.$etape->value);
        }
    }

    public function test_l_etape_inscrit_ne_compte_que_l_annee_de_la_campagne(): void
    {
        $ancienne = ESBTPAnneeUniversitaire::factory()->create(['name' => '2024-2025', 'is_current' => false]);
        $this->candidature(['nom' => 'CETTEANNEE', 'statut' => 'convertie', 'traite_at' => now()]);
        $this->candidature(['nom' => 'AUTREFOIS', 'statut' => 'convertie', 'traite_at' => now()->subYear(), 'annee_universitaire_id' => $ancienne->id]);

        $file = app(FileDesDemandes::class);

        $this->assertSame(1, $file->compteurs($this->agent)['etapes']['inscrit']);
        $noms = $file->page($this->agent, ['etape' => 'inscrit'])->getCollection()->map(fn ($d) => $d->nom)->all();
        $this->assertSame(['CETTEANNEE Ama'], $noms);
    }

    public function test_les_etapes_suivent_l_onglet_choisi(): void
    {
        $this->unDossierParCas();
        $file = app(FileDesDemandes::class);

        $reinscriptions = $file->compteurs($this->agent, FileDesDemandes::TYPE_REINSCRIPTION);

        $this->assertSame(1, $reinscriptions['etapes']['rdv_planifie']);
        $this->assertSame(1, $reinscriptions['etapes']['inscrit']);
        $this->assertSame(0, $reinscriptions['etapes']['a_examiner']);
        // Les onglets, eux, comptent toujours tous les types visibles.
        $this->assertSame($file->compteurs($this->agent)['a_traiter'], $reinscriptions['a_traiter']);
    }

    public function test_la_page_filtre_une_etape_et_montre_les_deux_types_avec_leur_etape(): void
    {
        $this->unDossierParCas();

        $this->actingAs($this->agent)->get(route('esbtp.demandes.index'))
            ->assertOk()
            ->assertSee('Dossiers')
            ->assertSee('PLANIFIE')
            ->assertSee('REINSRDV')
            ->assertSee('RDV planifié')
            ->assertSee('Contact à confirmer')
            ->assertSee('Reçu aujourd\'hui');

        $this->actingAs($this->agent)->getJson(route('esbtp.demandes.index', ['etape' => 'rdv_planifie', 'fragment' => 1]))
            ->assertOk()
            ->assertSee('PLANIFIE')->assertSee('REINSRDV')
            ->assertDontSee('EXAMEN')->assertDontSee('RECUE');
    }

    public function test_une_etape_inconnue_revient_aux_dossiers_ouverts(): void
    {
        $this->candidature(['nom' => 'OUVERTE']);
        $this->candidature(['nom' => 'FERMEE', 'statut' => 'rejetee', 'traite_at' => now(), 'motif_rejet' => 'Hors délai']);

        $this->actingAs($this->agent)->getJson(route('esbtp.demandes.index', ['etape' => 'n_importe_quoi', 'fragment' => 1]))
            ->assertOk()->assertSee('OUVERTE')->assertDontSee('FERMEE');
    }

    public function test_la_recherche_trouve_par_nom_telephone_et_reference_dans_une_etape(): void
    {
        $cible = $this->candidature(['nom' => 'KONAN', 'prenoms' => 'Aya', 'telephone' => '+2250701020304']);
        $this->reserver($cible, $this->creneau(1));
        $autre = $this->candidature(['nom' => 'BAMBA']);
        $this->reserver($autre, $this->creneau(1));
        $reference = $cible->assurerReferencePublique();

        foreach (['Aya Konan', '07 01 02 03 04', $reference] as $q) {
            $this->actingAs($this->agent)->getJson(route('esbtp.demandes.index', ['etape' => 'rdv_planifie', 'q' => $q, 'fragment' => 1]))
                ->assertOk()->assertSee('KONAN')->assertDontSee('BAMBA');
        }
    }

    public function test_le_panneau_dit_si_la_convocation_est_delivree_ou_a_rebondi(): void
    {
        $delivree = $this->candidature(['nom' => 'DELIVREE']);
        $this->reserver($delivree, $this->creneau(1))->forceFill([
            'convocation_statut' => StatutConvocationRdv::Envoyee, 'convocation_envoyee_at' => now()->subDay(), 'convocation_delivree_at' => now()->subDay(),
        ])->save();
        $rebond = $this->candidature(['nom' => 'REBOND']);
        $this->reserver($rebond, $this->creneau(1))->forceFill([
            'convocation_statut' => StatutConvocationRdv::Echec, 'convocation_code_distant' => 'bounced',
        ])->save();

        $html = $this->actingAs($this->agent)->getJson(route('esbtp.demandes.dossier', ['nouvelle', $delivree->id]))->assertOk()->json('html');
        $this->assertStringContainsString('Convocation délivrée', $html);
        $this->assertStringContainsString('RDV planifié', $html);

        $html = $this->actingAs($this->agent)->getJson(route('esbtp.demandes.dossier', ['nouvelle', $rebond->id]))->assertOk()->json('html');
        $this->assertStringContainsString('Courriel rebondi', $html);
    }

    public function test_les_compteurs_de_la_page_ne_multiplient_pas_les_requetes_avec_le_volume(): void
    {
        $this->unDossierParCas();
        // Une premiere requete chauffe ce qui ne depend pas de la liste (session,
        // droits, reglages) ; chaque mesure part ensuite des memes caches vides.
        $url = route('esbtp.demandes.index', ['fragment' => 1, 'compteurs' => 1, 'etat' => 'toutes']);
        $this->actingAs($this->agent)->getJson($url)->assertOk();
        $compter = function () use ($url): int {
            Cache::flush();
            \DB::flushQueryLog();
            \DB::enableQueryLog();
            $this->actingAs($this->agent)->getJson($url)->assertOk();

            return count(\DB::getQueryLog());
        };
        $avant = $compter();

        for ($i = 0; $i < 6; $i++) {
            $c = $this->candidature(['nom' => 'VOLUME'.$i]);
            $this->reserver($c, $this->creneau(1));
        }

        $this->assertSame($avant, $compter());
    }
}
