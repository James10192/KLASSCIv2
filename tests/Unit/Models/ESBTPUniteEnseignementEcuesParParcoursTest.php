<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Lecture des éléments constitutifs d'une unité, maquette par maquette.
 *
 * Une unité est partagée entre parcours ; ce qui diffère, c'est la liste des
 * éléments qu'on y suit. Ces tests portent sur les deux façons dont cette
 * lecture peut mentir SANS lever la moindre erreur :
 *
 *  - laisser fuir dans une maquette un élément réservé à l'autre — c'est un
 *    test d'ABSENCE, le seul qui attrape la panne ;
 *  - rendre deux fois le même élément quand il porte à la fois un lien commun
 *    et un lien réservé — sa note serait alors comptée deux fois dans la
 *    moyenne de l'unité, et son crédit deux fois dans le total.
 *
 * Aucune base : les relations sont injectées en mémoire par setRelation(). On
 * hérite tout de même de Tests\TestCase parce que le modèle est audité, et que
 * le trait Auditable lit la configuration dès l'instanciation.
 */
class ESBTPUniteEnseignementEcuesParParcoursTest extends TestCase
{
    private const PARCOURS_BATIMENT = 7;
    private const PARCOURS_TRAVAUX_PUBLICS = 9;

    /**
     * Sans cette colonne exposée, `$ecue->pivot->parcours_id` rend null en
     * silence et tout élément réservé se relit comme commun.
     */
    public function test_la_relation_expose_le_parcours_du_lien(): void
    {
        $colonnes = (new ESBTPUniteEnseignement())->ecues()->getPivotColumns();

        $this->assertContains('parcours_id', $colonnes);
    }

    public function test_sans_maquette_demandee_rien_n_est_filtre(): void
    {
        $ue = $this->uniteAvecLiens([
            $this->element(1, 'Résistance des matériaux', self::PARCOURS_BATIMENT),
            $this->element(2, 'Topographie', self::PARCOURS_TRAVAUX_PUBLICS),
            $this->element(3, 'Mathématiques', ESBTPUniteEnseignement::PARCOURS_COMMUN),
        ]);

        $this->assertSame([1, 2, 3], $this->idsDe($ue->getEcuesEffectifs()));
    }

    public function test_une_maquette_ne_voit_que_le_commun_et_ce_qui_lui_est_reserve(): void
    {
        $ue = $this->uniteAvecLiens([
            $this->element(1, 'Résistance des matériaux', self::PARCOURS_BATIMENT),
            $this->element(2, 'Topographie', self::PARCOURS_TRAVAUX_PUBLICS),
            $this->element(3, 'Mathématiques', ESBTPUniteEnseignement::PARCOURS_COMMUN),
        ]);

        $vusParBatiment = $this->idsDe($ue->getEcuesEffectifs(self::PARCOURS_BATIMENT));

        $this->assertSame([1, 3], $vusParBatiment);
        // Le test qui compte : l'élément de l'autre maquette ne fuit pas.
        $this->assertNotContains(2, $vusParBatiment);
    }

    public function test_un_lien_sans_parcours_vaut_commun(): void
    {
        // Rétro-compatibilité : les lignes écrites avant la colonne, et tout
        // pivot hydraté sans elle, doivent rester visibles de toutes les
        // maquettes — sinon une unité entière disparaîtrait du bulletin.
        $sansParcours = new ESBTPMatiere();
        $sansParcours->forceFill(['id' => 42, 'name' => 'Anglais', 'is_active' => true]);
        $sansParcours->setRelation('pivot', new Pivot([]));

        $ue = $this->uniteAvecLiens([$sansParcours]);

        $this->assertSame([42], $this->idsDe($ue->getEcuesEffectifs(self::PARCOURS_BATIMENT)));
    }

    public function test_un_element_a_la_fois_commun_et_reserve_ne_compte_qu_une_fois(): void
    {
        $ue = $this->uniteAvecLiens([
            $this->element(5, 'Béton armé', ESBTPUniteEnseignement::PARCOURS_COMMUN, creditEcue: 3),
            $this->element(5, 'Béton armé', self::PARCOURS_BATIMENT, creditEcue: 4),
        ]);

        $lus = $ue->getEcuesEffectifs(self::PARCOURS_BATIMENT);

        $this->assertCount(1, $lus);
        // La ligne la plus précise gagne : c'est celle que quelqu'un a posée
        // pour cette maquette, avec son propre crédit.
        $this->assertSame(4, (int) $lus->first()->pivot->credit_ecue);
    }

    public function test_hors_maquette_le_doublon_est_tranche_sur_le_lien_commun(): void
    {
        $ue = $this->uniteAvecLiens([
            $this->element(5, 'Béton armé', self::PARCOURS_BATIMENT, creditEcue: 4),
            $this->element(5, 'Béton armé', ESBTPUniteEnseignement::PARCOURS_COMMUN, creditEcue: 3),
        ]);

        $lus = $ue->getEcuesEffectifs();

        $this->assertCount(1, $lus);
        // Hors contexte de parcours, le choix neutre — et surtout stable d'une
        // lecture à l'autre, quel que soit l'ordre des lignes.
        $this->assertSame(3, (int) $lus->first()->pivot->credit_ecue);
    }

    public function test_le_repli_par_cle_etrangere_reste_visible_de_toutes_les_maquettes(): void
    {
        // L'import de maquettes n'écrit que la clé étrangère. Sans ce repli,
        // tout élément importé existerait en base sans jamais apparaître.
        $parCle = new ESBTPMatiere();
        $parCle->forceFill(['id' => 11, 'name' => 'Hydraulique', 'is_active' => true]);

        $inactif = new ESBTPMatiere();
        $inactif->forceFill(['id' => 12, 'name' => 'Option retirée', 'is_active' => false]);

        $ue = $this->uniteAvecLiens(
            [$this->element(3, 'Mathématiques', ESBTPUniteEnseignement::PARCOURS_COMMUN)],
            [$parCle, $inactif]
        );

        $this->assertSame([3, 11], $this->idsDe($ue->getEcuesEffectifs(self::PARCOURS_BATIMENT)));
    }

    public function test_le_credit_de_la_maquette_prime_sur_celui_de_l_unite(): void
    {
        $ue = new ESBTPUniteEnseignement();
        $ue->forceFill(['id' => 100, 'credit' => 6]);

        $this->assertSame(6, $ue->creditEffectif());

        $ue->creditParcours = 4;
        $this->assertSame(4, $ue->creditEffectif());
    }

    /**
     * @param  array<int, ESBTPMatiere>  $liensPivot
     * @param  array<int, ESBTPMatiere>  $parCleEtrangere
     */
    private function uniteAvecLiens(array $liensPivot, array $parCleEtrangere = []): ESBTPUniteEnseignement
    {
        $ue = new ESBTPUniteEnseignement();
        $ue->forceFill(['id' => 100, 'credit' => 6]);
        $ue->setRelation('ecues', new Collection($liensPivot));
        $ue->setRelation('matieres', new Collection($parCleEtrangere));

        return $ue;
    }

    private function element(int $id, string $nom, int $parcoursId, int $creditEcue = 2): ESBTPMatiere
    {
        $matiere = new ESBTPMatiere();
        $matiere->forceFill(['id' => $id, 'name' => $nom, 'is_active' => true]);
        $matiere->setRelation('pivot', new Pivot([
            'parcours_id' => $parcoursId,
            'credit_ecue' => $creditEcue,
            'coefficient_ecue' => 1,
            'ordre_bulletin' => 0,
        ]));

        return $matiere;
    }

    /** @return array<int, int> */
    private function idsDe(Collection $matieres): array
    {
        return $matieres->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
