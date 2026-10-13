<?php

namespace Tests\Unit\Services\Notifications;

use App\Models\ESBTPEtudiant;
use App\Models\User;
use App\Services\Notifications\FamilyWorkflowMailer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FamilyWorkflowMailerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('email')->nullable();
            $table->string('email_personnel')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_parents', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_etudiant_parent', function (Blueprint $table): void {
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('parent_id');
            $table->string('relation')->nullable();
            $table->boolean('is_tuteur')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function student(): ESBTPEtudiant
    {
        DB::table('esbtp_etudiants')->insert([
            'id' => 31,
            'user_id' => 72,
            'email_personnel' => 'eleve@example.com',
            'email' => 'eleve@example.com',
        ]);
        DB::table('esbtp_parents')->insert([
            ['id' => 50, 'email' => 'parent@example.com'],
            ['id' => 51, 'email' => 'autre-parent@example.com'],
        ]);
        DB::table('esbtp_etudiant_parent')->insert([
            ['etudiant_id' => 31, 'parent_id' => 50, 'is_tuteur' => true],
            ['etudiant_id' => 31, 'parent_id' => 51, 'is_tuteur' => false],
        ]);

        $student = ESBTPEtudiant::findOrFail(31);
        $user = new User();
        $user->id = 72;
        $user->email = 'parent@example.com'; // same account, same address as the tutor
        $student->setRelation('user', $user);

        return $student;
    }

    public function test_one_mailable_per_distinct_student_and_parent_email(): void
    {
        Mail::fake();
        $student = $this->student();
        $created = 0;

        $accepted = app(FamilyWorkflowMailer::class)->send(
            $student,
            'payment_received',
            function () use (&$created): Mailable {
                $created++;
                return new Mailable();
            }
        );

        $this->assertSame(3, $accepted);
        $this->assertSame(3, $created);
        Mail::assertSentCount(3);
        foreach (['parent@example.com', 'eleve@example.com', 'autre-parent@example.com'] as $address) {
            Mail::assertSent(Mailable::class, fn (Mailable $message): bool => $message->hasTo($address));
        }
    }
    public function test_a_parent_of_another_student_sharing_the_login_does_not_receive_this_students_private_mail(): void
    {
        Mail::fake();
        $student = $this->student();

        // Same user_id for the login, but a *different student-family link*.
        // Prior implementation reused StudentAccessInvitation::destinataires
        // and would leak payment/academic data to this unrelated parent.
        DB::table('esbtp_etudiants')->insert([
            'id' => 32,
            'user_id' => 72,
            'email_personnel' => 'autre-eleve@example.com',
            'email' => 'autre-eleve@example.com',
        ]);
        DB::table('esbtp_parents')->insert([
            'id' => 52, 'email' => 'parent-non-lie@example.com',
        ]);
        DB::table('esbtp_etudiant_parent')->insert([
            'etudiant_id' => 32, 'parent_id' => 52, 'is_tuteur' => true,
        ]);

        $accepted = app(FamilyWorkflowMailer::class)->send(
            $student, 'low_grades', fn (): Mailable => new Mailable()
        );

        $this->assertSame(3, $accepted);
        Mail::assertSentCount(3);
        Mail::assertNotSent(Mailable::class, fn (Mailable $message): bool =>
            $message->hasTo('parent-non-lie@example.com')
            || $message->hasTo('autre-eleve@example.com')
        );
    }

}
