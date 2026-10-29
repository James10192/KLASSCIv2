<?php

namespace Tests\Unit\Planning;

use App\Services\Planning\PlageHoraireJournee;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * La plage horaire d'une journee de cours est un reglage d'etablissement.
 *
 * Les reglages sont servis depuis le cache (`setting_<cle>`) : on les y pose
 * directement, sans base de donnees.
 */
class PlageHoraireJourneeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_sans_reglage_la_plage_reste_celle_d_avant(): void
    {
        $this->regler(null, null);
        $plage = new PlageHoraireJournee();

        $this->assertSame(['debut' => 7, 'fin' => 18], $plage->pourLeNavigateur());
        $this->assertSame(range(7, 17), $plage->creneaux());
        $this->assertSame(range(7, 18), $plage->heuresDeSaisie());
    }

    public function test_une_ecole_du_soir_etend_la_journee_jusqu_a_22h(): void
    {
        $this->regler(8, 22);
        $plage = new PlageHoraireJournee();

        $this->assertSame(21, last($plage->creneaux()));
        $this->assertSame(22, last($plage->heuresDeSaisie()));
        $this->assertSame(8, $plage->debut());
    }

    public function test_la_grille_de_disponibilite_s_arrete_au_dernier_creneau(): void
    {
        // Journee 8h-22h : dernier creneau 21h-22h. Une ligne « 22h » ouvrirait
        // un creneau 22h-23h hors de la journee reglee.
        $this->regler(8, 22);
        $this->app->forgetScopedInstances();

        $enseignant = new \App\Models\ESBTPTeacher();
        $enseignant->setRelation('availabilities', collect([
            new \App\Models\ESBTPTeacherAvailability([
                'day_of_week' => 0, 'start_time' => '21:00', 'end_time' => '22:00', 'availability_type' => 'available',
            ]),
        ]));

        $matrice = app(\App\Services\TeacherPlanningService::class)->getAvailabilityMatrix($enseignant);

        $this->assertSame(range(8, 21), $matrice['hours']);
        $this->assertCount(14, $matrice['availability']['monday']);
        $this->assertSame('available', $matrice['availability']['monday'][21 - 8]);
    }

    public function test_la_forme_heure_minutes_est_acceptee(): void
    {
        $this->regler('07:30', '21:00');

        $this->assertSame(['debut' => 7, 'fin' => 21], (new PlageHoraireJournee())->pourLeNavigateur());
    }

    /** @dataProvider reglagesIncoherents */
    public function test_un_reglage_incoherent_retombe_sur_la_plage_par_defaut(mixed $debut, mixed $fin): void
    {
        $this->regler($debut, $fin);

        $this->assertSame(['debut' => 7, 'fin' => 18], (new PlageHoraireJournee())->pourLeNavigateur());
    }

    public static function reglagesIncoherents(): array
    {
        return [
            'fin avant le debut' => [18, 8],
            'debut egal a la fin' => [10, 10],
            'fin au-dela de 23h' => [7, 24],
            'valeur illisible' => ['matin', 18],
        ];
    }

    private function regler(mixed $debut, mixed $fin): void
    {
        // Setting::get met en cache le resultat, defaut compris : poser la
        // valeur sous la cle de cache revient a poser le reglage.
        Cache::put('setting_'.PlageHoraireJournee::CLE_DEBUT, $debut ?? PlageHoraireJournee::DEBUT_PAR_DEFAUT, 60);
        Cache::put('setting_'.PlageHoraireJournee::CLE_FIN, $fin ?? PlageHoraireJournee::FIN_PAR_DEFAUT, 60);
    }
}
