<?php

namespace Tests\Feature\Bulletin;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie l'exclusion des matières classées « specialite » des bulletins de tronc
 * commun, sans casser les bulletins de spécialité (héritage TC). BTS uniquement.
 *
 * @see \App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver
 */
class BtsBulletinSubjectClassificationTest extends TestCase
{
    use RefreshDatabase;

    private function link(ESBTPMatiere $matiere, ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, ?string $classification): void
    {
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'classification' => $classification,
        ]);
    }

    public function test_bulletin_tc_exclut_specialite_et_garde_null_et_tronc_commun(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $classeTc = ESBTPClasse::factory()->create([
            'filiere_id' => $tc->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $mCommun = ESBTPMatiere::factory()->create(['name' => 'Mathematiques', 'is_active' => true]);
        $mNull = ESBTPMatiere::factory()->create(['name' => 'Francais', 'is_active' => true]);
        $mSpe = ESBTPMatiere::factory()->create(['name' => 'Securite', 'is_active' => true]);

        $this->link($mCommun, $tc, $niveau, ESBTPMatiereFilierNiveau::TRONC_COMMUN);
        $this->link($mNull, $tc, $niveau, null);
        $this->link($mSpe, $tc, $niveau, ESBTPMatiereFilierNiveau::SPECIALITE);

        $ids = app(BtsBulletinSubjectResolver::class)->subjectsForClasse($classeTc)->pluck('id')->all();

        $this->assertContains($mCommun->id, $ids, 'La matière tronc commun doit apparaître.');
        $this->assertContains($mNull->id, $ids, 'La matière non classée (null) doit apparaître (non-régressif).');
        $this->assertNotContains($mSpe->id, $ids, 'La matière classée specialite doit être exclue du bulletin TC.');
    }

    public function test_bulletin_specialite_garde_ses_matieres_et_herite_du_tc(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $spe = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => $tc->id]);
        $classeSpe = ESBTPClasse::factory()->create([
            'filiere_id' => $spe->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $mCommun = ESBTPMatiere::factory()->create(['name' => 'Mathematiques', 'is_active' => true]);
        $mSpeSurComboTc = ESBTPMatiere::factory()->create(['name' => 'Securite', 'is_active' => true]);
        $mSpePropre = ESBTPMatiere::factory()->create(['name' => 'Resistance', 'is_active' => true]);

        // Combo TC parent : matière commune + une matière flaggée specialite (pollution).
        $this->link($mCommun, $tc, $niveau, ESBTPMatiereFilierNiveau::TRONC_COMMUN);
        $this->link($mSpeSurComboTc, $tc, $niveau, ESBTPMatiereFilierNiveau::SPECIALITE);
        // Combo spécialité : sa matière propre (non classée).
        $this->link($mSpePropre, $spe, $niveau, null);

        $ids = app(BtsBulletinSubjectResolver::class)->subjectsForClasse($classeSpe)->pluck('id')->all();

        $this->assertContains($mSpePropre->id, $ids, 'La matière de spécialité propre doit apparaître.');
        $this->assertContains($mCommun->id, $ids, 'La matière commune héritée du TC doit apparaître.');
        $this->assertNotContains($mSpeSurComboTc->id, $ids, 'La matière specialite flaggée sur le combo TC ne doit pas être héritée.');
    }
}
