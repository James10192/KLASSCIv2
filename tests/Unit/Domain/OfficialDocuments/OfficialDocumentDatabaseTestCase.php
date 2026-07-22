<?php

namespace Tests\Unit\Domain\OfficialDocuments;

use App\Models\ESBTPLMDJury;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class OfficialDocumentDatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        Storage::fake('local');
        $this->createDependencies();
        $this->runRealMigrations();
        $this->seedAcademicYears();
        $this->seedSystemSettings();
    }

    protected function seedIssuableJury(): ESBTPLMDJury
    {
        $now = now();
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'PrÃƒÂ©sidente Test', 'email' => 'presidente@test.ci', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Assesseur Test', 'email' => 'assesseur@test.ci', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_annee_universitaires')->insertOrIgnore(['id' => 1, 'libelle' => '2025-2026', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_lmd_parcours')->insert(['id' => 1, 'name' => 'GÃƒÂ©nie civil', 'code' => 'GC', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_classes')->insert(['id' => 1, 'name' => 'Licence 1 GC', 'parcours_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_lmd_sessions')->insert(['id' => 1, 'libelle' => 'Session normale', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_etudiants')->insert([
            ['id' => 10, 'matricule' => 'LMD001', 'nom' => 'KOFFI', 'prenoms' => 'Aya', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'matricule' => 'LMD002', 'nom' => 'YAO', 'prenoms' => 'Jean', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_lmd_jurys')->insert(['id' => 1, 'annee_universitaire_id' => 1, 'session_id' => 1, 'parcours_id' => 1, 'classe_id' => 1, 'semestre' => 1, 'libelle' => 'Jury S1', 'date_jury' => '2026-07-22', 'status' => 'en_cours', 'observations' => 'DÃƒÂ©libÃƒÂ©ration rÃƒÂ©guliÃƒÂ¨re', 'created_at' => $now, 'updated_at' => $now]);
        $signature = $this->signatureData();
        DB::table('esbtp_lmd_jury_membres')->insert([
            ['id' => 1, 'jury_id' => 1, 'user_id' => 1, 'role' => 'president', 'present' => true, 'signature_data' => $signature, 'signature_at' => $now, 'signature_ip' => '127.0.0.1', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'jury_id' => 1, 'user_id' => 2, 'role' => 'assesseur', 'present' => true, 'signature_data' => $signature, 'signature_at' => $now, 'signature_ip' => '127.0.0.2', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_lmd_bulletins')->insert([
            ['id' => 100, 'etudiant_id' => 10, 'classe_id' => 1, 'parcours_id' => 1, 'annee_universitaire_id' => 1, 'semestre' => 1, 'is_published' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 101, 'etudiant_id' => 11, 'classe_id' => 1, 'parcours_id' => 1, 'annee_universitaire_id' => 1, 'semestre' => 1, 'is_published' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_lmd_jury_decisions')->insert([
            ['id' => 1, 'jury_id' => 1, 'etudiant_id' => 10, 'bulletin_id' => 100, 'decision_auto' => 'admis', 'decision' => 'admis', 'mention' => 'bien', 'moyenne_generale' => 14, 'credits_obtenus' => 30, 'credits_attendus' => 30, 'override_par_jury' => false, 'motif_override' => null, 'vote_resultat' => 'unanime', 'locked' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'jury_id' => 1, 'etudiant_id' => 11, 'bulletin_id' => 101, 'decision_auto' => 'ajourne', 'decision' => 'admis_sous_condition', 'mention' => null, 'moyenne_generale' => 9, 'credits_obtenus' => 24, 'credits_attendus' => 30, 'override_par_jury' => true, 'motif_override' => 'DÃƒÂ©cision motivÃƒÂ©e', 'vote_resultat' => 'majorite', 'locked' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_grade_sheets')->insert(['id' => 1, 'code' => 'GS-LMD-S1', 'classe_id' => 1, 'annee_universitaire_id' => 1, 'academic_system' => 'LMD', 'semester' => '1', 'status' => 'validated', 'lock_version' => 3, 'created_at' => $now, 'updated_at' => $now]);
        $this->actingAs(User::query()->findOrFail(1));
        return ESBTPLMDJury::query()->findOrFail(1);
    }

    protected function signatureData(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    private function runRealMigrations(): void
    {
        foreach ([
            '2026_07_22_011929_create_esbtp_official_documents_table.php',
            '2026_07_22_011957_create_esbtp_official_document_events_table.php',
            '2026_07_22_015819_create_esbtp_pv_sequences_table.php'
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    private function createDependencies(): void
    {
        $this->createIdentityTables();
        $this->createJuryTables();
        $this->createAcademicTables();
        $this->createAuditTables();
    }

    private function createIdentityTables(): void
    {
        Schema::create('users', fn (Blueprint $t) => $this->userColumns($t));
        Schema::create('settings', function (Blueprint $t): void {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->string('type')->default('string');
            $t->string('group')->default('general');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('esbtp_annee_universitaires', function (Blueprint $t): void { $t->id(); $t->string('libelle'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_lmd_parcours', function (Blueprint $t): void { $t->id(); $t->string('name'); $t->string('code'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_classes', function (Blueprint $t): void { $t->id(); $t->string('name'); $t->unsignedBigInteger('parcours_id')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_lmd_sessions', function (Blueprint $t): void { $t->id(); $t->string('libelle'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_etudiants', function (Blueprint $t): void { $t->id(); $t->string('matricule'); $t->string('nom'); $t->string('prenoms'); $t->timestamps(); $t->softDeletes(); });
    }

    private function userColumns(Blueprint $t): void
    {
        $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('password')->nullable(); $t->timestamps(); $t->softDeletes();
    }

    private function createJuryTables(): void
    {
        Schema::create('esbtp_lmd_jurys', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('annee_universitaire_id'); $t->unsignedBigInteger('session_id')->nullable(); $t->unsignedBigInteger('parcours_id')->nullable(); $t->unsignedBigInteger('classe_id')->nullable(); $t->unsignedTinyInteger('semestre')->nullable(); $t->string('libelle'); $t->date('date_jury')->nullable(); $t->string('pv_numero')->nullable()->unique(); $t->string('pv_path')->nullable(); $t->dateTime('pv_genere_at')->nullable(); $t->unsignedBigInteger('pv_genere_par')->nullable(); $t->string('status')->default('preparation'); $t->dateTime('clos_at')->nullable(); $t->dateTime('publie_at')->nullable(); $t->unsignedBigInteger('publie_par')->nullable(); $t->text('observations')->nullable(); $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_lmd_jury_membres', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('jury_id'); $t->unsignedBigInteger('user_id'); $t->string('role'); $t->boolean('present'); $t->longText('signature_data')->nullable(); $t->dateTime('signature_at')->nullable(); $t->string('signature_ip')->nullable(); $t->text('signature_user_agent')->nullable(); $t->text('notes')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_lmd_jury_decisions', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('jury_id'); $t->unsignedBigInteger('etudiant_id'); $t->unsignedBigInteger('bulletin_id')->nullable(); $t->string('decision_auto')->nullable(); $t->string('decision')->nullable(); $t->string('mention')->nullable(); $t->boolean('override_par_jury')->default(false); $t->text('motif_override')->nullable(); $t->string('vote_resultat')->nullable(); $t->decimal('moyenne_generale', 5, 2)->nullable(); $t->unsignedInteger('credits_obtenus')->default(0); $t->unsignedInteger('credits_attendus')->default(0); $t->boolean('locked')->default(false); $t->dateTime('locked_at')->nullable(); $t->timestamps(); $t->softDeletes(); });
    }

    private function createAcademicTables(): void
    {
        Schema::create('esbtp_lmd_bulletins', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('etudiant_id'); $t->unsignedBigInteger('classe_id')->nullable(); $t->unsignedBigInteger('parcours_id')->nullable(); $t->unsignedBigInteger('annee_universitaire_id'); $t->unsignedTinyInteger('semestre')->nullable(); $t->string('decision_deliberation')->nullable(); $t->boolean('is_published')->default(false); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_grade_sheets', function (Blueprint $t): void { $t->id(); $t->string('code'); $t->unsignedBigInteger('classe_id'); $t->unsignedBigInteger('annee_universitaire_id'); $t->string('academic_system'); $t->string('semester')->nullable(); $t->string('status'); $t->unsignedInteger('lock_version')->default(1); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_system_settings', function (Blueprint $t): void { $t->id(); $t->string('key')->unique(); $t->text('value')->nullable(); $t->string('type')->default('string'); $t->text('description')->nullable(); $t->timestamps(); });
    }

    private function createAuditTables(): void
    {
        Schema::create('audits', function (Blueprint $t): void { $t->id(); $t->string('user_type')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('event'); $t->string('auditable_type'); $t->unsignedBigInteger('auditable_id'); $t->text('old_values')->nullable(); $t->text('new_values')->nullable(); $t->text('url')->nullable(); $t->ipAddress('ip_address')->nullable(); $t->string('user_agent', 1023)->nullable(); $t->string('tags')->nullable(); $t->timestamps(); });
    }

    private function seedAcademicYears(): void
    {
        $now = now();
        DB::table('esbtp_annee_universitaires')->insertOrIgnore([
            ['id' => 1, 'libelle' => '2025-2026', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'libelle' => '2026-2027', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedSystemSettings(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['key' => 'lmd_jury_quorum_min', 'value' => '2', 'type' => 'integer', 'group' => 'lmd', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'lmd_jury_quorum_assesseurs_min', 'value' => '1', 'type' => 'integer', 'group' => 'lmd', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'lmd_jury_signature_required', 'value' => '1', 'type' => 'boolean', 'group' => 'lmd', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('esbtp_system_settings')->insertOrIgnore([
            ['key' => 'paywall_active', 'value' => '0', 'type' => 'boolean', 'description' => 'Verrouille le paywall applicatif.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'matricule_mode', 'value' => 'automatique', 'type' => 'string', 'description' => 'Mode de gÃ©nÃ©ration des matricules: manuel ou automatique', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'current_etablissement_id', 'value' => '1', 'type' => 'integer', 'description' => 'Ã‰tablissement actuellement configurÃ©', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
