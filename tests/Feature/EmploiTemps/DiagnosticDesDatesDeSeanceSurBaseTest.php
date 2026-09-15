<?php

namespace Tests\Feature\EmploiTemps;

use App\Domain\EmploiTemps\DiagnosticDesDatesDeSeance;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le relevé et le rattrapage, exécutés contre une vraie base.
 *
 * ## Pourquoi ce test existe, et ce qu'il répare
 *
 * Le test unitaire voisin ne couvre que l'arithmétique, et son docbloc
 * expliquait que le reste — le comptage, le groupement, le rattrapage — était
 * « une requête SQL, et cet environnement n'a pas de MySQL ». La phrase était
 * vraie et la conclusion fausse : **`requete()` n'existait pas**, et les trois
 * points d'entrée tombaient sur `Call to undefined method` AVANT toute
 * connexion. Une seule exécution l'aurait montré ; l'honnêteté du docbloc a
 * servi à couvrir un trou qu'elle n'avait pas mesuré.
 *
 * D'où ce test, monté sur **SQLite en mémoire** : l'absence de MySQL n'était
 * pas une raison de ne rien exécuter. Il ne remplace pas un passage sur une
 * instance réelle — SQLite et MySQL ne typent pas pareil — mais il exécute le
 * vrai code sur une vraie base, ce qui est précisément ce qui manquait.
 *
 * ## Ce que la table de départ met à l'épreuve
 *
 * Cinq séances sans date, dont trois recalculables et **une sans enseignant**,
 * plus quatre pièges qui doivent être ignorés : une récréation, une pause
 * déjeuner, une séance déjà datée, une séance supprimée.
 *
 * La séance sans enseignant n'est pas un cas de figure inventé : elle est ce
 * qui distingue le total du relevé de ce que la paie recouperait. Son absence
 * de la table de départ est ce qui avait laissé passer un docbloc affirmant que
 * les deux coïncidaient.
 */
class DiagnosticDesDatesDeSeanceSurBaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite_diag', 'database.connections.sqlite_diag' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('sqlite_diag');

        $this->creerLeSchemaMinimal();
        $this->semerLesSeances();
    }

    private function creerLeSchemaMinimal(): void
    {
        Schema::create('esbtp_emploi_temps', function (Blueprint $t) {
            $t->id();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
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
            $t->string('type_seance')->nullable();
            $t->date('date_seance')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        foreach (['esbtp_matieres', 'esbtp_classes'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
                $t->unsignedBigInteger('unite_enseignement_id')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }

        Schema::create('esbtp_teachers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function semerLesSeances(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'KOUAME Yao'],
            ['id' => 2, 'name' => 'DIABATE Awa'],
        ]);
        DB::table('esbtp_teachers')->insert([
            ['id' => 7, 'user_id' => 1],
            ['id' => 9, 'user_id' => 2],
        ]);

        DB::table('esbtp_emploi_temps')->insert([
            // 2026-09-14 est un LUNDI : les dates recalculées se vérifient à la main.
            ['id' => 1, 'date_debut' => '2026-09-14', 'date_fin' => '2026-12-20'],
            ['id' => 2, 'date_debut' => null, 'date_fin' => null],
        ]);

        $seance = fn (array $champs) => array_merge([
            'emploi_temps_id' => 1, 'teacher_id' => 7, 'jour' => 'Lundi',
            'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00',
            'type' => 'course', 'date_seance' => null, 'deleted_at' => null,
        ], $champs);

        DB::table('esbtp_seance_cours')->insert([
            // Recalculables : 2h le lundi, 3h le mercredi.
            $seance([]),
            $seance(['jour' => 'Mercredi', 'heure_debut' => '14:00:00', 'heure_fin' => '17:00:00']),
            // Irrattrapables, une par raison.
            $seance(['teacher_id' => 9, 'jour' => 'Dimanche', 'heure_fin' => '09:30:00']),
            $seance(['emploi_temps_id' => 2, 'teacher_id' => 9, 'heure_fin' => '09:00:00']),
            // SANS enseignant : 3h. Population réelle et non marginale —
            // `ESBTPSeanceCoursController` pose `teacher_id = null` sur tout
            // `type = 'homework'`, donc sur TOUTES les évaluations LMD. Elle
            // entre au relevé (elle n'est ni récréation ni déjeuner) mais la
            // paie ne la compterait pas même datée : elle exige un enseignant.
            $seance(['type' => 'homework', 'teacher_id' => null, 'heure_debut' => '15:00:00', 'heure_fin' => '18:00:00']),
            // Hors périmètre : récréation, déjeuner, déjà datée, supprimée.
            $seance(['type' => 'break', 'heure_debut' => '10:00:00', 'heure_fin' => '10:15:00']),
            $seance(['type' => 'lunch', 'heure_debut' => '12:00:00', 'heure_fin' => '13:00:00']),
            $seance(['jour' => 'Mardi', 'date_seance' => '2026-09-15']),
            $seance(['jour' => 'Jeudi', 'heure_fin' => '11:00:00', 'deleted_at' => '2026-09-01 00:00:00']),
        ]);
    }

    private function diagnostic(): DiagnosticDesDatesDeSeance
    {
        return app(DiagnosticDesDatesDeSeance::class);
    }

    public function test_la_recreation_le_dejeuner_le_date_et_le_supprime_sont_hors_du_releve(): void
    {
        $rapport = $this->diagnostic()->rapport(50);

        // Cinq, et non neuf : la récréation, le déjeuner, la séance déjà datée
        // et la supprimée sont hors de la population du défaut.
        $this->assertSame(5, $rapport['total_sans_date']);
        $this->assertSame(3, $rapport['rattrapables']);
        $this->assertSame([
            'jour illisible' => 1,
            'emploi du temps sans date de début' => 1,
        ], $rapport['irrattrapables']);
    }

    public function test_les_heures_perdues_sont_chiffrees_et_non_nulles(): void
    {
        // 2 + 3 + 1,5 + 1 + 3. Ce total valait 0.0 tant que la durée était lue
        // sur l'attribut du modèle : c'est le seul chiffre qui rend ce défaut
        // visible, et il était le seul à être faux.
        $this->assertSame(10.5, $this->diagnostic()->rapport(50)['heures_perdues']);
    }

    public function test_les_heures_sans_enseignant_sont_comptees_a_part(): void
    {
        // La borne que le relevé NE reprend PAS de la paie est `teacher_id`.
        // Le total global est donc plus large que le recoupable, et publier la
        // somme seule ferait engager une écriture de masse sur un chiffre dont
        // une part ne se rapprochera d'aucun bulletin — c'est précisément pour
        // cela que les deux sont séparés.
        $rapport = $this->diagnostic()->rapport(50);

        $this->assertSame(7.5, $rapport['heures_recoupables_paie']);
        $this->assertSame(3.0, $rapport['heures_sans_enseignant']);
        $this->assertSame(
            $rapport['heures_perdues'],
            round($rapport['heures_recoupables_paie'] + $rapport['heures_sans_enseignant'], 2),
        );
    }

    public function test_les_heures_sont_groupees_par_enseignant(): void
    {
        $parEnseignant = collect($this->diagnostic()->rapport(50)['par_enseignant'])
            ->keyBy('teacher_id');

        $this->assertSame(5.0, $parEnseignant[7]['heures']);
        $this->assertSame(2.5, $parEnseignant[9]['heures']);

        // La séance sans enseignant a sa propre ligne, sous un libellé qui le
        // dit : la faire disparaître du groupement la rendrait invisible alors
        // que c'est précisément un défaut à corriger.
        $this->assertSame(3.0, $parEnseignant[null]['heures']);
        $this->assertSame('(aucun enseignant affecté)', $parEnseignant[null]['enseignant']);
    }

    public function test_le_detail_rend_les_heures_brutes(): void
    {
        // Et non « 2026-09-15 08:00:00 » : l'accesseur du modèle colle la date
        // du jour devant l'heure, dans un rapport qui traque des dates manquantes.
        $this->assertSame('08:00:00', $this->diagnostic()->rapport(50)['detail'][0]['heure_debut']);
    }

    public function test_le_releve_se_restreint_au_meme_perimetre_que_l_ecriture(): void
    {
        $rapport = $this->diagnostic()->rapport(0, 2);

        $this->assertSame(1, $rapport['total_sans_date']);
        $this->assertSame(0, $rapport['rattrapables']);
    }

    public function test_la_simulation_n_ecrit_rien(): void
    {
        $avant = DB::table('esbtp_seance_cours')->whereNull('date_seance')->count();

        $resultat = $this->diagnostic()->rattraper(false);

        $this->assertFalse($resultat['applique']);
        $this->assertSame(3, $resultat['dates_posees']);
        $this->assertSame(
            $avant,
            DB::table('esbtp_seance_cours')->whereNull('date_seance')->count(),
            'La simulation a écrit en base.'
        );
    }

    public function test_le_rattrapage_pose_la_date_du_bon_jour(): void
    {
        $this->diagnostic()->rattraper(true);

        $dates = DB::table('esbtp_seance_cours')
            ->whereIn('id', [1, 2])->orderBy('id')->pluck('date_seance', 'id');

        // Période ouverte le lundi 14 : lundi → 14, mercredi → 16.
        $this->assertStringStartsWith('2026-09-14', $dates[1]);
        $this->assertStringStartsWith('2026-09-16', $dates[2]);
    }

    public function test_le_rattrapage_est_rejouable(): void
    {
        $this->diagnostic()->rattraper(true);

        // N'écrit que là où la date est nulle : un second passage ne déplace
        // aucune séance déjà datée.
        $this->assertSame(0, $this->diagnostic()->rattraper(true)['dates_posees']);
    }
}
