<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Admissions\StudentAccessInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Reproduit l'incident de production sur une base SQLite isolée :
 * l'enregistrement d'un étudiant ne dépend pas de l'envoi d'un lien.
 */
class PasswordResetStorageRepairTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_la_migration_repare_la_table_et_le_broker_peut_generer_un_lien(): void
    {
        $this->assertFalse(Schema::hasTable('password_resets'));

        $this->migration()->up();

        $this->assertTrue(Schema::hasTable('password_resets'));

        $user = new User();
        $user->email = 'etudiant@example.test';

        $token = Password::broker()->createToken($user);

        $this->assertNotEmpty($token);
        $this->assertSame(1, DB::table('password_resets')->where('email', $user->email)->count());
        $this->assertTrue(Password::broker()->tokenExists($user, $token));
    }

    public function test_la_migration_est_idempotente_et_le_rollback_preserve_les_jetons_existants(): void
    {
        $migration = $this->migration();
        $migration->up();

        DB::table('password_resets')->insert([
            'email' => 'historique@example.test',
            'token' => 'jeton-existant',
            'created_at' => now(),
        ]);

        $migration->up();
        $migration->down();

        $this->assertTrue(Schema::hasTable('password_resets'));
        $this->assertSame('jeton-existant', DB::table('password_resets')
            ->where('email', 'historique@example.test')->value('token'));
    }

    public function test_un_stockage_absent_ne_fait_pas_echouer_l_invitation(): void
    {
        $this->assertFalse(Schema::hasTable('password_resets'));

        $user = new User();
        $user->id = 1726;
        $user->email = 'etudiant@example.test';

        $this->assertFalse(app(StudentAccessInvitation::class)->inviter($user, 'activation'));
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_16_000000_restore_password_resets_table.php');
    }
}
