<?php

namespace Tests\Feature\BtsTroncCommun;

use App\Domain\BtsTroncCommun\LiaisonsDeMatiere;
use App\Domain\BtsTroncCommun\ResolutionDeMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La maquette BTS refuse une ECUE à l'ENTRÉE, et l'accepte à la SORTIE.
 *
 * L'asymétrie est le sujet même de ce test, et elle n'est pas un oubli.
 *
 * Le chantier qui a découvert la fuite a d'abord filtré chez les lecteurs :
 * douze filtres dans neuf fichiers, trouvés en trois passes de revue, et il en
 * restait encore. Filtrer en lecture demande à chaque futur écran de s'en
 * souvenir. Refuser à l'écriture ne le demande qu'une fois — c'est ce que garde
 * le premier test.
 *
 * Mais refuser des deux côtés est précisément ce qui avait rendu le défaut
 * incorrigible : `TPOH243` × (TRAVAUX_PUBLICS, 2A) était listée par le CLI,
 * masquée par l'écran de classification, et refusée au retrait. Visible, et
 * retirable par rien. C'est ce que garde le second test.
 *
 * @see \App\Domain\BtsTroncCommun\LiaisonsDeMatiere::poser()
 * @see \App\Domain\BtsTroncCommun\ResolutionDeMatiere::matiere()
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 */
class MaquetteBtsRefuseUneEcueTest extends TestCase
{
    use RefreshDatabase;

    private function ecue(string $nom = 'Alimentation en eau et QTE'): ESBTPMatiere
    {
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE Ouvrages hydrauliques',
            'code' => 'UE-TPOH24-'.uniqid(),
        ]);

        return ESBTPMatiere::factory()->create([
            'name' => $nom,
            'code' => 'TPOH243',
            'is_active' => true,
            'unite_enseignement_id' => $ue->id,
        ]);
    }

    public function test_poser_refuse_une_ecue_et_n_ecrit_rien(): void
    {
        $ecue = $this->ecue();
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        $this->expectException(\InvalidArgumentException::class);

        try {
            app(LiaisonsDeMatiere::class)->poser((int) $ecue->id, (int) $filiere->id, (int) $niveau->id);
        } finally {
            // Le refus doit être TOTAL : une ligne canonique écrite puis
            // l'exception levée laisserait exactement la ligne qu'on refuse.
            $this->assertSame(0, ESBTPMatiereFilierNiveau::query()
                ->where('matiere_id', $ecue->id)
                ->count());
        }
    }

    public function test_poser_accepte_une_matiere_bts(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['name' => 'Topographie', 'is_active' => true]);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        app(LiaisonsDeMatiere::class)->poser((int) $matiere->id, (int) $filiere->id, (int) $niveau->id);

        $this->assertSame(1, ESBTPMatiereFilierNiveau::query()
            ->where('matiere_id', $matiere->id)
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->count());
    }

    public function test_la_resolution_refuse_une_ecue_au_chargement_et_l_accepte_au_retrait(): void
    {
        $ecue = $this->ecue();
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        $resolution = app(ResolutionDeMatiere::class);

        // Chargement : elle n'existe pas, pour que rien ne l'écrive.
        $auChargement = $resolution->matiere(
            ['id' => (int) $ecue->id],
            (int) $filiere->id,
            (int) $niveau->id,
        );
        $this->assertSame('introuvable', $auChargement['statut']);

        // Retrait : elle existe, pour qu'on puisse l'enlever.
        $auRetrait = $resolution->matiere(
            ['id' => (int) $ecue->id],
            (int) $filiere->id,
            (int) $niveau->id,
            pourRetrait: true,
        );
        $this->assertSame('ok', $auRetrait['statut']);
        $this->assertSame((int) $ecue->id, (int) $auRetrait['matiere']->id);
    }

    public function test_le_retrait_par_libelle_retrouve_aussi_une_ecue(): void
    {
        $ecue = $this->ecue();
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        // Le garde n'existait d'abord que sur le libellé, et l'identifiant
        // passait à côté ; en levant la garde pour le retrait, il faut la lever
        // des DEUX côtés, sinon le retrait par nom reste impossible.
        $resolue = app(ResolutionDeMatiere::class)->matiere(
            $ecue->name,
            (int) $filiere->id,
            (int) $niveau->id,
            pourRetrait: true,
        );

        $this->assertSame('ok', $resolue['statut']);
        $this->assertSame((int) $ecue->id, (int) $resolue['matiere']->id);
    }
}
