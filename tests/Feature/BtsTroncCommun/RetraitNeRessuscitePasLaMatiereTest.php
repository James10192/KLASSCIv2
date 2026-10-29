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
 * Le retrait vise UN couple filiere x niveau, et lui seul.
 *
 * CE FICHIER A PORTE L'ASSERTION INVERSE, ET ELLE A ETE RETIREE. Une version
 * precedente exigeait qu'une matiere retiree disparaisse aussi de l'ecran du
 * bulletin, ce qu'un detachement des deux listes plates obtenait. Ce
 * detachement decidait sur le pivot CANONIQUE pendant que le repli, lui, lit
 * le PRODUIT des deux listes plates — et l'ecran des matieres ecrit ces listes
 * sans jamais ecrire le canonique. « Plat plus large que canonique » est donc
 * l'etat NORMAL, pas le signe d'une ligne devenue inutile : le detachement
 * faisait disparaitre des matieres d'autres classes, et supprimait en dur leur
 * `coefficient` et leurs `heures_cours`, que rien ne porte ailleurs.
 *
 * Le defaut qui reste est donc assume et documente sur `retirer()` : vider
 * entierement une maquette par la croix fait reapparaitre ses matieres sur
 * l'ecran du bulletin, par le repli. Il n'est PAS asserte ici — un test vert
 * qui affirme un comportement faux le fige et le fait recopier.
 *
 * Ce qui reste garde ici est ce qui tient : la PORTEE du retrait.
 *
 * @see \App\Domain\BtsTroncCommun\LiaisonsDeMatiere::retirer()
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

    public function test_un_couple_voisin_garde_sa_matiere(): void
    {
        // La meme matiere au programme de (A, 1) ET (A, 2). Quitter (A, 2) ne
        // doit rien retirer a (A, 1). Le controle a rejouer avant de toucher
        // `retirer()` : lui enlever l'une de ses deux clauses de couple. Ce
        // test doit alors virer au rouge.
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

        $this->assertSame(1, $retire['canonique'], 'Une seule ligne canonique doit partir : celle du couple demande.');

        $this->assertContains('Topographie', $this->nomsSurLEcranDuBulletin($classe1),
            'Le couple voisin a perdu sa matiere : le retrait a depasse son couple.');
    }
}
