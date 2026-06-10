<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPFiliere;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unit pour ESBTPFiliere::troncCommunUnionFiliereIds() (C9 — Plan C BTS).
 *
 * Le helper renvoie :
 *  - [id] pour une filière sans parent (ou dont le parent n'est pas un TC)
 *  - [id, parent_id] lorsque le parent est un véritable tronc commun
 *
 * Pas de DB nécessaire — on construit les modèles en mémoire et on injecte la
 * relation parent via setRelation().
 */
class ESBTPFiliereUnionTest extends TestCase
{
    private function makeFiliere(array $attributes): ESBTPFiliere
    {
        $filiere = new ESBTPFiliere();
        $filiere->forceFill($attributes);

        return $filiere;
    }

    public function test_filiere_sans_parent_retourne_uniquement_son_id(): void
    {
        $filiere = $this->makeFiliere([
            'id' => 10,
            'parent_id' => null,
            'is_tronc_commun' => false,
        ]);

        $this->assertSame([10], $filiere->troncCommunUnionFiliereIds());
    }

    public function test_filiere_tronc_commun_pure_retourne_uniquement_son_id(): void
    {
        $tc = $this->makeFiliere([
            'id' => 5,
            'parent_id' => null,
            'is_tronc_commun' => true,
        ]);

        $this->assertSame([5], $tc->troncCommunUnionFiliereIds());
    }

    public function test_specialite_avec_parent_tronc_commun_retourne_union(): void
    {
        $tcParent = $this->makeFiliere([
            'id' => 5,
            'parent_id' => null,
            'is_tronc_commun' => true,
        ]);

        $specialite = $this->makeFiliere([
            'id' => 20,
            'parent_id' => 5,
            'is_tronc_commun' => false,
        ]);
        $specialite->setRelation('parent', $tcParent);

        $this->assertSame([20, 5], $specialite->troncCommunUnionFiliereIds());
    }

    public function test_specialite_dont_parent_non_tc_ne_remonte_pas(): void
    {
        // Parent existe mais n'est PAS un tronc commun (filière mère ordinaire).
        $parentOrdinaire = $this->makeFiliere([
            'id' => 7,
            'parent_id' => null,
            'is_tronc_commun' => false,
        ]);

        $specialite = $this->makeFiliere([
            'id' => 21,
            'parent_id' => 7,
            'is_tronc_commun' => false,
        ]);
        $specialite->setRelation('parent', $parentOrdinaire);

        $this->assertSame([21], $specialite->troncCommunUnionFiliereIds());
    }

    public function test_specialite_avec_parent_id_mais_parent_absent_ne_remonte_pas(): void
    {
        // parent_id défini mais relation parent non résolue (null) → pas d'union.
        $specialite = $this->makeFiliere([
            'id' => 22,
            'parent_id' => 7,
            'is_tronc_commun' => false,
        ]);
        $specialite->setRelation('parent', null);

        $this->assertSame([22], $specialite->troncCommunUnionFiliereIds());
    }
}
