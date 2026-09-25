<?php

namespace Tests\Feature\EmploiTemps;

use App\Domain\EmploiTemps\FenetresDEmargement;
use App\Domain\EmploiTemps\MomentDEmargement;
use App\Domain\EmploiTemps\ProlongationDeSeance;
use App\Models\ESBTPProlongationSeance;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Délais d'émargement réglables et prolongation d'un cours sans conflit,
 * exécutés sur une base SQLite en mémoire.
 *
 * Emploi du temps de référence : lundi 2026-09-14, classe 5.
 *   - séance 1 : 08:00–10:00, enseignant 7, salle A1 (celle qu'on prolonge)
 *   - séance 2 : 10:30–12:00, même classe (la prolongation au-delà de 10:30 la chevauche)
 *   - séance 3 : 10:00–11:00, enseignant 7 dans une AUTRE classe (autre emploi du temps)
 */
class ProlongationEtFenetresDEmargementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite_emg', 'database.connections.sqlite_emg' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('sqlite_emg');
        Cache::flush();
        Carbon::setTestNow('2026-09-14 09:40:00');

        $this->schema();
        $this->semer();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_les_defauts_reproduisent_l_ancien_comportement(): void
    {
        $f = new FenetresDEmargement();
        $debut = Carbon::parse('2026-09-14 08:00');

        $this->assertSame('08:00', $f->ouvertureDebut($debut)->format('H:i'));
        $this->assertSame('08:20', $f->limitePresent($debut)->format('H:i'));
        $this->assertSame('08:45', $f->limiteRetard($debut)->format('H:i'));
        $this->assertFalse($f->exigeJustification());
    }

    public function test_les_delais_suivent_les_reglages_de_l_ecole(): void
    {
        (new FenetresDEmargement())->ensureDefaults();
        DB::table('settings')->where('key', FenetresDEmargement::CLE_AVANCE)->update(['value' => '10']);
        DB::table('settings')->where('key', FenetresDEmargement::CLE_RETARD)->update(['value' => '60']);
        DB::table('settings')->where('key', FenetresDEmargement::CLE_DEPASSEMENT)->update(['value' => 'justification']);
        Cache::flush();

        $f = new FenetresDEmargement();
        $debut = Carbon::parse('2026-09-14 08:00');
        $this->assertSame('07:50', $f->ouvertureDebut($debut)->format('H:i'));
        $this->assertSame('09:00', $f->limiteRetard($debut)->format('H:i'));
        $this->assertTrue($f->exigeJustification());
    }

    public function test_un_delai_vide_ne_ferme_pas_l_emargement(): void
    {
        (new FenetresDEmargement())->ensureDefaults();
        DB::table('settings')->where('key', FenetresDEmargement::CLE_PRESENT)->update(['value' => '']);
        Cache::flush();

        $this->assertSame(20, (new FenetresDEmargement())->minutes(FenetresDEmargement::CLE_PRESENT));
    }

    public function test_un_seul_classement_decide_du_moment_d_emargement(): void
    {
        $f = new FenetresDEmargement();
        $debut = Carbon::parse('2026-09-14 08:00');

        $this->assertSame(MomentDEmargement::TropTot, $f->classerDebut(Carbon::parse('2026-09-14 07:59'), $debut));
        $this->assertSame(MomentDEmargement::Present, $f->classerDebut(Carbon::parse('2026-09-14 08:20'), $debut));
        $this->assertSame(MomentDEmargement::Retard, $f->classerDebut(Carbon::parse('2026-09-14 08:45'), $debut));
        $this->assertSame(MomentDEmargement::Depasse, $f->classerDebut(Carbon::parse('2026-09-14 08:46'), $debut));
        $this->assertTrue($f->marqueAbsentDOffice());
    }

    public function test_un_retard_regle_a_90_minutes_ne_classe_pas_depasse_a_50_minutes(): void
    {
        (new FenetresDEmargement())->ensureDefaults();
        DB::table('settings')->where('key', FenetresDEmargement::CLE_RETARD)->update(['value' => '90']);
        DB::table('settings')->where('key', FenetresDEmargement::CLE_DEPASSEMENT)->update(['value' => 'justification']);
        Cache::flush();

        $f = new FenetresDEmargement();
        $debut = Carbon::parse('2026-09-14 08:00');
        $this->assertSame(MomentDEmargement::Retard, $f->classerDebut(Carbon::parse('2026-09-14 08:50'), $debut));
        // Retard accepté avec motif : la tâche planifiée ne doit rien marquer absent.
        $this->assertFalse($f->marqueAbsentDOffice());
    }

    public function test_la_fenetre_de_fin_suit_la_prolongation_accordee(): void
    {
        $service = new ProlongationDeSeance();
        $seance = ESBTPSeanceCours::find(1);
        DB::table('esbtp_seance_cours')->where('id', 3)->delete();

        [$ouverture, $fermeture] = (new FenetresDEmargement())->fenetreDeFin($seance);
        $this->assertSame(['09:40', '10:30'], [$ouverture->format('H:i'), $fermeture->format('H:i')]);

        $service->accorder($service->demander($seance, User::find(1), 20, 'Fin du chapitre sur les poutres'), User::find(2));

        [$ouverture, $fermeture] = (new FenetresDEmargement())->fenetreDeFin($seance->fresh());
        $this->assertSame(['10:00', '10:50'], [$ouverture->format('H:i'), $fermeture->format('H:i')]);
    }

    public function test_une_prolongation_sur_un_creneau_libre_est_accordee_et_deplace_la_fin(): void
    {
        $service = new ProlongationDeSeance();
        $seance = ESBTPSeanceCours::find(1);
        DB::table('esbtp_seance_cours')->where('id', 3)->delete(); // l'enseignant est libre

        $p = $service->demander($seance, User::find(1), 20, 'Fin du chapitre sur les poutres');
        $this->assertSame([], $service->conflits($p));

        $service->accorder($p, User::find(2));

        $this->assertSame(ESBTPProlongationSeance::ACCORDEE, $p->fresh()->statut);
        $this->assertSame('10:20', $service->heureFinEffective($seance)->format('H:i'));
    }

    public function test_une_prolongation_qui_chevauche_le_cours_suivant_de_la_classe_est_refusee(): void
    {
        $service = new ProlongationDeSeance();
        DB::table('esbtp_seance_cours')->where('id', 3)->delete();

        $p = $service->demander(ESBTPSeanceCours::find(1), User::find(1), 45, 'Examen blanc à terminer');

        try {
            $service->accorder($p, User::find(2));
            $this->fail('La prolongation aurait dû être refusée.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('La classe a déjà une séance', implode(' ', $e->errors()['prolongation']));
        }

        $this->assertSame(ESBTPProlongationSeance::REFUSEE, $p->fresh()->statut);
        $this->assertNotEmpty($p->fresh()->conflits);
        $this->assertSame('10:00', $service->heureFinEffective(ESBTPSeanceCours::find(1))->format('H:i'));
    }

    public function test_une_prolongation_qui_empiete_sur_un_autre_cours_de_l_enseignant_est_refusee(): void
    {
        $service = new ProlongationDeSeance();
        $p = $service->demander(ESBTPSeanceCours::find(1), User::find(1), 15, 'Questions des étudiants');

        $conflits = $service->conflits($p);
        $this->assertNotEmpty($conflits);
        $this->assertStringContainsString('a déjà un cours à cet horaire', implode(' ', $conflits));
    }

    public function test_une_seconde_demande_en_attente_est_refusee(): void
    {
        $service = new ProlongationDeSeance();
        $service->demander(ESBTPSeanceCours::find(1), User::find(1), 15, 'Questions des étudiants');

        $this->expectException(ValidationException::class);
        $service->demander(ESBTPSeanceCours::find(1), User::find(1), 10, 'Encore une demande');
    }

    private function schema(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->string('type')->default('string');
            $t->string('group')->nullable();
            $t->string('category')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_required')->default(false);
            $t->boolean('is_active')->default(true);
            $t->text('default_value')->nullable();
            $t->text('validation_rules')->nullable();
            $t->integer('sort_order')->default(0);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('esbtp_emploi_temps', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('esbtp_seance_cours', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('emploi_temps_id')->nullable();
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->unsignedBigInteger('matiere_id')->nullable();
            $t->unsignedBigInteger('teacher_id')->nullable();
            $t->string('jour')->nullable();
            $t->time('heure_debut');
            $t->time('heure_fin');
            $t->string('salle')->nullable();
            $t->string('type')->nullable();
            $t->date('date_seance')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('esbtp_prolongations_seance', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('seance_cours_id');
            $t->date('date');
            $t->time('heure_fin_initiale');
            $t->time('heure_fin_demandee');
            $t->unsignedSmallInteger('minutes');
            $t->string('motif', 500);
            $t->string('statut', 20)->default('en_attente');
            $t->unsignedBigInteger('demandee_par')->nullable();
            $t->unsignedBigInteger('decidee_par')->nullable();
            $t->timestamp('decidee_le')->nullable();
            $t->string('motif_decision', 500)->nullable();
            $t->json('conflits')->nullable();
            $t->timestamps();
        });
        Schema::create('esbtp_teachers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function semer(): void
    {
        DB::table('users')->insert([['id' => 1, 'name' => 'KOUAME Yao'], ['id' => 2, 'name' => 'Coordination']]);
        DB::table('esbtp_teachers')->insert(['id' => 7, 'user_id' => 1]);
        DB::table('esbtp_emploi_temps')->insert([
            ['id' => 1, 'classe_id' => 5, 'date_debut' => '2026-09-14', 'date_fin' => '2026-12-20', 'is_active' => true],
            ['id' => 2, 'classe_id' => 6, 'date_debut' => '2026-09-14', 'date_fin' => '2026-12-20', 'is_active' => true],
        ]);
        $s = fn (array $c) => array_merge(['emploi_temps_id' => 1, 'classe_id' => 5, 'teacher_id' => 7, 'jour' => '1', 'type' => 'course', 'is_active' => true], $c);
        DB::table('esbtp_seance_cours')->insert([
            $s(['id' => 1, 'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00', 'salle' => 'A1']),
            $s(['id' => 2, 'heure_debut' => '10:30:00', 'heure_fin' => '12:00:00', 'teacher_id' => null, 'salle' => 'A1']),
            $s(['id' => 3, 'emploi_temps_id' => 2, 'classe_id' => 6, 'heure_debut' => '10:00:00', 'heure_fin' => '11:00:00', 'salle' => 'B2']),
        ]);
    }
}
