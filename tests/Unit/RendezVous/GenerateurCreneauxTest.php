<?php

namespace Tests\Unit\RendezVous;

use App\Services\RendezVous\CreneauRegle;
use App\Services\RendezVous\GenerateurCreneaux;
use Carbon\Carbon;
use Tests\TestCase;

class GenerateurCreneauxTest extends TestCase
{
    public function test_la_pause_n_est_pas_un_creneau(): void
    {
        $regle = $this->regle(pauseDebut: '12:00', pauseFin: '13:00');

        $horaires = GenerateurCreneaux::horairesDuJour($regle);
        $debuts = array_column($horaires, 0);

        $this->assertContains('11:30', $debuts);
        $this->assertNotContains('12:00', $debuts);
        $this->assertNotContains('12:30', $debuts);
        $this->assertContains('13:00', $debuts);
    }

    public function test_un_reste_plus_court_que_la_duree_n_est_pas_un_creneau(): void
    {
        $regle = $this->regle(heureFin: '09:20', duree: 30);

        $this->assertSame(
            [['08:00', '08:30'], ['08:30', '09:00']],
            GenerateurCreneaux::horairesDuJour($regle)
        );
    }

    public function test_le_week_end_est_ignore_si_les_jours_sont_ouvres(): void
    {
        $regle = $this->regle(
            ouverture: '2026-09-14',
            fermeture: '2026-09-20',
            plancher: '2026-09-14',
        );

        $slots = GenerateurCreneaux::theoriques($regle, Carbon::parse('2026-09-07 07:00:00'));
        $dates = array_unique(array_column($slots, 'date'));

        $this->assertContains('2026-09-14', $dates);
        $this->assertContains('2026-09-18', $dates);
        $this->assertNotContains('2026-09-19', $dates);
        $this->assertNotContains('2026-09-20', $dates);
    }

    public function test_un_creneau_deja_passe_n_est_pas_propose(): void
    {
        $regle = $this->regle(
            ouverture: '2026-09-07',
            fermeture: '2026-09-07',
            plancher: '2026-09-07',
        );

        $slots = GenerateurCreneaux::theoriques($regle, Carbon::parse('2026-09-07 09:05:00'));
        $debuts = array_column($slots, 'heure_debut');

        $this->assertNotContains('08:00', $debuts);
        $this->assertNotContains('09:00', $debuts);
        $this->assertContains('09:30', $debuts);
    }

    public function test_le_plancher_physique_repousse_la_generation(): void
    {
        $regle = $this->regle(
            ouverture: '2026-08-01',
            fermeture: '2026-09-14',
            plancher: '2026-09-12',
        );

        $slots = GenerateurCreneaux::theoriques($regle, Carbon::parse('2026-08-15 07:00:00'));
        $dates = array_unique(array_column($slots, 'date'));

        $this->assertNotContains('2026-08-03', $dates);
        $this->assertContains('2026-09-14', $dates);
        $this->assertSame('2026-09-14', min($dates));
    }

    public function test_abidjan_reste_a_utc_plus_zero(): void
    {
        $tz = new \DateTimeZone('Africa/Abidjan');

        $this->assertSame(0, $tz->getOffset(new \DateTime('2026-01-15', $tz)));
        $this->assertSame(0, $tz->getOffset(new \DateTime('2026-07-15', $tz)));
    }

    private function regle(
        string $ouverture = '2026-09-14',
        string $fermeture = '2026-09-18',
        string $plancher = '2026-09-14',
        string $heureDebut = '08:00',
        string $heureFin = '16:00',
        int $duree = 30,
        int $capacite = 10,
        array $jours = [1, 2, 3, 4, 5],
        ?string $pauseDebut = null,
        ?string $pauseFin = null,
    ): CreneauRegle {
        return new CreneauRegle(
            ouverture: Carbon::parse($ouverture)->startOfDay(),
            fermeture: Carbon::parse($fermeture)->startOfDay(),
            plancher: Carbon::parse($plancher)->startOfDay(),
            heureDebut: $heureDebut,
            heureFin: $heureFin,
            dureeMinutes: $duree,
            capacite: $capacite,
            joursOuverts: $jours,
            pauseDebut: $pauseDebut,
            pauseFin: $pauseFin,
        );
    }
}
