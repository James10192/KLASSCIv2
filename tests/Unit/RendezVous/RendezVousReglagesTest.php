<?php

namespace Tests\Unit\RendezVous;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\RendezVous\RendezVousReglages;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RendezVousReglagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_un_reglage_manquant_est_nomme(): void
    {
        $this->poser([
            RendezVousReglages::OUVERTURE => '2026-09-12',
            RendezVousReglages::FERMETURE => '2026-09-30',
            PortailCandidaturePublication::REGLAGE_PHYSIQUES => '2026-09-12',
            RendezVousReglages::HEURE_DEBUT => '08:00',
            RendezVousReglages::HEURE_FIN => '16:00',
            RendezVousReglages::DUREE => '30',
            RendezVousReglages::CAPACITE => '10',
            RendezVousReglages::JOURS => '',
            RendezVousReglages::PAUSE_DEBUT => '',
            RendezVousReglages::PAUSE_FIN => '',
        ]);

        try {
            app(RendezVousReglages::class)->pourGeneration();
            $this->fail('Devait refuser sans jours ouverts.');
        } catch (ReglagesRdvIncomplets $e) {
            $this->assertContains(RendezVousReglages::JOURS, $e->cles);
        }
    }

    public function test_le_plancher_physique_manquant_est_nomme(): void
    {
        $this->poserComplet([PortailCandidaturePublication::REGLAGE_PHYSIQUES => '']);

        try {
            app(RendezVousReglages::class)->pourGeneration();
            $this->fail('Devait refuser sans date de guichet.');
        } catch (ReglagesRdvIncomplets $e) {
            $this->assertContains(PortailCandidaturePublication::REGLAGE_PHYSIQUES, $e->cles);
        }
    }

    public function test_une_pause_sans_fin_est_nommee(): void
    {
        $this->poserComplet([RendezVousReglages::PAUSE_DEBUT => '12:00']);

        try {
            app(RendezVousReglages::class)->pourGeneration();
            $this->fail('Devait refuser une pause incomplete.');
        } catch (ReglagesRdvIncomplets $e) {
            $this->assertContains(RendezVousReglages::PAUSE_FIN, $e->cles);
        }
    }

    public function test_le_debit_journalier_est_calcule_en_clair(): void
    {
        $this->poserComplet();

        $this->assertSame(
            [
                'duree' => 30,
                'capacite' => 10,
                'minutes_utiles' => 480,
                'creneaux_par_jour' => 16,
                'personnes_par_jour' => 160,
            ],
            app(RendezVousReglages::class)->debitJournalier()
        );
    }

    public function test_la_pause_reduit_le_debit(): void
    {
        $this->poserComplet([
            RendezVousReglages::PAUSE_DEBUT => '12:00',
            RendezVousReglages::PAUSE_FIN => '13:00',
        ]);

        $debit = app(RendezVousReglages::class)->debitJournalier();

        $this->assertSame(420, $debit['minutes_utiles']);
        $this->assertSame(14, $debit['creneaux_par_jour']);
        $this->assertSame(140, $debit['personnes_par_jour']);
    }

    /**
     * @param  array<string, string>  $valeurs
     */
    private function poserComplet(array $valeurs = []): void
    {
        $this->poser(array_merge([
            RendezVousReglages::OUVERTURE => '2026-09-12',
            RendezVousReglages::FERMETURE => '2026-09-30',
            PortailCandidaturePublication::REGLAGE_PHYSIQUES => '2026-09-12',
            RendezVousReglages::HEURE_DEBUT => '08:00',
            RendezVousReglages::HEURE_FIN => '16:00',
            RendezVousReglages::DUREE => '30',
            RendezVousReglages::CAPACITE => '10',
            RendezVousReglages::JOURS => '1,2,3,4,5',
            RendezVousReglages::PAUSE_DEBUT => '',
            RendezVousReglages::PAUSE_FIN => '',
        ], $valeurs));
    }

    /**
     * @param  array<string, string>  $valeurs
     */
    private function poser(array $valeurs): void
    {
        foreach ($valeurs as $cle => $valeur) {
            Cache::put('setting_'.$cle, $valeur, 60);
        }
    }
}
