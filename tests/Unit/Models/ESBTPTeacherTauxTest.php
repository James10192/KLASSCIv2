<?php

namespace Tests\Unit\Models;

use App\Enums\TypeSeance;
use App\Models\ESBTPEnseignantTauxSeance;
use App\Models\ESBTPTeacher;
use Tests\TestCase;

/**
 * Cascade des taux par type de séance (sans DB).
 *
 * Règle métier (grill juin 2026) : taux spécifique du type → taux par défaut
 * (taux_horaire) → 0. Voir ESBTPTeacher::tauxPour() / tauxParTypeMap().
 */
class ESBTPTeacherTauxTest extends TestCase
{
    private function makeTeacher(?float $tauxDefaut, array $tauxParType = []): ESBTPTeacher
    {
        $teacher = new ESBTPTeacher();
        $teacher->taux_horaire = $tauxDefaut;

        $rows = collect();
        foreach ($tauxParType as $type => $taux) {
            $row = new ESBTPEnseignantTauxSeance();
            $row->type_seance = $type;   // casté vers TypeSeance
            $row->taux_horaire = $taux;
            $rows->push($row);
        }
        $teacher->setRelation('tauxSeances', $rows);

        return $teacher;
    }

    public function test_taux_specifique_prime_sur_defaut(): void
    {
        $teacher = $this->makeTeacher(5000, ['CM' => 8000]);

        $this->assertSame(8000.0, $teacher->tauxPour(TypeSeance::CM));
    }

    public function test_fallback_sur_taux_defaut_quand_pas_de_ligne(): void
    {
        $teacher = $this->makeTeacher(5000, ['CM' => 8000]);

        // TD/TP n'ont pas de taux spécifique → fallback sur le taux par défaut.
        $this->assertSame(5000.0, $teacher->tauxPour(TypeSeance::TD));
        $this->assertSame(5000.0, $teacher->tauxPour(TypeSeance::TP));
    }

    public function test_zero_quand_aucun_taux(): void
    {
        $teacher = $this->makeTeacher(null);

        $this->assertSame(0.0, $teacher->tauxPour(TypeSeance::CM));
    }

    public function test_accepte_valeur_string_de_type(): void
    {
        $teacher = $this->makeTeacher(5000, ['TP' => 6500]);

        $this->assertSame(6500.0, $teacher->tauxPour('TP'));
    }

    public function test_map_par_type_retourne_les_lignes_configurees(): void
    {
        $teacher = $this->makeTeacher(5000, ['CM' => 8000, 'TD' => 7000]);

        $map = $teacher->tauxParTypeMap();

        $this->assertSame(8000.0, $map['CM']);
        $this->assertSame(7000.0, $map['TD']);
        $this->assertArrayNotHasKey('TP', $map);
    }
}
