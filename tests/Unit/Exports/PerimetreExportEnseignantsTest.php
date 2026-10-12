<?php

namespace Tests\Unit\Exports;

use App\Services\Enseignants\PerimetreExportEnseignants;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class PerimetreExportEnseignantsTest extends TestCase
{
    private function classes(): Collection
    {
        return collect([
            ['id' => 10, 'filiere_id' => 1, 'niveau_etude_id' => 1],
            ['id' => 11, 'filiere_id' => 2, 'niveau_etude_id' => 1],
            ['id' => 12, 'filiere_id' => 2, 'niveau_etude_id' => 2],
            ['id' => 13, 'filiere_id' => 3, 'niveau_etude_id' => 2],
        ]);
    }

    private function filieres(): Collection
    {
        return collect([
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => null],
        ]);
    }

    public function test_choix_vides_laissent_toutes_les_classes(): void
    {
        $this->assertNull(PerimetreExportEnseignants::classes($this->classes(), $this->filieres(), []));
    }

    public function test_selection_multiple_et_intersection_entre_dimensions(): void
    {
        $selection = PerimetreExportEnseignants::classes($this->classes(), $this->filieres(), [
            'filiere_ids' => [1, 3],
            'niveau_ids' => [2],
            'classe_ids' => [10, 12, 13],
        ]);
        $this->assertSame([12, 13], $selection);
    }

    public function test_filiere_mere_inclut_ses_specialites_mais_pas_autres_filieres(): void
    {
        $selection = PerimetreExportEnseignants::classes($this->classes(), $this->filieres(), [
            'filiere_ids' => [1],
        ]);
        $this->assertSame([10, 11, 12], $selection);
    }

    public function test_combinaison_sans_correspondance_reste_vide(): void
    {
        $selection = PerimetreExportEnseignants::classes($this->classes(), $this->filieres(), [
            'filiere_ids' => [3],
            'niveau_ids' => [1],
        ]);
        $this->assertSame([], $selection);
    }

    public function test_ancien_lien_classe_reste_pris_en_charge(): void
    {
        $selection = PerimetreExportEnseignants::classes($this->classes(), $this->filieres(), [
            'classe_id' => 11,
        ]);
        $this->assertSame([11], $selection);
    }
}
