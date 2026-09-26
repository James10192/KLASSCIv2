<?php

namespace Tests\Feature\AcademicPilotage;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\Unit\Domain\AcademicPilotage\AcademicPilotageDatabaseTestCase;

class AcademicAssignmentEndpointsTest extends AcademicPilotageDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createRelations();
        // Pas withoutMiddleware() nu : il coupe aussi SubstituteBindings, et la route
        // de desactivation recevait alors une affectation vide (404 sur findOrFail(null)).
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
            \App\Http\Middleware\ExigerDoubleAuthentification::class,
            \App\Http\Middleware\ForcePasswordChange::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);
        $this->actingAs($this->actor(50));
        Gate::before(fn (): bool => true);
    }

    public function test_an_assignment_can_be_created_listed_and_deactivated_without_reload(): void
    {
        $created = $this->postJson(route('esbtp.academic-assignments.store'), [
            'user_id' => 51,
            'classe_id' => 10,
            'annee_universitaire_id' => 20,
            'responsibility' => 'grade_entry',
        ]);

        $created->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('assignment.responsibility', 'grade_entry');
        $assignmentId = $created->json('assignment.id');

        $this->getJson(route('esbtp.academic-assignments.index', [
            'year_id' => 20,
            'class_id' => 10,
        ]))->assertOk()
            ->assertJsonPath('assignments.0.id', $assignmentId)
            ->assertJsonPath('assignments.0.user.name', 'Awa Educatrice')
            ->assertJsonPath('assignments.0.classe.name', 'BTS1 · BTS 1 A')
            ->assertJsonPath('assignments.0.annee_universitaire.name', '2026-2027');

        $this->deleteJson(route('esbtp.academic-assignments.deactivate', $assignmentId))
            ->assertOk()
            ->assertJsonPath('assignment.is_active', false);
    }

    private function createRelations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->softDeletes();
        });
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->softDeletes();
        });
        // ESBTPClasse charge toujours sa filiere et son niveau ($with).
        foreach (['esbtp_filieres', 'esbtp_niveau_etudes'] as $table) {
            Schema::create($table, function (Blueprint $t): void {
                $t->id();
                $t->softDeletes();
            });
        }
        Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('annee_debut')->nullable();
            $table->string('annee_fin')->nullable();
            $table->softDeletes();
        });

        DB::table('users')->insert([
            ['id' => 50, 'name' => 'Responsable', 'email' => 'responsable@example.test'],
            ['id' => 51, 'name' => 'Awa Educatrice', 'email' => 'awa@example.test'],
        ]);
        DB::table('esbtp_classes')->insert(['id' => 10, 'name' => 'BTS 1 A', 'code' => 'BTS1']);
        DB::table('esbtp_annee_universitaires')->insert([
            'id' => 20,
            'name' => '-',
            'annee_debut' => '2026',
            'annee_fin' => '2027',
        ]);
    }
}
