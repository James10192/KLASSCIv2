<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPFiliere;
use PHPUnit\Framework\TestCase;

/**
 * Sous quelle nature une filière se présente à qui la choisit.
 *
 * Sans base de données : la règle tient dans deux colonnes, et c'est justement
 * pour cela qu'il faut l'éprouver. Elle se réécrit de mémoire, un peu de
 * travers — « un parcours pointe vers moi, donc je suis un reflet » —, et cette
 * version-là est fausse : sur les instances mixtes, un parcours LMD pointe
 * légitimement vers une VRAIE filière BTS équivalente.
 */
class ESBTPFiliereNatureLmdTest extends TestCase
{
    private function filiere(array $attributs): ESBTPFiliere
    {
        return (new ESBTPFiliere())->forceFill($attributs);
    }

    public function test_une_vraie_filiere_n_a_rien_a_preciser(): void
    {
        $filiere = $this->filiere(['name' => 'Génie civil']);

        $this->assertNull($filiere->natureLmd());
        $this->assertFalse($filiere->estMiroirLmd());
    }

    public function test_un_reflet_de_parcours_se_presente_comme_un_parcours(): void
    {
        $filiere = $this->filiere(['name' => 'Agronomie', 'lmd_parcours_id' => 7]);

        $this->assertSame('Parcours', $filiere->natureLmd());
        $this->assertTrue($filiere->estMiroirLmd());
    }

    public function test_un_reflet_de_mention_se_presente_comme_une_mention(): void
    {
        $filiere = $this->filiere(['name' => 'Agronomie', 'lmd_mention_id' => 3]);

        $this->assertSame('Mention', $filiere->natureLmd());
        $this->assertTrue($filiere->estMiroirLmd());
    }

    /**
     * Un reflet créé pour un parcours porte parfois aussi la mention dont il
     * relève. Le parcours est alors la nature la plus précise : c'est lui que
     * la classe désigne, et c'est donc lui qu'il faut montrer.
     */
    public function test_le_parcours_prime_sur_la_mention(): void
    {
        $filiere = $this->filiere([
            'name' => 'Agronomie',
            'lmd_mention_id' => 3,
            'lmd_parcours_id' => 7,
        ]);

        $this->assertSame('Parcours', $filiere->natureLmd());
    }
}
