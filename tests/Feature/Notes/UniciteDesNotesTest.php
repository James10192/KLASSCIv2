<?php

namespace Tests\Feature\Notes;

use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\Notes\UniciteDesNotes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Une seule note vivante par élève et par évaluation. Exige MySQL : la
 * contrainte repose sur une colonne générée et un index unique.
 *
 * Pas de RefreshDatabase ni de DatabaseMigrations : poser ou retirer l'index
 * valide implicitement la transaction de test, et le rollback de
 * DatabaseMigrations bute sur une migration plus ancienne (type enum). La base
 * de test est donc mise à jour, puis la table vidée après chaque test.
 */
class UniciteDesNotesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Unicité des notes : MySQL uniquement.');
        }
        $this->artisan('migrate', ['--force' => true]);
        Schema::disableForeignKeyConstraints();
        DB::table('esbtp_notes')->delete();
    }

    protected function tearDown(): void
    {
        DB::table('esbtp_notes')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('users')->delete();
        app(UniciteDesNotes::class)->poser();
        Schema::enableForeignKeyConstraints();
        parent::tearDown();
    }

    private function note(array $valeurs = []): int
    {
        return DB::table('esbtp_notes')->insertGetId(array_merge([
            'etudiant_id' => 1, 'evaluation_id' => 58, 'matiere_id' => 1, 'classe_id' => 1, 'note' => 13,
            'created_at' => now(), 'updated_at' => now(),
        ], $valeurs));
    }

    public function test_deux_notes_vivantes_pour_la_meme_paire_sont_refusees(): void
    {
        $this->note();

        try {
            $this->note(['note' => 14]);
            $this->fail('La seconde note vivante aurait dû être refusée.');
        } catch (QueryException $e) {
            $this->assertSame(1062, $e->errorInfo[1]);
        }
    }

    public function test_une_note_effacee_ou_archivee_n_empeche_pas_de_ressaisir(): void
    {
        $this->note(['deleted_at' => now()]);
        $this->note(['archived_at' => now()]);
        $this->note();

        $this->assertSame(3, DB::table('esbtp_notes')->count());
    }

    public function test_desarchiver_laisse_archivee_une_note_dont_la_jumelle_est_vivante(): void
    {
        $archiveeAvecJumelle = $this->note(['archived_at' => now()]);
        $this->note();
        $archiveeSeule = $this->note(['evaluation_id' => 59, 'archived_at' => now()]);

        $restaurees = ESBTPNote::withoutGlobalScope('not_archived')
            ->where('etudiant_id', 1)
            ->whereNotNull('archived_at')
            ->sansJumelleVivante()
            ->update(['archived_at' => null]);

        $this->assertSame(1, $restaurees);
        $this->assertNotNull(DB::table('esbtp_notes')->where('id', $archiveeAvecJumelle)->value('archived_at'));
        $this->assertNull(DB::table('esbtp_notes')->where('id', $archiveeSeule)->value('archived_at'));
    }

    public function test_desarchiver_deux_jumelles_archivees_n_en_rend_qu_une(): void
    {
        // Deux jumelles archivées sans note vivante : cela arrive quand la
        // jumelle restée vivante est archivée à son tour. Les rendre vivantes
        // toutes deux heurterait l'unicité (1062) et bloquerait le retour de
        // l'élève dans sa classe.
        $ancienne = $this->note(['archived_at' => now()]);
        $recente = $this->note(['note' => 14, 'archived_at' => now()]);

        $restaurees = ESBTPNote::withoutGlobalScope('not_archived')
            ->where('etudiant_id', 1)
            ->whereNotNull('archived_at')
            ->sansJumelleVivante()
            ->update(['archived_at' => null]);

        $this->assertSame(1, $restaurees);
        $this->assertNull(DB::table('esbtp_notes')->where('id', $recente)->value('archived_at'));
        $this->assertNotNull(DB::table('esbtp_notes')->where('id', $ancienne)->value('archived_at'));
    }

    public function test_des_jumelles_archivees_ne_bloquent_pas_l_unicite(): void
    {
        $this->note(['archived_at' => now()]);
        $this->note(['note' => 14, 'archived_at' => now()]);

        $this->assertTrue(app(UniciteDesNotes::class)->doublons()->isEmpty());
    }

    public function test_la_cli_liste_les_doublons_puis_pose_l_unicite_une_fois_tranches(): void
    {
        $this->retirerLIndex();
        $premier = $this->note();
        $second = $this->note(['note' => 14]);
        // Le garde d'installation renvoie tout vers /install sans superAdmin.
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');

        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->getJson(route('api.cli.diagnostics.notes-doublons'))
            ->assertOk()
            ->assertJsonPath('data.index_pose', false)
            ->assertJsonPath('data.doublons.0.note_ids', [$premier, $second]);
        $this->postJson(route('api.cli.notes.unicite'))->assertForbidden();

        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
        $this->postJson(route('api.cli.notes.unicite'))->assertStatus(409);
        $this->assertSame(2, DB::table('esbtp_notes')->whereNull('deleted_at')->count());

        DB::table('esbtp_notes')->where('id', $second)->update(['deleted_at' => now()]);
        $this->postJson(route('api.cli.notes.unicite'))
            ->assertOk()
            ->assertJsonPath('data.index_pose', true);
    }

    /**
     * État réel après migration : MySQL a retiré l'index qu'il avait créé pour
     * la clé étrangère d'etudiant_id, que le nôtre sert désormais. Clés
     * actives, le retirer sans le remplacer échouerait (1553).
     */
    private function retirerLIndex(): void
    {
        if (collect(DB::select("SHOW INDEX FROM esbtp_notes WHERE Key_name = 'esbtp_notes_etudiant_id_foreign'"))->isNotEmpty()) {
            DB::statement('ALTER TABLE esbtp_notes DROP INDEX esbtp_notes_etudiant_id_foreign');
        }
        Schema::enableForeignKeyConstraints();
        app(UniciteDesNotes::class)->retirer();
        Schema::disableForeignKeyConstraints();
    }

    public function test_des_doublons_existants_suspendent_l_index_sans_rien_effacer(): void
    {
        $unicite = app(UniciteDesNotes::class);
        $this->retirerLIndex();
        $premier = $this->note();
        $second = $this->note(['note' => 14]);

        $this->assertFalse($unicite->poser());
        $this->assertFalse($unicite->indexPose());
        $this->assertSame(2, DB::table('esbtp_notes')->count());
        $this->assertSame("{$premier},{$second}", $unicite->doublons()->first()->note_ids);
        $this->artisan('notes:unicite')->assertFailed();

        DB::table('esbtp_notes')->where('id', $second)->update(['deleted_at' => now()]);

        $this->artisan('notes:unicite')->assertSuccessful();
        $this->assertTrue($unicite->indexPose());
    }
}
