<?php

namespace Tests\Feature\Bulletin;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une ECUE LMD ne sort jamais sur un bulletin BTS, quel que soit le pivot par
 * lequel elle s'y est glissée.
 *
 * Cas réel (esbtp-abidjan, septembre 2026) : « Alimentation en eau et QTE »
 * (TPOH243), importée avec les maquettes Génie Civil, portait une ligne dans le
 * pivot canonique BTS sur (TRAVAUX_PUBLICS, 2A). Elle s'imprimait donc sur les
 * bulletins de Travaux Publics 2ᵉ année, tout en restant **introuvable** depuis
 * `/esbtp/matieres` et depuis l'écran de classification — les deux écrans qui
 * auraient permis de l'en retirer filtrent déjà les ECUE. C'est ce qui rendait
 * ce défaut coûteux, pas la matière en trop.
 *
 * Cette dernière phrase est au PASSÉ depuis la correction, et il faut le dire
 * ici sous peine de laisser un énoncé faux dans un fichier qu'on relit en
 * confiance : l'écran de classification affiche désormais ces lignes dans un
 * bloc « éléments LMD dans cette maquette BTS », avec leur croix de retrait, et
 * la résolution du retrait les accepte. Voir
 * `MaquetteBtsRefuseUneEcueTest`, qui garde cette asymétrie entrée/sortie.
 *
 * Ce que ce test garde : les deux branches de lecture du résolveur, parce que
 * corriger la branche canonique seule laissait la porte du repli grande ouverte.
 *
 * @see \App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 */
class EcueLmdHorsBulletinBtsTest extends TestCase
{
    use RefreshDatabase;

    private function ecue(string $nom): ESBTPMatiere
    {
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE Ouvrages hydrauliques',
            'code' => 'UE-TPOH24-'.uniqid(),
        ]);

        return ESBTPMatiere::factory()->create([
            'name' => $nom,
            'is_active' => true,
            'unite_enseignement_id' => $ue->id,
        ]);
    }

    public function test_une_ecue_liee_au_pivot_canonique_ne_sort_pas_au_bulletin(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $matiereBts = ESBTPMatiere::factory()->create(['name' => 'Topographie', 'is_active' => true]);
        $ecue = $this->ecue('Alimentation en eau et QTE');

        foreach ([$matiereBts, $ecue] as $matiere) {
            ESBTPMatiereFilierNiveau::create([
                'matiere_id' => $matiere->id,
                'filiere_id' => $filiere->id,
                'niveau_etude_id' => $niveau->id,
                'classification' => null,
            ]);
        }

        $ids = app(BtsBulletinSubjectResolver::class)->subjectsForClasse($classe)->pluck('id')->all();

        $this->assertContains($matiereBts->id, $ids, 'La matière BTS doit rester au bulletin.');
        $this->assertNotContains($ecue->id, $ids, "L'ECUE LMD ne doit pas sortir sur un bulletin BTS.");
    }

    public function test_une_ecue_du_pivot_legacy_ne_sort_pas_non_plus(): void
    {
        // Aucune ligne dans le pivot canonique : le résolveur retombe sur
        // `esbtp_classe_matiere`, la voie des classes BTS historiques.
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $matiereBts = ESBTPMatiere::factory()->create(['name' => 'Béton armé', 'is_active' => true]);
        $ecue = $this->ecue('Aménagement hydraulique');

        $classe->matieres()->attach([$matiereBts->id, $ecue->id]);

        $ids = app(BtsBulletinSubjectResolver::class)->subjectsForClasse($classe)->pluck('id')->all();

        $this->assertContains($matiereBts->id, $ids, 'Le repli legacy doit garder les matières BTS.');
        $this->assertNotContains($ecue->id, $ids, "Le repli legacy ne doit pas laisser passer une ECUE.");
    }
}
