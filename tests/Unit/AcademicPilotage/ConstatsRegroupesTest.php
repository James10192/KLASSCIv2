<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Support\ConstatsRegroupes;
use PHPUnit\Framework\TestCase;

class ConstatsRegroupesTest extends TestCase
{
    public function test_une_cause_commune_donne_un_seul_groupe_avec_tous_les_noms(): void
    {
        $fiches = ['code' => 'grade_sheets_missing', 'severity' => 'blocking', 'message' => 'Fiches non préparées.', 'action_url' => '/pilotage'];
        $lignes = array_map(fn (string $nom) => ['student_name' => $nom, 'issues' => [$fiches]], ['A', 'B', 'C']);

        $groupes = ConstatsRegroupes::depuis($lignes);

        $this->assertCount(1, $groupes);
        $this->assertSame(3, $groupes[0]['count']);
        $this->assertSame(['A', 'B', 'C'], array_column($groupes[0]['etudiants'], 'nom'));
        $this->assertSame('/pilotage', $groupes[0]['action_url']);
    }

    public function test_les_blocages_passent_avant_les_avertissements_meme_moins_nombreux(): void
    {
        $groupes = ConstatsRegroupes::depuis([
            ['student_name' => 'A', 'warnings' => [['code' => 'grade_sheets_not_validated', 'severity' => 'warning', 'message' => 'w']]],
            ['student_name' => 'B', 'warnings' => [['code' => 'grade_sheets_not_validated', 'severity' => 'warning', 'message' => 'w']]],
            ['student_name' => 'C', 'issues' => [['code' => 'no_grade_data', 'severity' => 'blocking', 'message' => 'b']]],
        ]);

        $this->assertSame(['no_grade_data', 'grade_sheets_not_validated'], array_column($groupes, 'code'));
        $this->assertSame([1, 2], array_column($groupes, 'count'));
    }

    public function test_aucune_ligne_aucun_groupe(): void
    {
        $this->assertSame([], ConstatsRegroupes::depuis([]));
    }
}
