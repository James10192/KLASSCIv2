<?php

namespace Tests\Feature\RendezVous;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\RendezVous\MessagerieRdv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Le deploiement enchaine pull, cache:clear, migrate : quelques secondes
 * durant lesquelles le nouveau code tourne sur l'ancien schema. Les ecrans de
 * rendez-vous et la pose d'une convocation doivent tenir sans les colonnes
 * ajoutees par cette version.
 *
 * Le test retire ces colonnes pour de vrai. Un DDL valide implicitement toute
 * transaction MySQL : celle de RefreshDatabase est donc fermee avant, les
 * donnees du test vivent dans une transaction a part, et le schema est
 * restaure quoi qu'il arrive.
 */
class RendezVousAvantMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATIONS = [
        '2026_09_23_182356_add_verification_contact_to_portail_demandes.php',
        '2026_09_23_182338_add_suivi_distant_to_esbtp_rdv_reservations.php',
    ];

    public function test_les_ecrans_et_la_pose_d_une_convocation_tiennent_sans_les_nouvelles_colonnes(): void
    {
        $migrations = array_map(fn (string $f) => require database_path('migrations/'.$f), self::MIGRATIONS);

        DB::rollBack();
        foreach ($migrations as $migration) {
            $migration->down();
        }

        try {
            DB::beginTransaction();
            Cache::flush();
            $this->scenario();
        } finally {
            Carbon::setTestNow();
            DB::rollBack();
            foreach (array_reverse($migrations) as $migration) {
                $migration->up();
            }
            Cache::flush();
            // RefreshDatabase annule sa transaction en fin de test : on lui en rend une.
            DB::beginTransaction();
        }
    }

    private function scenario(): void
    {
        Carbon::setTestNow('2026-10-05 09:10:00');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.manage', 'inscriptions.rdv.accueil'];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent = User::factory()->create();
        $agent->givePermissionTo($permissions);

        $reservation = $this->reservation();

        app(MessagerieRdv::class)->planifier($reservation, 'confirme');
        $this->assertNotNull($reservation->fresh()->convocation_statut);

        $this->actingAs($agent)->get(route('esbtp.rendez-vous.accueil.index'))->assertOk()->assertSee('KOUASSI');
        $this->actingAs($agent)->get(route('esbtp.rendez-vous.index'))->assertOk()->assertSee('KOUASSI');
    }

    private function reservation(): ESBTPRdvReservation
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create()->id;
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee, 'date' => Carbon::today()->toDateString(),
            'heure_debut' => '10:00:00', 'heure_fin' => '10:30:00', 'capacite' => 4, 'ouvert' => true,
        ]);
        $candidature = DB::table('esbtp_candidatures')->insertGetId([
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'date_naissance' => '2007-03-12',
            'telephone' => '+2250701020304', 'email' => 'famille@gmail.com',
            'annee_universitaire_id' => $annee, 'consentement_at' => now(), 'statut' => 'en_attente',
            'tuteur_nom' => 'Kouassi Paul', 'tuteur_telephone' => '+2250505050505', 'tuteur_lien' => 'Père',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature, 'statut' => 'confirmee',
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'telephone' => '+2250701020304',
            'date_naissance' => '2007-03-12', 'email' => 'famille@gmail.com',
            'convocation_statut' => 'envoyee', 'convocation_action' => 'confirme',
        ]);
    }
}
