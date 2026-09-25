<?php

namespace Tests\Feature\RendezVous;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\RendezVous\FamillesAPrevenirRdv;
use App\Services\RendezVous\FeuilleRendezVous;
use App\Services\TenantScolariteSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * La feuille de suivi imprimable : la semaine ou un jour, seulement les
 * familles qui occupent un creneau, dans l'ordre du guichet.
 */
class FeuilleSuiviRendezVousTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private int $annee;

    private int $numero = 0;

    /** Lundi 5 octobre 2026 */
    private const MAINTENANT = '2026-10-05 07:00:00';

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

        foreach (['admin.access', 'inscriptions.rdv.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['admin.access', 'inscriptions.rdv.view']);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_semaine_liste_les_familles_attendues_dans_l_ordre_du_guichet(): void
    {
        $mardi = $this->creneau('10:00', '10:30', 1);
        $lundi = $this->creneau('08:00', '08:30', 0);
        $this->reservation($mardi, ['nom' => 'ZADI']);
        $this->reservation($lundi, ['nom' => 'YAO', 'statut' => 'honoree']);
        $this->reservation($lundi, ['nom' => 'AKA']);
        $this->reservation($lundi, ['nom' => 'ANNULE', 'statut' => 'annulee']);
        // Semaine suivante : hors de la feuille.
        $this->reservation($this->creneau('08:00', '08:30', 7), ['nom' => 'PLUSTARD']);

        $feuille = app(FeuilleRendezVous::class);
        $p = $feuille->periode(null, null);
        $lignes = $feuille->lignes($p['debut'], $p['fin']);

        $this->assertSame(['AKA', 'YAO', 'ZADI'], array_column($lignes, 'nom'));
        $this->assertSame(['', 'Reçue', ''], array_column($lignes, 'statut'));
        $this->assertSame('Inscription', $lignes[0]['dossier']);
        $this->assertSame('08:00 – 08:30', $lignes[0]['heure']);
    }

    public function test_un_jour_seul_ne_rend_que_ce_jour(): void
    {
        $this->reservation($this->creneau('08:00', '08:30', 0), ['nom' => 'LUNDI']);
        $this->reservation($this->creneau('08:00', '08:30', 1), ['nom' => 'MARDI']);

        $feuille = app(FeuilleRendezVous::class);
        $p = $feuille->periode(null, Carbon::today()->addDay()->toDateString());

        $this->assertTrue($p['jourSeul']);
        $this->assertSame(['MARDI'], array_column($feuille->lignes($p['debut'], $p['fin']), 'nom'));
    }

    public function test_la_feuille_s_imprime_et_se_telecharge(): void
    {
        $this->reservation($this->creneau('08:00', '08:30', 0));

        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.feuille.apercu', ['debut' => Carbon::today()->toDateString()]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.feuille.apercu', ['jour' => Carbon::today()->toDateString()]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.feuille.excel'))->assertOk();
    }

    public function test_sans_permission_la_feuille_est_fermee(): void
    {
        $lecteur = User::factory()->create();

        $this->actingAs($lecteur)->get(route('esbtp.rendez-vous.feuille.apercu'))->assertForbidden();
    }

    public function test_un_contact_non_confirme_n_est_pas_decrit_comme_sans_adresse(): void
    {
        DB::table('settings')->updateOrInsert(['key' => TenantScolariteSettings::VERIFICATION_CONTACT], ['value' => '1']);
        Cache::flush();
        $r = $this->reservation($this->creneau('10:00', '10:30', 1), ['convocation_statut' => 'sans_email']);
        DB::table('esbtp_candidatures')->where('id', $r->candidature_id)->update(['verification_contact' => 'email_non_verifie']);

        $lignes = app(FamillesAPrevenirRdv::class)->lignes();

        $this->assertCount(1, $lignes);
        $this->assertStringStartsWith('Contact non confirmé', $lignes[0]['motif']);
    }

    private function creneau(string $debut, string $fin, int $dansJours): ESBTPRdvCreneau
    {
        return ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee,
            'date' => Carbon::today()->addDays($dansJours)->toDateString(),
            'heure_debut' => $debut.':00', 'heure_fin' => $fin.':00',
            'capacite' => 4, 'ouvert' => true,
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
