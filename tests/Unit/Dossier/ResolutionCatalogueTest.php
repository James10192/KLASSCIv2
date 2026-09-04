<?php

namespace Tests\Unit\Dossier;

use App\Models\ESBTPPieceDossier;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Le catalogue varie par filiere ET par niveau, avec un defaut commun a l'ecole.
 * Ces tests fixent la regle de resolution : pour un meme code, la ligne la plus
 * precise remplace les autres. Sans base : c'est de l'arithmetique sur une
 * collection, pas une requete.
 */
class ResolutionCatalogueTest extends TestCase
{
    private function piece(array $attributs): ESBTPPieceDossier
    {
        $piece = new ESBTPPieceDossier();
        $piece->forceFill($attributs + [
            'id' => 1,
            'code' => 'extrait_naissance',
            'libelle' => 'Extrait de naissance',
            'filiere_id' => null,
            'niveau_id' => null,
            'ordre' => 0,
            'is_obligatoire' => true,
            'is_active' => true,
        ]);

        return $piece;
    }

    public function test_le_defaut_de_l_ecole_s_applique_quand_rien_de_plus_precis_n_existe(): void
    {
        $resolu = ESBTPPieceDossier::resoudreParCode(new Collection([
            $this->piece(['id' => 1, 'libelle' => 'Defaut ecole']),
        ]));

        $this->assertCount(1, $resolu);
        $this->assertSame('Defaut ecole', $resolu->first()->libelle);
    }

    public function test_la_ligne_filiere_plus_niveau_remplace_toutes_les_autres(): void
    {
        $resolu = ESBTPPieceDossier::resoudreParCode(new Collection([
            $this->piece(['id' => 1, 'libelle' => 'Defaut ecole']),
            $this->piece(['id' => 2, 'libelle' => 'Filiere seule', 'filiere_id' => 7]),
            $this->piece(['id' => 3, 'libelle' => 'Niveau seul', 'niveau_id' => 3]),
            $this->piece(['id' => 4, 'libelle' => 'Filiere + niveau', 'filiere_id' => 7, 'niveau_id' => 3]),
        ]));

        $this->assertCount(1, $resolu);
        $this->assertSame('Filiere + niveau', $resolu->first()->libelle);
    }

    /**
     * La filiere decrit le dossier attendu ("un transfert n'apporte pas les
     * memes pieces qu'une premiere inscription"), le niveau ne fait que graduer.
     * En cas d'egalite de precision, la filiere l'emporte donc.
     */
    public function test_la_filiere_prime_sur_le_niveau_a_precision_egale(): void
    {
        $resolu = ESBTPPieceDossier::resoudreParCode(new Collection([
            $this->piece(['id' => 1, 'libelle' => 'Niveau seul', 'niveau_id' => 3]),
            $this->piece(['id' => 2, 'libelle' => 'Filiere seule', 'filiere_id' => 7]),
        ]));

        $this->assertSame('Filiere seule', $resolu->first()->libelle);
    }

    public function test_les_codes_distincts_coexistent_tous(): void
    {
        $resolu = ESBTPPieceDossier::resoudreParCode(new Collection([
            $this->piece(['id' => 1, 'code' => 'extrait_naissance', 'ordre' => 1]),
            $this->piece(['id' => 2, 'code' => 'photo', 'ordre' => 2]),
            $this->piece(['id' => 3, 'code' => 'diplome_bac', 'ordre' => 3]),
        ]));

        $this->assertSame(
            ['extrait_naissance', 'photo', 'diplome_bac'],
            $resolu->pluck('code')->all()
        );
    }

    /**
     * Le resultat sert directement a l'affichage : il doit sortir dans l'ordre
     * du dossier, pas dans l'ordre de precision qui a servi a arbitrer.
     */
    public function test_le_resultat_est_rendu_dans_l_ordre_d_affichage(): void
    {
        $resolu = ESBTPPieceDossier::resoudreParCode(new Collection([
            $this->piece(['id' => 10, 'code' => 'photo', 'ordre' => 30]),
            // Surcharge precise d'une piece qui s'affiche en premier
            $this->piece(['id' => 11, 'code' => 'extrait_naissance', 'ordre' => 10, 'filiere_id' => 7, 'niveau_id' => 3]),
            $this->piece(['id' => 12, 'code' => 'extrait_naissance', 'ordre' => 10]),
            $this->piece(['id' => 13, 'code' => 'diplome_bac', 'ordre' => 20]),
        ]));

        $this->assertSame(
            ['extrait_naissance', 'diplome_bac', 'photo'],
            $resolu->pluck('code')->all()
        );
        $this->assertSame(11, $resolu->first()->id);
    }

    public function test_un_catalogue_vide_ne_produit_rien(): void
    {
        $this->assertTrue(ESBTPPieceDossier::resoudreParCode(new Collection())->isEmpty());
    }
}
