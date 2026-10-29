<?php

namespace Tests\Unit\Inscription;

use App\Models\ESBTPCandidature;
use App\Services\Inscription\PreRemplissageCandidature;
use Tests\TestCase;

/**
 * La date saisie correspond-elle a la candidature qu'on inscrit ?
 *
 * La comparaison est posee AVANT la creation, et c'est tout l'interet. Apres
 * coup, elle arrivait sur une inscription deja committee : elle ne pouvait
 * plus que constater, laisser la candidature ouverte pour toujours — rien,
 * dans la corbeille, ne ferme un dossier « acceptee » — et renvoyer l'agent
 * vers un bouton « Creer l'inscription » toujours affiche. En suivant la
 * consigne, il creait un SECOND etudiant : le doublon meme que la cloture
 * existe pour empecher.
 *
 * Le cas frequent n'est pas l'erreur d'aiguillage mais la correction : le
 * candidat a tape 2007 sur son telephone, la piece d'identite dit 2006. C'est
 * le geste normal de l'etape sur place, d'ou une confirmation a cocher plutot
 * qu'un refus sec — et d'ou les deux cotes testes ici.
 */
class NaissanceDivergenteTest extends TestCase
{
    private function candidature(?string $naissance): ESBTPCandidature
    {
        $candidature = new ESBTPCandidature;
        $candidature->forceFill(['id' => 12, 'date_naissance' => $naissance]);

        return $candidature;
    }

    public function test_une_date_identique_ne_diverge_pas(): void
    {
        $this->assertFalse(
            PreRemplissageCandidature::naissanceDiverge($this->candidature('2006-03-15'), '2006-03-15')
        );
    }

    /** Le cas dangereux : l'agent inscrit quelqu'un d'autre depuis ce formulaire. */
    public function test_une_date_differente_diverge(): void
    {
        $this->assertTrue(
            PreRemplissageCandidature::naissanceDiverge($this->candidature('2006-03-15'), '2001-11-02')
        );
    }

    /** Un jour d'ecart compte : c'est justement la correction qu'on veut voir. */
    public function test_un_jour_d_ecart_diverge(): void
    {
        $this->assertTrue(
            PreRemplissageCandidature::naissanceDiverge($this->candidature('2006-03-15'), '2006-03-16')
        );
    }

    /**
     * Une date illisible n'est pas une divergence : la validation la refusera
     * avec un message qui parle d'elle. Repondre « ne correspond pas a la
     * candidature » pour une case vide enverrait l'agent chercher a cote.
     *
     * @dataProvider datesIllisibles
     */
    public function test_une_date_illisible_laisse_parler_la_validation(string $saisie): void
    {
        $this->assertFalse(
            PreRemplissageCandidature::naissanceDiverge($this->candidature('2006-03-15'), $saisie)
        );
    }

    /** @return array<string, array{string}> */
    public static function datesIllisibles(): array
    {
        return [
            'vide' => [''],
            'espaces' => ['   '],
            'jour inexistant' => ['2006-02-31'],
            'texte' => ['hier'],
            'format francais' => ['15/03/2006'],
        ];
    }

    /**
     * Une candidature sans date de naissance est une anomalie de donnees — le
     * champ est obligatoire au depot — pas un signal sur l'identite de qui
     * l'on inscrit. On ne bloque pas l'agent la-dessus.
     */
    public function test_une_candidature_sans_date_ne_bloque_personne(): void
    {
        $this->assertFalse(
            PreRemplissageCandidature::naissanceDiverge($this->candidature(null), '2006-03-15')
        );
    }
}
