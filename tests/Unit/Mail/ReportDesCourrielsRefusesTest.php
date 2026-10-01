<?php

namespace Tests\Unit\Mail;

use App\Jobs\EnvoyerRelanceJob;
use App\Mail\Transport\DebitMailPulseAtteint;
use App\Mail\Transport\ReportDesCourrielsRefuses;
use App\Models\ESBTPRelance;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Le report des courriels refusés pour débit, éprouvé par le vrai worker de
 * Laravel sur une file `database` (SQLite en mémoire) : réserve, essai, échec,
 * remise en file. Aucun mock du worker, c'est lui qu'on veut voir trancher.
 */
class ReportDesCourrielsRefusesTest extends TestCase
{
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

        JobDeTestCourriel::$vuDansUneFile = null;
    }

    protected function tearDown(): void
    {
        ReportDesCourrielsRefuses::sortir('test');
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function un_refus_de_debit_remet_une_copie_neuve_en_file_sans_bruler_d_essai(): void
    {
        app('queue')->connection('database')->push(new JobDeTestCourriel('debit'));

        $this->executerUnJob();

        $ligne = DB::table('jobs')->sole();
        $this->assertSame(0, (int) $ligne->attempts, 'La copie repart à zéro essai.');
        $this->assertSame(1, json_decode($ligne->payload, true)[ReportDesCourrielsRefuses::CLE_CHARGE]);
        $this->assertGreaterThanOrEqual(time() + 40, (int) $ligne->available_at, 'Le délai du refus est tenu.');
        $this->assertTrue(JobDeTestCourriel::$vuDansUneFile, 'Dans un job de file, le refus doit pouvoir remonter.');
    }

    /** @test */
    public function trois_refus_de_suite_ne_mettent_pas_le_job_en_echec(): void
    {
        app('queue')->connection('database')->push(new JobDeTestCourriel('debit'));

        foreach (range(1, 3) as $tour) {
            DB::table('jobs')->update(['available_at' => time() - 1]);
            $this->executerUnJob();
        }

        $ligne = DB::table('jobs')->sole();
        $this->assertSame(3, json_decode($ligne->payload, true)[ReportDesCourrielsRefuses::CLE_CHARGE]);
    }

    /** @test */
    public function a_la_derniere_tentative_le_job_echoue_au_lieu_d_etre_reporte(): void
    {
        // Deux essais déjà brûlés par d'autres pannes : le troisième, refusé
        // pour débit, est le dernier. Le worker le met en échec avant
        // l'écouteur, qui ne doit pas le ressusciter.
        app('queue')->connection('database')->push(new JobDeTestCourriel('debit'));
        DB::table('jobs')->update(['attempts' => 2]);
        $echecs = 0;
        Event::listen(JobFailed::class, function () use (&$echecs) {
            $echecs++;
        });

        $this->executerUnJob();

        // `failed_jobs` est écrit par la commande queue:work, pas par le
        // worker : ici, c'est l'évènement JobFailed qui fait foi.
        $this->assertSame(1, $echecs, 'Le worker a mis le job en échec.');
        $this->assertSame(0, DB::table('jobs')->count(), 'Un job en échec ne repart pas en file.');
    }

    /** @test */
    public function un_job_qui_s_est_deja_relache_n_est_pas_reporte_une_seconde_fois(): void
    {
        app('queue')->connection('database')->push(new JobDeTestCourriel('relache-puis-debit'));

        $this->executerUnJob();

        $ligne = DB::table('jobs')->sole();
        $this->assertSame(1, (int) $ligne->attempts, 'Le release du job lui-même tient, il n\'y a pas de copie.');
        $this->assertArrayNotHasKey(ReportDesCourrielsRefuses::CLE_CHARGE, json_decode($ligne->payload, true));
    }

    /** @test */
    public function au_plafond_de_reports_le_report_compte_un_essai(): void
    {
        app('queue')->connection('database')->push(new JobDeTestCourriel('debit'));
        $payload = json_decode(DB::table('jobs')->value('payload'), true);
        $payload[ReportDesCourrielsRefuses::CLE_CHARGE] = ReportDesCourrielsRefuses::PLAFOND_REPORTS;
        DB::table('jobs')->update(['payload' => json_encode($payload)]);

        $this->executerUnJob();

        $ligne = DB::table('jobs')->sole();
        $this->assertSame(1, (int) $ligne->attempts, 'Au plafond, release() : l\'essai compte.');
        $this->assertSame(ReportDesCourrielsRefuses::PLAFOND_REPORTS, json_decode($ligne->payload, true)[ReportDesCourrielsRefuses::CLE_CHARGE]);
    }

    /** @test */
    public function une_autre_panne_suit_le_chemin_ordinaire_du_worker(): void
    {
        app('queue')->connection('database')->push(new JobDeTestCourriel('panne'));

        $this->executerUnJob();

        $ligne = DB::table('jobs')->sole();
        $this->assertSame(1, (int) $ligne->attempts);
        $this->assertArrayNotHasKey(ReportDesCourrielsRefuses::CLE_CHARGE, json_decode($ligne->payload, true));
    }

    /** @test */
    public function un_job_sync_n_est_pas_une_file(): void
    {
        try {
            app('queue')->connection('sync')->push(new JobDeTestCourriel('panne'));
        } catch (RuntimeException $e) {
            // La panne remonte à l'appelant : c'est le comportement de sync.
        }

        $this->assertFalse(JobDeTestCourriel::$vuDansUneFile, 'Un job sync tourne dans la requête : le refus ne doit pas y remonter.');
        $this->assertFalse(ReportDesCourrielsRefuses::dansUneFile());
    }

    /** @test */
    public function la_relance_par_courriel_ne_passe_pas_en_echec_sur_un_refus_de_debit_dans_une_file(): void
    {
        $relance = $this->relanceQuiRefuse();
        ReportDesCourrielsRefuses::entrer('test');

        $this->expectException(DebitMailPulseAtteint::class);
        try {
            app(NotificationService::class)->envoyerRelanceEmail($relance);
        } finally {
            $this->assertSame([], $relance->misesAJour, 'Aucun statut écrit : la relance repartira.');
        }
    }

    /** @test */
    public function hors_file_la_relance_par_courriel_garde_son_echec_sans_lever(): void
    {
        $relance = $this->relanceQuiRefuse();

        $resultat = app(NotificationService::class)->envoyerRelanceEmail($relance);

        $this->assertFalse($resultat['success']);
        $this->assertSame('echec', $relance->misesAJour[0]['statut']);
    }

    /** @test */
    public function l_avis_de_reinscription_laisse_remonter_le_refus_dans_une_file_seulement(): void
    {
        $inscription = new class {
            public function load(): void
            {
                throw new DebitMailPulseAtteint('plafond', 30);
            }
        };

        // Depuis l'écran : avalé, comme avant.
        app(NotificationService::class)->notifyParentsReinscriptionCreated($inscription, 'passage', null);

        ReportDesCourrielsRefuses::entrer('test');
        $this->expectException(DebitMailPulseAtteint::class);
        app(NotificationService::class)->notifyParentsReinscriptionCreated($inscription, 'passage', null);
    }

    /** @test */
    public function le_job_de_relance_laisse_le_worker_reporter_au_lieu_d_echouer(): void
    {
        $service = Mockery::mock(NotificationService::class);
        $service->shouldReceive('envoyerRelanceEmail')->andThrow(new DebitMailPulseAtteint('plafond', 30));

        $relance = new class extends ESBTPRelance {
            public array $echecs = [];

            public function marquerCommeEchec($errorData = null)
            {
                $this->echecs[] = $errorData;
            }
        };
        $relance->setRawAttributes(['id' => 7, 'type' => 'email']);
        $job = new EnvoyerRelanceJob($relance);

        // Hors file : échec marqué, comme avant.
        $job->handle($service);
        $this->assertCount(1, $relance->echecs);

        // Dans une file : le refus remonte, aucun échec de plus.
        ReportDesCourrielsRefuses::entrer('test');
        try {
            $job->handle($service);
            $this->fail('Le refus de débit aurait dû remonter au worker.');
        } catch (DebitMailPulseAtteint $e) {
            $this->assertCount(1, $relance->echecs);
        }
    }

    private function executerUnJob(): void
    {
        app('queue.worker')->runNextJob('database', 'default', new WorkerOptions(
            'default', 0, 128, 60, 0, 3
        ));
    }

    private function relanceQuiRefuse(): object
    {
        return new class {
            public array $misesAJour = [];

            public function __get(string $nom): mixed
            {
                throw new DebitMailPulseAtteint('plafond', 30);
            }

            public function update(array $valeurs): void
            {
                $this->misesAJour[] = $valeurs;
            }
        };
    }
}

class JobDeTestCourriel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static ?bool $vuDansUneFile = null;

    public int $tries = 3;

    public function __construct(public string $conduite)
    {
    }

    public function handle(): void
    {
        self::$vuDansUneFile = ReportDesCourrielsRefuses::dansUneFile();

        if ($this->conduite === 'relache-puis-debit') {
            $this->release(5);
        }

        if ($this->conduite === 'panne') {
            throw new RuntimeException('panne');
        }

        throw new DebitMailPulseAtteint('plafond', 42);
    }
}
