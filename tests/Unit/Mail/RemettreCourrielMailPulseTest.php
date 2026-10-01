<?php

namespace Tests\Unit\Mail;

use App\Jobs\MailPulse\RemettreCourrielMailPulse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le job qui remet un courriel retenu pour débit, éprouvé par le vrai worker de
 * Laravel sur une file `database` (SQLite en mémoire) : réserve, essai,
 * relâche, échec. Aucun mock du worker, c'est lui qu'on veut voir trancher.
 */
class RemettreCourrielMailPulseTest extends TestCase
{
    private const ENDPOINT = 'mailpulse.test/api/v1/messages';

    private const CLE = 'klassci-mail-test';

    private int $echecs = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        config()->set('queue.default', 'database');
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('jobs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('queue')->index();
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });

        config()->set('app.tenant_code', 'presentation');
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');
        config()->set('services.mailpulse.mail_per_minute', 30);
        RateLimiter::clear('mailpulse-courriels:presentation');

        $this->echecs = 0;
        Event::listen(JobFailed::class, function () {
            $this->echecs++;
        });
    }

    /** @test */
    public function il_envoie_la_charge_retenue_avec_sa_cle_quand_le_debit_le_permet(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg-1', 'status' => 'sent'],
        ], 202)]);
        $this->mettreEnFile();

        $this->executerUnJob();

        Http::assertSent(fn (Request $r) => $r->header('Idempotency-Key')[0] === self::CLE
            && $r['recipient']['value'] === 'parent@example.com'
            && $r['metadata']['subject'] === 'Absence');
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, $this->echecs);
    }

    /** @test */
    public function un_nouveau_429_de_debit_le_relache_sans_jamais_bruler_ses_essais(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => 'Message rate limit exceeded'], 429)]);
        $this->mettreEnFile();

        // Quatre tours avec --tries=3 : seul `retryUntil()` borne le job.
        foreach (range(1, 4) as $tour) {
            DB::table('jobs')->update(['available_at' => time() - 1]);
            $this->executerUnJob();
        }

        $ligne = DB::table('jobs')->sole();
        $this->assertSame(4, (int) $ligne->attempts);
        $this->assertGreaterThanOrEqual(time() + RemettreCourrielMailPulse::DELAI_MINIMAL - 1, (int) $ligne->available_at);
        $this->assertSame(0, $this->echecs, 'Un refus de débit ne fait jamais échouer le job.');
    }

    /** @test */
    public function mailpulse_indisponible_le_relache_au_lieu_de_le_faire_echouer(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => 'down'], 503)]);
        $this->mettreEnFile();

        $this->executerUnJob();

        $ligne = DB::table('jobs')->sole();
        $this->assertSame(1, (int) $ligne->attempts);
        $this->assertGreaterThanOrEqual(time() + RemettreCourrielMailPulse::DELAI_MINIMAL - 1, (int) $ligne->available_at);
        $this->assertSame(0, $this->echecs, 'Une panne passagère se rejoue : la clé d\'idempotence empêche le doublon.');
    }

    /** @test */
    public function la_charge_en_file_est_chiffree(): void
    {
        $this->mettreEnFile();

        $payload = DB::table('jobs')->value('payload');
        $this->assertStringNotContainsString('Votre enfant était absent', $payload);
        $this->assertStringNotContainsString('parent@example.com', $payload);
        $this->assertStringNotContainsString(self::CLE, $payload);
    }

    /** @test */
    public function le_plafond_local_le_relache_sans_appeler_mailpulse(): void
    {
        Http::fake();
        config()->set('services.mailpulse.mail_per_minute', 1);
        RateLimiter::hit('mailpulse-courriels:presentation', 60);
        $this->mettreEnFile();

        $this->executerUnJob();

        Http::assertNothingSent();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, $this->echecs);
    }

    /** @test */
    public function un_refus_qui_n_est_pas_de_debit_le_fait_echouer_tout_de_suite(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => 'Monthly email quota exceeded'], 429)]);
        $this->mettreEnFile();

        $this->executerUnJob();

        $this->assertSame(1, $this->echecs);
        $this->assertSame(0, DB::table('jobs')->count(), 'Rejouer un quota épuisé pendant deux heures ne servirait à rien.');
    }

    /** @test */
    public function passe_sa_patience_il_echoue_au_lieu_d_etre_rejoue(): void
    {
        Http::fake();
        $this->mettreEnFile();
        $payload = json_decode(DB::table('jobs')->value('payload'), true);
        $payload['retryUntil'] = time() - 1;
        DB::table('jobs')->update(['payload' => json_encode($payload)]);

        $this->executerUnJob();

        Http::assertNothingSent();
        $this->assertSame(1, $this->echecs);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    private function mettreEnFile(): void
    {
        app('queue')->connection('database')->push(new RemettreCourrielMailPulse([
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => 'parent@example.com'],
            'content' => ['type' => 'text', 'text' => 'Votre enfant était absent.'],
            'metadata' => ['subject' => 'Absence', 'source' => 'klassci'],
        ], self::CLE, ['message_id' => 'test']));
    }

    private function executerUnJob(): void
    {
        app('queue.worker')->runNextJob('database', 'default', new WorkerOptions(
            'default', 0, 128, 60, 0, 3
        ));
    }
}
