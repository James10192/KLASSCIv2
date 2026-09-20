<?php

namespace Tests\Feature\BtsTroncCommun;

use App\Domain\BtsTroncCommun\LiaisonsDeMatiere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Services\BulletinInlineConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Retirer une matière de la maquette la retire AUSSI de l'écran du bulletin.
 *
 * Le trou que ce test garde n'est pas une dette ancienne : c'est ce chantier
 * qui l'a creusé, en donnant pour la première fois un geste de retrait à portée
 * de clic. `LiaisonsDeMatiere::retirer()` ne touchait volontairement pas aux
 * deux listes plates, et `BulletinInlineConfigurationService` retombe sur leur
 * PRODUIT dès qu'un couple n'a plus aucune ligne canonique. Vider entièrement
 * une maquette par la croix faisait donc réapparaître, sur l'écran qui décide
 * du contenu du bulletin, exactement ce qu'on venait d'en retirer.
 *
 * C'est le zéro confondu avec l'absence (`rien-en-dur.md`) : après un retrait
 * explicite, « vide » est une DÉCISION. Le repli n'a de sens que pour un couple
 * que personne n'a jamais renseigné.
 *
 * LE SECOND TEST EST LE PLUS IMPORTANT DES DEUX. Détacher sans condition serait
 * l'erreur inverse, et le docblock de `retirer()` la nommait déjà : les listes
 * plates ne savent pas de quel couple vient une filière. La condition est donc
 * étroite — on ne détache que ce qu'aucun couple canonique ne réclame plus.
 *
 * @see \App\Domain\BtsTroncCommun\LiaisonsDeMatiere::retirer()
 * @see \App\Services\BulletinInlineConfigurationService
 */
class RetraitNeRessuscitePasLaMatiereTest extends TestCase
{
    use RefreshDatabase;

    private function nomsSurLEcranDuBulletin(ESBTPClasse $classe): array
    {
        $data = app(BulletinInlineConfigurationService::class)
            ->data($classe->id, 1, 'semestre1');

        return collect($data['matieres'] ?? [])->pluck('name')->all();
    }

    public function test_une_matiere_retiree_ne_revient_pas_par_les_listes_plates(): void
    {
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create(['name' => 'Beton arme', 'is_active' => true]);

        $liaisons = app(LiaisonsDeMatiere::class);
        $liaisons->poser($matiere->id, $filiere->id, $niveau->id);

        $this->assertContains('Beton arme', $this->nomsSurLEcranDuBulletin($classe),
            'Temoin de montage : sans elle au depart, le test ne mesure rien.');

        $liaisons->retirer($matiere->id, $filiere->id, $niveau->id);

        $this->assertNotContains('Beton arme', $this->nomsSurLEcranDuBulletin($classe),
            'La matiere retiree est revenue par le produit des deux listes plates.');
    }

    public function test_un_couple_voisin_garde_sa_filiere_et_son_niveau(): void
    {
        // La même matière au programme de (A, 1) ET (A, 2). Quitter (A, 2) ne
        // doit rien retirer à (A, 1) — c'est l'objection que le docblock de
        // `retirer()` oppose depuis toujours à un détachement inconditionnel.
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau1 = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $niveau2 = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $classe1 = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau1->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create(['name' => 'Topographie', 'is_active' => true]);

        $liaisons = app(LiaisonsDeMatiere::class);
        $liaisons->poser($matiere->id, $filiere->id, $niveau1->id);
        $liaisons->poser($matiere->id, $filiere->id, $niveau2->id);

        $retire = $liaisons->retirer($matiere->id, $filiere->id, $niveau2->id);

        $this->assertFalse($retire['pivots_plats']['filiere'],
            'La filiere sert encore au couple (filiere, niveau1) : elle ne doit pas etre detachee.');
        $this->assertTrue($retire['pivots_plats']['niveau'],
            'Le niveau 2 ne sert plus a aucun couple canonique.');

        $this->assertContains('Topographie', $this->nomsSurLEcranDuBulletin($classe1),
            'Le couple voisin a perdu sa matiere : le detachement a ete trop large.');
    }
}
