<?php

namespace Tests\Unit\OfficialDocuments;

use App\Support\AccordGenre;
use PHPUnit\Framework\TestCase;

/**
 * La décision imprimée sur un relevé de notes officiel.
 *
 * Le modèle du ministère écrit « Admise » pour une étudiante, dans la colonne
 * DÉCISION de chaque unité d'enseignement et dans la décision d'année. Ces
 * quelques mots partent au ministère et suivent l'étudiante toute sa scolarité.
 *
 * L'accord se fait à partir du sexe GELÉ dans l'instantané, jamais de la fiche
 * courante : un relevé réédité doit ressortir identique.
 */
class DecisionAccordeeAuGenreTest extends TestCase
{
    /** Le rendu du gabarit, isolé de Blade : la même expression, testée. */
    private function decision(?string $brut, ?string $sexe): string
    {
        return $brut === null
            ? '—'
            : AccordGenre::accorderPhrase($brut === 'admis' ? 'Admis' : 'Ajourné', $sexe);
    }

    public function test_une_etudiante_est_admise(): void
    {
        $this->assertSame('Admise', $this->decision('admis', 'F'));
        $this->assertSame('Ajournée', $this->decision('ajourne', 'F'));
    }

    public function test_un_etudiant_est_admis(): void
    {
        $this->assertSame('Admis', $this->decision('admis', 'M'));
        $this->assertSame('Ajourné', $this->decision('ajourne', 'M'));
    }

    public function test_un_sexe_absent_reste_au_masculin(): void
    {
        // Le masculin est le défaut du français quand le genre n'est pas connu.
        // Six étudiants importés d'USAT n'ont pas de date de naissance lisible ;
        // rien ne garantit que le sexe sera toujours là non plus.
        $this->assertSame('Admis', $this->decision('admis', null));
        $this->assertSame('Admis', $this->decision('admis', ''));
    }

    public function test_une_decision_absente_ne_conclut_rien(): void
    {
        // Un relevé ne tranche pas à la place du jury. Si l'instantané ne porte
        // pas de décision, la case reste vide.
        $this->assertSame('—', $this->decision(null, 'F'));
    }

    public function test_la_mention_officielle_porte_son_trait_d_union(): void
    {
        // « Assez-bien » sur le modèle du ministère, et non « Assez bien ».
        // C'est la forme du document officiel ; elle n'est pas négociable.
        $mentions = ['Excellent', 'Très bien', 'Bien', 'Assez-bien', 'Passable'];

        foreach ($mentions as $mention) {
            // Aucune n'est dans la table d'accord : une mention ne s'accorde pas
            // au genre de l'étudiant, elle qualifie une note.
            $this->assertSame($mention, AccordGenre::accorderPhrase($mention, 'F'), $mention);
        }
    }
}
