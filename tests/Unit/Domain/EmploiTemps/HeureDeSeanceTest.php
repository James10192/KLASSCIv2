<?php

namespace Tests\Unit\Domain\EmploiTemps;

use App\Domain\EmploiTemps\HeureDeSeance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * La mise en forme d'une heure de séance, les trois types et le rattrapage.
 *
 * `Tests\TestCase` et non celui de PHPUnit, uniquement pour la façade `Log` :
 * la classe elle-même ne touche ni la base ni le conteneur.
 *
 * Le cas qui compte est le dernier. `optional($valeur)->format('H:i')` avait été
 * posé à la place de cette classe ; il rend `null` sur une chaîne sans un mot,
 * et ces `null` entraient dans une clé de déduplication où ils repliaient deux
 * conflits distincts en une seule ligne d'écran. Un rattrapage qui dégrade
 * l'affichage doit se journaliser — piège #12 de `klassci-debugging-discipline.md`.
 */
class HeureDeSeanceTest extends TestCase
{
    public function test_un_carbon_rend_l_heure_et_non_la_date(): void
    {
        // Le cas d'ESBTPSeanceCours : l'accesseur rend un Carbon daté du jour.
        $this->assertSame('08:00', HeureDeSeance::hi(Carbon::parse('2026-09-17 08:00:00')));
    }

    public function test_une_chaine_est_coupee_a_cinq_caracteres(): void
    {
        // Le cas d'ESBTPAttendance : la colonne rend sa chaîne brute.
        $this->assertSame('08:00', HeureDeSeance::hi('08:00:00'));
        $this->assertSame('08:00', HeureDeSeance::hi('08:00'));
    }

    public function test_une_heure_absente_rend_null_sans_bruit(): void
    {
        Log::shouldReceive('warning')->never();

        $this->assertNull(HeureDeSeance::hi(null));
        $this->assertNull(HeureDeSeance::hi(''));
    }

    public function test_un_type_illisible_rend_null_ET_le_journalise(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message, $contexte) => str_contains($message, 'HeureDeSeance')
                && ($contexte['type'] ?? null) === 'array');

        $this->assertNull(HeureDeSeance::hi(['08:00']));
    }
}
