<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\PlanningFilterCatalog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlanningFilterCatalogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_excludes_pure_lmd_filieres_and_keeps_mixed_bts_filieres(): void
    {
        $catalog = app(PlanningFilterCatalog::class);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        $btsNiveau = ESBTPNiveauEtude::create([
            'name' => 'BTS 1 Test',
            'code' => 'BTS1-T',
            'type' => 'BTS',
            'year' => 1,
            'is_active' => true,
        ]);

        $lmdNiveau = ESBTPNiveauEtude::create([
            'name' => 'Licence 1 Test',
            'code' => 'LIC1-T',
            'type' => 'Licence',
            'year' => 1,
            'is_active' => true,
        ]);

        $pureLmdFiliere = ESBTPFiliere::create(['name' => 'Filiere LMD Pure', 'code' => 'FLP', 'is_active' => true]);
        $mixedFiliere = ESBTPFiliere::create(['name' => 'Filiere Mixte', 'code' => 'FMX', 'is_active' => true]);
        $btsOnlyFiliere = ESBTPFiliere::create(['name' => 'Filiere BTS', 'code' => 'FBT', 'is_active' => true]);

        $domaine = ESBTPLMDDomaine::create(['name' => 'Domaine Test', 'code' => 'DOM-T', 'is_active' => true]);
        $mention = ESBTPLMDMention::create(['name' => 'Mention Test', 'code' => 'MEN-T', 'domaine_id' => $domaine->id, 'is_active' => true]);

        ESBTPLMDParcours::create([
            'name' => 'Parcours Pure LMD',
            'code' => 'PAR-LMD',
            'mention_id' => $mention->id,
            'filiere_id' => $pureLmdFiliere->id,
            'is_active' => true,
        ]);

        $mixedParcours = ESBTPLMDParcours::create([
            'name' => 'Parcours Mixte',
            'code' => 'PAR-MIX',
            'mention_id' => $mention->id,
            'filiere_id' => $mixedFiliere->id,
            'is_active' => true,
        ]);

        ESBTPClasse::create([
            'name' => 'Classe BTS Mixte',
            'code' => 'CBM',
            'filiere_id' => $mixedFiliere->id,
            'niveau_etude_id' => $btsNiveau->id,
            'annee_universitaire_id' => $annee->id,
            'is_active' => true,
        ]);

        ESBTPClasse::create([
            'name' => 'Classe BTS Pure',
            'code' => 'CBP',
            'filiere_id' => $btsOnlyFiliere->id,
            'niveau_etude_id' => $btsNiveau->id,
            'annee_universitaire_id' => $annee->id,
            'is_active' => true,
        ]);

        ESBTPClasse::create([
            'name' => 'Classe LMD Mixte',
            'code' => 'CLM',
            'filiere_id' => $mixedFiliere->id,
            'niveau_etude_id' => $lmdNiveau->id,
            'annee_universitaire_id' => $annee->id,
            'parcours_id' => $mixedParcours->id,
            'is_active' => true,
        ]);

        $visibleFilieres = $catalog->getBtsFilieres()->pluck('name');
        $visibleNiveaux = $catalog->getBtsNiveaux()->pluck('name');

        $this->assertFalse($visibleFilieres->contains('Filiere LMD Pure'));
        $this->assertTrue($visibleFilieres->contains('Filiere Mixte'));
        $this->assertTrue($visibleFilieres->contains('Filiere BTS'));
        $this->assertFalse($visibleNiveaux->contains('Licence 1 Test'));
        $this->assertTrue($visibleNiveaux->contains('BTS 1 Test'));
        $this->assertNull($catalog->normalizeBtsFiliereId($pureLmdFiliere->id));
        $this->assertSame($mixedFiliere->id, $catalog->normalizeBtsFiliereId($mixedFiliere->id));
        $this->assertNull($catalog->normalizeBtsNiveauId($lmdNiveau->id));
        $this->assertSame($btsNiveau->id, $catalog->normalizeBtsNiveauId($btsNiveau->id));
    }
}
