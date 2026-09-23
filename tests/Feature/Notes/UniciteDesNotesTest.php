<?php

namespace Tests\Feature\Notes;

use App\Models\ESBTPNote;
use App\Services\Notes\UniciteDesNotes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_des_doublons_existants_suspendent_l_index_sans_rien_effacer(): void
    {
        $unicite = app(UniciteDesNotes::class);
        // État réel après migration : MySQL a retiré l'index qu'il avait créé
        // pour la clé étrangère d'etudiant_id, que le nôtre sert désormais.
        // Clés actives, le retirer sans le remplacer échouerait (1553).
        if (collect(DB::select("SHOW INDEX FROM esbtp_notes WHERE Key_name = 'esbtp_notes_etudiant_id_foreign'"))->isNotEmpty()) {
            DB::statement('ALTER TABLE esbtp_notes DROP INDEX esbtp_notes_etudiant_id_foreign');
        }
        Schema::enableForeignKeyConstraints();
        $unicite->retirer();
        Schema::disableForeignKeyConstraints();
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
