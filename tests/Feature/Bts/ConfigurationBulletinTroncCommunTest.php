<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Services\BulletinInlineConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La fenêtre « Configuration requise du bulletin » listait les matières
 * classées « spécialité » d'une classe de tronc commun : l'école y remplissait
 * coefficients et professeurs pour des matières que le bulletin de tronc commun
 * n'affiche pas. Elle suit désormais la même règle que la maquette.
 */
class ConfigurationBulletinTroncCommunTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_matiere_de_specialite_n_est_pas_proposee_sur_le_bulletin_de_tronc_commun(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $tc->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $lier = function (string $nom, ?string $classification) use ($tc, $niveau) {
            $matiere = ESBTPMatiere::factory()->create(['name' => $nom, 'unite_enseignement_id' => null, 'is_active' => true]);
            ESBTPMatiereFilierNiveau::create([
                'matiere_id' => $matiere->id,
                'filiere_id' => $tc->id,
                'niveau_etude_id' => $niveau->id,
                'classification' => $classification,
            ]);

            return $matiere;
        };

        $maths = $lier('Mathematiques', ESBTPMatiereFilierNiveau::TRONC_COMMUN);
        $nonClassee = $lier('Physique', null);
        $securite = $lier('Securite', ESBTPMatiereFilierNiveau::SPECIALITE);

        $ids = collect(app(BulletinInlineConfigurationService::class)->data($classe->id, $annee->id, 'semestre1')['matieres'])
            ->pluck('id')
            ->all();

        $this->assertContains($maths->id, $ids);
        $this->assertContains($nonClassee->id, $ids, 'Une matière non classée reste proposée, comme au bulletin.');
        $this->assertNotContains($securite->id, $ids);
    }
}
