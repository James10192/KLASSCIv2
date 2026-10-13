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

    public function test_a_sibling_sharing_the_user_account_never_leaks_into_this_student_email(): void
    {
        Mail::fake();
        $student = $this->student();

        DB::table('esbtp_etudiants')->insert([
            'id' => 32,
            'user_id' => 72,
            'email_personnel' => 'autre-eleve@example.com',
        ]);
        DB::table('esbtp_parents')->insert([
            'id' => 52, 'email' => 'autre-famille@example.com',
        ]);
        DB::table('esbtp_etudiant_parent')->insert([
            'etudiant_id' => 32, 'parent_id' => 52, 'is_tuteur' => true,
        ]);

        $accepted = app(FamilyWorkflowMailer::class)->send(
            $student, 'payment_received', fn (): Mailable => new Mailable()
        );
        $this->assertSame(3, $accepted);
        Mail::assertSentCount(3);
        Mail::assertNotSent(Mailable::class, fn (Mailable $mail): bool =>
            $mail->hasTo('autre-famille@example.com')
            || $mail->hasTo('autre-eleve@example.com')
        );
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
}
