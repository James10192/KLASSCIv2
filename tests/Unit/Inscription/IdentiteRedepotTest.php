<?php

namespace Tests\Unit\Inscription;

use App\Models\ESBTPCandidature;
use App\Services\Inscription\PortailCandidatureService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Qui a le droit de reecrire une candidature deja decidee ?
 *
 * La cle d'unicite du canal public est le TELEPHONE, et en Cote d'Ivoire un
 * foyer le partage. Aya candidate, l'ecole accepte ; Koffi depose ensuite avec
 * le meme numero. Sans cette verification, la ligne devenait Koffi, « en
 * attente », `traite_par` remis a null : la decision de l'ecole effacee,
 * l'identite d'Aya avec, et rien de visible — le dossier quittait l'onglet
 * « Acceptée » pour reapparaitre dans « En attente » sous un autre nom.
 *
 * La comparaison est indulgente sur la forme et stricte sur le fond : un
 * formulaire public se remplit rarement deux fois a l'identique, mais un
 * prenom different n'est pas une faute de frappe.
 */
class IdentiteRedepotTest extends TestCase
{
    private function estLaMemePersonne(array $deposees): bool
    {
        $candidature = new ESBTPCandidature([
            'nom' => 'Kouassi',
            'prenoms' => 'Aya Marie',
        ]);
        $candidature->date_naissance = '2007-03-15';

        $methode = new ReflectionMethod(PortailCandidatureService::class, 'memeIdentite');
        $methode->setAccessible(true);

        return $methode->invoke(
            app(PortailCandidatureService::class),
            $candidature,
            $deposees
        );
    }

    /** Le cas qui a motive la verification : le cadet, sur le telephone du foyer. */
    public function test_un_autre_membre_du_foyer_n_est_pas_la_meme_personne(): void
    {
        $this->assertFalse($this->estLaMemePersonne([
            'nom' => 'Kouassi',
            'prenoms' => 'Koffi',
            'date_naissance' => '2009-11-02',
        ]));
    }

    public function test_un_prenom_different_suffit_a_distinguer(): void
    {
        $this->assertFalse($this->estLaMemePersonne([
            'nom' => 'Kouassi',
            'prenoms' => 'Koffi',
            'date_naissance' => '2007-03-15',
        ]));
    }

    public function test_une_date_de_naissance_differente_suffit_a_distinguer(): void
    {
        $this->assertFalse($this->estLaMemePersonne([
            'nom' => 'Kouassi',
            'prenoms' => 'Aya Marie',
            'date_naissance' => '2006-03-15',
        ]));
    }

    /**
     * Indulgente sur la forme : une famille qui redepose ne retape pas son nom
     * au caractere pres, et lui refuser sa correction pour une majuscule
     * l'enverrait telephoner a l'ecole.
     */
    public function test_la_casse_et_les_espaces_ne_changent_personne(): void
    {
        $this->assertTrue($this->estLaMemePersonne([
            'nom' => '  KOUASSI ',
            'prenoms' => 'aya   marie',
            'date_naissance' => '2007-03-15',
        ]));
    }

    public function test_les_accents_ne_changent_personne(): void
    {
        $candidature = new ESBTPCandidature(['nom' => 'Traoré', 'prenoms' => 'Aïcha']);
        $candidature->date_naissance = '2007-03-15';

        $methode = new ReflectionMethod(PortailCandidatureService::class, 'memeIdentite');
        $methode->setAccessible(true);

        $this->assertTrue($methode->invoke(
            app(PortailCandidatureService::class),
            $candidature,
            ['nom' => 'TRAORE', 'prenoms' => 'Aicha', 'date_naissance' => '2007-03-15']
        ));
    }

    /**
     * L'identite fait partie du journal d'audit, et pas par gout de la
     * completude : c'est une surface NON AUTHENTIFIEE qui la reecrit. Sans ces
     * colonnes, le journal dirait « acceptée → en attente » sans jamais dire
     * que le dossier a change de personne.
     */
    public function test_l_identite_est_auditee(): void
    {
        $liste = (new ReflectionMethod(ESBTPCandidature::class, 'getAuditInclude'))
            ->invoke(new ESBTPCandidature);

        foreach (['nom', 'prenoms', 'date_naissance'] as $champ) {
            $this->assertContains($champ, $liste, "{$champ} doit etre audite");
        }
    }
}
