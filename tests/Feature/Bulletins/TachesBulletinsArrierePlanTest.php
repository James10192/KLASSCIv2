<?php

namespace Tests\Feature\Bulletins;

use App\Console\Commands\TraiterTachesBulletins;
use App\Domain\AcademicPilotage\DTO\BulkBulletinGenerationResult;
use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Domain\Bulletins\Taches\BulletinTache;
use App\Domain\Bulletins\Taches\ExecuteurTachesBulletins;
use App\Domain\Bulletins\Taches\LancementTachesBulletins;
use App\Domain\Bulletins\Taches\NotificationTachesBulletins;
use App\Domain\Bulletins\Taches\SuiviTachesBulletins;
use App\Http\Controllers\ESBTPBulletinTacheController;
use App\Mail\TacheBulletinsTermineeMail;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\User;
use App\Services\BulletinBulkPdfExporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Génération et PDF groupé des bulletins en arrière-plan.
 *
 * Ce qui est éprouvé ici : la tâche avance tranche par tranche, reprend où
 * elle s'est arrêtée, finit, et prévient son demandeur — cloche, e-mail à une
 * adresse vérifiée, toast. Et qu'un échec est dit, jamais avalé.
 *
 * Le calcul des bulletins n'est pas rejoué : il n'appartient pas à ce module,
 * qui ne fait qu'orchestrer. Les trois appels qui produisent sont remplacés.
 */
class TachesBulletinsArrierePlanTest extends TestCase
{
    /** @var array<int, array<int, int>> */
    public array $tranchesGenerees = [];

    public ?BulkBulletinGenerationResult $resultatImpose = null;

    public bool $renduEchoue = false;

    /** @var array<int, int> tâches reclassées, une entrée par reclassement */
    public array $reclassements = [];

    /** @var array<int, int> tâches dont chaque tranche lève */
    public array $tachesEnPanne = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'audit.enabled' => false,
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('custom_notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('title');
            $t->text('message');
            $t->string('type')->default('info');
            $t->boolean('is_read')->default(false);
            $t->string('link')->nullable();
            $t->unsignedBigInteger('sent_by')->nullable();
            $t->timestamps();
        });
        Schema::create('esbtp_classes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('systeme_academique')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        // Chargées d'office avec une classe ($with) : elles doivent exister.
        foreach (['esbtp_filieres', 'esbtp_niveau_etudes', 'esbtp_annee_universitaires'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
                $t->softDeletes();
                $t->timestamps();
            });
        }
        Schema::create('esbtp_bulletins', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('etudiant_id')->nullable();
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->timestamp('archived_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        (require database_path('migrations/2026_10_01_140000_create_esbtp_bulletin_taches_table.php'))->up();

        $now = now();
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Awa', 'email' => 'awa@ecole.test', 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Kouassi', 'email' => 'kouassi@ecole.test', 'email_verified_at' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_classes')->insert([
            ['id' => 1, 'name' => '1BTS GC A', 'systeme_academique' => 'BTS', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Licence 1 GC', 'systeme_academique' => 'LMD', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->app->instance(ExecuteurTachesBulletins::class, $this->executeur());
        Mail::fake();
    }

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/taches_bulletins/tache_*.pdf')) ?: [] as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function executeur(): ExecuteurTachesBulletins
    {
        $test = $this;

        return new class(app(BtsBulkBulletinGenerationService::class), app(BulletinBulkPdfExporter::class), app(NotificationTachesBulletins::class), $test) extends ExecuteurTachesBulletins
        {
            public function __construct($g, $e, $n, private readonly TachesBulletinsArrierePlanTest $test)
            {
                parent::__construct($g, $e, $n);
            }

            protected function genererLaTranche(ESBTPClasse $classe, BulletinTache $tache, array $ids): BulkBulletinGenerationResult
            {
                if (in_array($tache->id, $this->test->tachesEnPanne, true)) {
                    throw new \RuntimeException('Mémoire épuisée');
                }
                $this->test->tranchesGenerees[] = $ids;

                return $this->test->resultatImpose ?? new BulkBulletinGenerationResult(created: count($ids));
            }

            protected function reclasserLaClasse(BulletinTache $tache): void
            {
                $this->test->reclassements[] = $tache->id;
            }

            protected function rendreUnBulletin(ESBTPBulletin $bulletin): \Barryvdh\DomPDF\PDF
            {
                if ($this->test->renduEchoue) {
                    throw new \RuntimeException('Rendu impossible');
                }

                return \PDF::loadHTML('<p>Bulletin '.$bulletin->id.'</p>');
            }

            protected function pageDeGarde(Collection $absents, array $echecs, array $entete, int $inclus): ?\Barryvdh\DomPDF\PDF
            {
                return null;
            }
        };
    }

    private function generation(int $userId = 1, int $classeId = 1, int $eleves = 8, string $periode = 'semestre1'): BulletinTache
    {
        return app(LancementTachesBulletins::class)->generation(
            User::findOrFail($userId),
            ESBTPClasse::findOrFail($classeId),
            1,
            $periode,
            range(101, 100 + $eleves),
            false,
            null
        );
    }

    public function test_la_generation_avance_par_tranches_reprend_et_previent_le_demandeur(): void
    {
        $tache = $this->generation();
        $executeur = app(ExecuteurTachesBulletins::class);

        // L'onglet : une tranche par requête.
        $this->assertSame(1, $executeur->avancer($tache, 20, 1));
        $tache->refresh();
        $this->assertSame(BulletinTache::EN_COURS, $tache->statut);
        $this->assertSame(6, $tache->position);
        $this->assertSame(75, $tache->pourcent());

        // La planification reprend où l'onglet s'est arrêté.
        $executeur->avancer($tache, 50);
        $tache->refresh();

        $this->assertSame([range(101, 106), [107, 108]], $this->tranchesGenerees);
        // Une seule fois, à la conclusion : pas une par tranche.
        $this->assertSame([$tache->id], $this->reclassements);
        $this->assertSame(BulletinTache::TERMINEE, $tache->statut);
        $this->assertSame(8, $tache->resultat['created']);
        $this->assertNotNull($tache->cloche_at);

        $notification = DB::table('custom_notifications')->where('user_id', 1)->first();
        $this->assertNotNull($notification);
        $this->assertSame('success', $notification->type);
        $this->assertStringContainsString('classe_id=1', (string) $notification->link);
        // Lien relatif : la cloche reste sur l'hôte d'où l'on regarde.
        $this->assertStringStartsWith('/', (string) $notification->link);

        // L'e-mail ne part jamais dans la requête qui finit la tâche…
        Mail::assertNothingSent();
        $this->assertNull($tache->notifiee_at);

        // …mais par la planification, si la personne n'a pas vu la fin à l'écran.
        $this->travel(NotificationTachesBulletins::DELAI_COURRIEL_DEFAUT + 1)->minutes();
        app(NotificationTachesBulletins::class)->rattraper();

        Mail::assertSent(TacheBulletinsTermineeMail::class, fn ($m) => $m->hasTo('awa@ecole.test')
            && str_starts_with((string) $m->lien, 'http'));
        $tache->refresh();
        $this->assertNotNull($tache->email_envoye_at);
        $this->assertNotNull($tache->notifiee_at);
    }

    public function test_pas_d_e_mail_si_la_fin_a_ete_vue_a_l_ecran(): void
    {
        $tache = $this->generation(eleves: 2);
        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);

        // Avant le délai : rien.
        app(NotificationTachesBulletins::class)->rattraper();
        Mail::assertNothingSent();

        app(ESBTPBulletinTacheController::class)->marquerVue($this->requetePour(1), $tache->fresh());

        $this->travel(NotificationTachesBulletins::DELAI_COURRIEL_DEFAUT + 1)->minutes();
        app(NotificationTachesBulletins::class)->rattraper();

        Mail::assertNothingSent();
        $tache->refresh();
        $this->assertNotNull($tache->notifiee_at);
        $this->assertNull($tache->email_envoye_at);
    }

    public function test_une_tranche_qui_echoue_trois_fois_fait_echouer_la_tache_en_la_nommant(): void
    {
        $tache = $this->generation(eleves: 8);
        $this->tachesEnPanne = [$tache->id];
        $executeur = app(ExecuteurTachesBulletins::class);

        // Une erreur rattrapée n'achève plus la tâche d'un coup : elle compte un essai.
        $executeur->avancer($tache, 50);
        $this->assertSame(BulletinTache::EN_COURS, $tache->fresh()->statut);
        $this->assertSame(1, $tache->fresh()->essais_position);
        $executeur->avancer($tache, 50);
        $this->assertSame(BulletinTache::EN_COURS, $tache->fresh()->statut);

        $executeur->avancer($tache, 50);
        $tache->refresh();

        $this->assertSame(BulletinTache::ECHOUEE, $tache->statut);
        $this->assertStringContainsString('tranche 1 sur 2', (string) $tache->message);
        $this->assertStringContainsString('3 fois', (string) $tache->message);
        $this->assertSame('error', DB::table('custom_notifications')->where('user_id', 1)->value('type'));
    }

    public function test_une_tranche_qui_tue_le_processus_n_est_pas_rejouee_a_l_infini(): void
    {
        // Trois essais datés, aucun n'a rendu la main : le processus a été tué
        // (mémoire, hébergeur) sans passer par aucun catch.
        $tache = $this->generation(eleves: 8);
        $tache->forceFill(['statut' => BulletinTache::EN_COURS, 'position_essayee' => 0, 'essais_position' => 3])->save();

        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);
        $tache->refresh();

        $this->assertSame([], $this->tranchesGenerees);
        $this->assertSame(BulletinTache::ECHOUEE, $tache->statut);
        $this->assertStringContainsString('élèves 1 à 6', (string) $tache->message);
        $this->assertNotNull($tache->cloche_at);
    }

    public function test_un_export_ecarte_la_tranche_qui_tue_le_processus_et_continue(): void
    {
        $now = now();
        foreach (range(701, 707) as $id) {
            DB::table('esbtp_bulletins')->insert(['id' => $id, 'classe_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $tache = app(LancementTachesBulletins::class)->export(User::findOrFail(1), 'telechargement', range(701, 707), [
            'entete' => [], 'ungenerated_ids' => [], 'filtres' => [],
        ]);
        $tache->forceFill(['statut' => BulletinTache::EN_COURS, 'position_essayee' => 0, 'essais_position' => 3])->save();

        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);
        $tache->refresh();

        $this->assertSame(BulletinTache::TERMINEE, $tache->statut);
        $this->assertSame(1, $tache->resultat['rendus']);
        $this->assertCount(6, $tache->resultat['echecs']);
        $this->assertStringContainsString('Tranche 1 sur 2 écartée', $tache->resultat['echecs'][0]['message']);
        $this->assertStringContainsString("6 n'ont pas pu être rendus", (string) $tache->message);
    }

    public function test_une_tache_en_panne_ne_bloque_pas_les_suivantes_de_l_ecole(): void
    {
        $enPanne = $this->generation(classeId: 1, eleves: 2);
        $saine = $this->generation(eleves: 2, periode: 'semestre2');
        $this->tachesEnPanne = [$enPanne->id];

        app(ExecuteurTachesBulletins::class)->traiterLaFile(50);

        $this->assertSame(BulletinTache::TERMINEE, $saine->fresh()->statut);
        $this->assertSame(BulletinTache::EN_COURS, $enPanne->fresh()->statut);
        $this->assertSame(1, $enPanne->fresh()->essais_position);
    }

    public function test_une_tache_immobile_est_dite_en_pause(): void
    {
        $tache = $this->generation(eleves: 2);
        $this->assertFalse(SuiviTachesBulletins::etat($tache)['en_pause']);

        DB::table('esbtp_bulletin_taches')->where('id', $tache->id)
            ->update(['updated_at' => now()->subMinutes(SuiviTachesBulletins::PAUSE_MINUTES + 1)]);
        $etat = SuiviTachesBulletins::etat($tache->fresh());

        $this->assertTrue($etat['en_pause']);
        $this->assertSame(ExecuteurTachesBulletins::TAILLE_TRANCHE, $etat['taille']);
        $this->assertSame(1, $etat['tranches']);
    }

    public function test_une_seconde_personne_rejoint_la_generation_en_cours_et_est_prevenue(): void
    {
        $premiere = $this->generation(userId: 1, eleves: 2);
        $seconde = $this->generation(userId: 2, eleves: 2);

        $this->assertSame($premiere->id, $seconde->id);
        $this->assertSame(1, BulletinTache::count());
        $this->assertTrue($premiere->concerne(2));

        $controleur = app(ESBTPBulletinTacheController::class);
        $this->assertSame([$premiere->id], array_column($controleur->suivi($this->requetePour(2))->getData(true)['taches'], 'id'));

        app(ExecuteurTachesBulletins::class)->avancer($premiere, 50);

        $this->assertSame(1, DB::table('custom_notifications')->where('user_id', 1)->count());
        $this->assertSame(1, DB::table('custom_notifications')->where('user_id', 2)->count());

        // Chacun marque sa propre vue.
        $controleur->marquerVue($this->requetePour(2), $premiere->fresh());
        $this->assertSame([], $controleur->suivi($this->requetePour(2))->getData(true)['taches']);
        $this->assertCount(1, $controleur->suivi($this->requetePour(1))->getData(true)['taches']);
    }

    public function test_l_etat_propose_de_confirmer_une_adresse_non_verifiee(): void
    {
        $tache = $this->generation(userId: 2, eleves: 2);

        $this->assertTrue(SuiviTachesBulletins::etat($tache, User::findOrFail(2))['email_a_verifier']);
        $this->assertFalse(SuiviTachesBulletins::etat($tache, User::findOrFail(1))['email_a_verifier']);
    }

    public function test_une_generation_echouee_ramene_sur_l_ecran_pre_rempli(): void
    {
        $tache = $this->generation(classeId: 2);
        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);
        $etat = SuiviTachesBulletins::etat($tache->fresh());

        $this->assertStringContainsString('classe_id=2', (string) $etat['url']);
        $this->assertStringContainsString('periode=semestre1', (string) $etat['url']);
        $this->assertSame('Relancer la génération', $etat['libelle_lien']);
    }

    public function test_aucun_courriel_vers_une_adresse_non_verifiee(): void
    {
        $tache = $this->generation(userId: 2, eleves: 2);
        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);

        $this->assertSame(BulletinTache::TERMINEE, $tache->fresh()->statut);
        $this->assertSame(1, DB::table('custom_notifications')->where('user_id', 2)->count());

        $this->travel(NotificationTachesBulletins::DELAI_COURRIEL_DEFAUT + 1)->minutes();
        app(NotificationTachesBulletins::class)->rattraper();
        Mail::assertNothingSent();
    }

    public function test_une_classe_lmd_echoue_avec_un_message_clair(): void
    {
        $tache = $this->generation(classeId: 2);
        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);
        $tache->refresh();

        $this->assertSame(BulletinTache::ECHOUEE, $tache->statut);
        $this->assertStringContainsString('LMD', (string) $tache->message);
        $this->assertSame([], $this->tranchesGenerees);
        $this->assertSame('error', DB::table('custom_notifications')->value('type'));
    }

    public function test_un_blocage_de_configuration_arrete_la_tache_des_la_premiere_tranche(): void
    {
        $this->resultatImpose = new BulkBulletinGenerationResult(blockingErrors: [
            ['code' => 'professeurs_missing', 'message' => 'Les professeurs du bulletin sont a completer.'],
        ]);

        $tache = $this->generation(eleves: 20);
        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);
        $tache->refresh();

        $this->assertCount(1, $this->tranchesGenerees);
        $this->assertSame(BulletinTache::ECHOUEE, $tache->statut);
        $this->assertSame('Les professeurs du bulletin sont a completer.', $tache->message);
    }

    public function test_une_generation_sans_ecriture_mais_avec_erreurs_est_un_echec(): void
    {
        $this->resultatImpose = new BulkBulletinGenerationResult(errors: [['code' => 'generation_failed', 'message' => 'x']]);

        $tache = $this->generation(eleves: 3);
        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);

        $this->assertSame(BulletinTache::ECHOUEE, $tache->fresh()->statut);
        $this->assertSame([], $this->reclassements, 'Rien d\'écrit : rien à reclasser.');
    }

    public function test_une_generation_arretee_en_route_reclasse_les_tranches_deja_ecrites(): void
    {
        $tache = $this->generation(eleves: 8);
        $executeur = app(ExecuteurTachesBulletins::class);
        $executeur->avancer($tache, 20, 1);
        $this->assertSame([], $this->reclassements, 'Pas de reclassement par tranche.');

        $this->tachesEnPanne = [$tache->id];
        foreach (range(1, ExecuteurTachesBulletins::ESSAIS_MAX) as $_) {
            $executeur->avancer($tache, 50);
        }

        $this->assertSame(BulletinTache::ECHOUEE, $tache->fresh()->statut);
        $this->assertSame([$tache->id], $this->reclassements, 'Les six bulletins écrits doivent porter un rang juste.');
    }

    public function test_une_tache_tenue_ailleurs_n_est_pas_traitee_deux_fois(): void
    {
        $tache = $this->generation();
        $verrou = Cache::lock('bulletin_tache.'.$tache->id, 60);
        $this->assertTrue($verrou->get());

        $this->assertNull(app(ExecuteurTachesBulletins::class)->avancer($tache, 50));
        $this->assertSame([], $this->tranchesGenerees);

        $verrou->release();
    }

    public function test_relancer_la_meme_generation_rend_la_tache_en_cours(): void
    {
        $premiere = $this->generation();
        $seconde = $this->generation();

        $this->assertSame($premiere->id, $seconde->id);
        $this->assertSame(1, BulletinTache::count());
    }

    public function test_le_pdf_groupe_est_assemble_range_et_servi_a_son_seul_demandeur(): void
    {
        $now = now();
        DB::table('esbtp_bulletins')->insert([
            ['id' => 501, 'classe_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 502, 'classe_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $tache = app(LancementTachesBulletins::class)->export(User::findOrFail(1), 'apercu', [502, 501], [
            'entete' => ['classe' => '1BTS GC A'],
            'ungenerated_ids' => [],
            'filtres' => ['classe_id' => 1],
        ]);

        // Reste d'une tranche interrompue avant l'enregistrement de l'avancée :
        // elle sera rejouée, et ce bulletin ne doit pas sortir deux fois.
        $dossier = app(BulletinBulkPdfExporter::class)->dossierDeSession('tache'.$tache->id);
        file_put_contents($dossier.'/blt_000000_reste.pdf', \PDF::loadHTML('<p>reste</p>')->output());

        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);
        $tache->refresh();

        $this->assertSame(BulletinTache::TERMINEE, $tache->statut);
        $this->assertSame(2, $tache->resultat['rendus']);
        $this->assertSame(2, (new Fpdi)->setSourceFile($tache->fichier));
        $this->assertDirectoryDoesNotExist($dossier);
        $this->assertFileExists($tache->fichier);
        $this->assertStringStartsWith(storage_path('app/taches_bulletins'), $tache->fichier);
        $this->assertStringContainsString('PDF prêt : 2 bulletin(s)', $tache->message);

        $etat = SuiviTachesBulletins::etat($tache);
        $this->assertStringContainsString('/bulletins-taches/'.$tache->id.'/fichier', (string) $etat['url']);

        $controleur = app(ESBTPBulletinTacheController::class);
        $reponse = $controleur->fichier($this->requetePour(1), $tache);
        $this->assertSame(200, $reponse->getStatusCode());

        $this->expectException(NotFoundHttpException::class);
        $controleur->fichier($this->requetePour(2), $tache);
    }

    public function test_un_export_dont_aucun_bulletin_ne_se_rend_echoue_au_lieu_de_servir_un_document_vide(): void
    {
        $this->renduEchoue = true;
        DB::table('esbtp_bulletins')->insert(['id' => 601, 'classe_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $tache = app(LancementTachesBulletins::class)->export(User::findOrFail(1), 'telechargement', [601], [
            'entete' => [], 'ungenerated_ids' => [], 'filtres' => [],
        ]);
        app(ExecuteurTachesBulletins::class)->avancer($tache, 50);
        $tache->refresh();

        $this->assertSame(BulletinTache::ECHOUEE, $tache->statut);
        $this->assertNull($tache->fichier);
        $this->assertStringContainsString("Aucun bulletin n'a pu être rendu", (string) $tache->message);
    }

    public function test_le_suivi_ne_montre_que_ses_propres_taches_et_la_vue_les_retire(): void
    {
        $mienne = $this->generation(userId: 1, eleves: 2);
        $autre = $this->generation(userId: 2, eleves: 2, periode: 'semestre2');
        app(ExecuteurTachesBulletins::class)->avancer($mienne, 50);

        $controleur = app(ESBTPBulletinTacheController::class);
        $suivi = $controleur->suivi($this->requetePour(1))->getData(true);

        $this->assertSame([$mienne->id], array_column($suivi['taches'], 'id'));
        $this->assertTrue($suivi['taches'][0]['finale']);

        $controleur->marquerVue($this->requetePour(1), $mienne->fresh());
        $this->assertSame([], $controleur->suivi($this->requetePour(1))->getData(true)['taches']);

        // La tâche d'un autre n'existe pas pour moi.
        $this->expectException(NotFoundHttpException::class);
        $controleur->etat($this->requetePour(1), $autre);
    }

    public function test_l_avancee_depuis_l_onglet_rend_l_etat_et_signale_une_tache_occupee(): void
    {
        $tache = $this->generation(eleves: 8);
        $controleur = app(ESBTPBulletinTacheController::class);

        $donnees = $controleur->avancer($this->requetePour(1), $tache)->getData(true);
        $this->assertFalse($donnees['occupee']);
        $this->assertSame(6, $donnees['tache']['position']);

        $verrou = Cache::lock('bulletin_tache.'.$tache->id, 60);
        $verrou->get();
        $donnees = $controleur->avancer($this->requetePour(1), $tache->fresh())->getData(true);
        $this->assertTrue($donnees['occupee']);
        $this->assertSame(6, $donnees['tache']['position']);
        $verrou->release();

        // La dernière tranche ne conclut pas dans la même requête : la
        // conclusion (assemblage) ne doit pas s'ajouter à une tranche sous la
        // limite des trente secondes.
        $donnees = $controleur->avancer($this->requetePour(1), $tache->fresh())->getData(true);
        $this->assertSame(8, $donnees['tache']['position']);
        $this->assertFalse($donnees['tache']['finale']);

        // L'appel suivant conclut, et c'est tout ce qu'il fait.
        $donnees = $controleur->avancer($this->requetePour(1), $tache->fresh())->getData(true);
        $this->assertTrue($donnees['tache']['finale']);
        $this->assertSame('terminee', $donnees['tache']['statut']);
        $this->assertCount(2, $this->tranchesGenerees);
    }

    public function test_la_commande_planifiee_finit_les_taches_et_purge_les_documents_expires(): void
    {
        $tache = $this->generation(eleves: 8);

        $expire = BulletinTache::create([
            'type' => BulletinTache::TYPE_EXPORT, 'user_id' => 1, 'statut' => BulletinTache::TERMINEE,
            'elements' => [], 'total' => 0, 'notifiee_at' => now(),
            'terminee_at' => now()->subHours(BulletinTache::CONSERVATION_HEURES + 1),
            'fichier' => $chemin = storage_path('app/taches_bulletins/tache_test_expire.pdf'),
        ]);
        @mkdir(dirname($chemin), 0755, true);
        file_put_contents($chemin, '%PDF');

        $this->assertSame(0, Artisan::call(TraiterTachesBulletins::NOM, ['--budget' => 50]));

        $this->assertSame(BulletinTache::TERMINEE, $tache->fresh()->statut);
        $this->assertFileDoesNotExist($chemin);
        $this->assertNull($expire->fresh()->fichier);
    }

    public function test_une_notification_manquee_est_rattrapee(): void
    {
        $tache = BulletinTache::create([
            'type' => BulletinTache::TYPE_GENERATION, 'user_id' => 1, 'classe_id' => 1,
            'annee_universitaire_id' => 1, 'periode' => 'semestre1',
            'statut' => BulletinTache::TERMINEE, 'message' => 'Fini.',
            'elements' => [1], 'total' => 1, 'position' => 1, 'terminee_at' => now(),
        ]);

        $this->assertSame(1, app(NotificationTachesBulletins::class)->rattraper());
        $this->assertNotNull($tache->fresh()->cloche_at);
        $this->assertSame(0, app(NotificationTachesBulletins::class)->rattraper());
    }

    public function test_le_composant_ne_connait_que_les_taches_du_visiteur(): void
    {
        // Le compositeur de vues global lit les rôles du visiteur.
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name')->default('web');
            $t->timestamps();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name')->default('web');
            $t->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->unsignedBigInteger('role_id');
        });

        $this->generation(userId: 1, eleves: 2);
        $this->generation(userId: 2, eleves: 2, periode: 'semestre2');

        $this->actingAs(User::findOrFail(1));
        $html = Blade::render('<x-taches-arriere-plan />');

        $this->assertStringContainsString('x-data="tachesArrierePlan()"', $html);
        $this->assertStringContainsString('esbtp-special\/bulletins-taches', $html);
        $donnees = [];
        preg_match("/data-taches='([^']*)'/", $html, $donnees);
        $this->assertCount(1, json_decode(html_entity_decode($donnees[1]), true));
    }

    private function requetePour(int $userId): Request
    {
        $requete = Request::create('/');
        $user = User::findOrFail($userId);
        $requete->setUserResolver(fn () => $user);

        return $requete;
    }
}
