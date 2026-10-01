<?php

namespace Tests\Unit\Domain\Assistant\Support;

use App\Domain\Assistant\Support\FilDeSupport;
use App\Domain\Assistant\Support\GuideDeSupport;
use App\Domain\Assistant\Support\Intention;
use App\Domain\Assistant\Support\TourDeSupport;
use PHPUnit\Framework\TestCase;

/**
 * Le repli sans modèle : une question à la fois, puis un récapitulatif fidèle.
 */
class GuideDeSupportTest extends TestCase
{
    private GuideDeSupport $guide;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guide = new GuideDeSupport();
    }

    public function test_un_probleme_parcourt_toutes_les_questions_puis_recapitule(): void
    {
        $page = ['titre' => 'Bulletin de Koné Awa — KLASSCI'];
        $messages = [
            ['role' => 'nanan', 'texte' => 'Que se passe-t-il ?'],
            ['role' => 'personne', 'texte' => 'Le bulletin de Koné ne sort pas.'],
        ];

        $questions = [];
        for ($i = 0; $i < 10; $i++) {
            $tour = $this->guide->tour(Intention::PROBLEME, FilDeSupport::depuis($messages), $page, 7);
            if ($tour->action !== TourDeSupport::QUESTION) {
                break;
            }
            $questions[] = $tour->texte;
            $messages[] = ['role' => 'nanan', 'texte' => $tour->texte];
            $messages[] = ['role' => 'personne', 'texte' => "réponse $i"];
        }

        $this->assertCount(6, $questions);
        $this->assertSame("Cela se passe-t-il sur la page que vous aviez ouverte en demandant de l'aide ?", $questions[0]);
        $this->assertSame(GuideDeSupport::QUESTION_URGENCE, $questions[1]);
        $this->assertStringContainsString("message d'erreur", $questions[5]);
        $this->assertSame(TourDeSupport::RECAPITULATIF, $tour->action);
        $this->assertSame('Le bulletin de Koné ne sort pas.', $tour->recap['titre']);
        $this->assertSame('PROBLEME', $tour->recap['categorie']);
        $this->assertStringContainsString('réponse 5', $tour->recap['description']);
        // Le titre de l'onglet (souvent un nom d'élève) ne part jamais au support.
        foreach ($questions as $question) {
            $this->assertStringNotContainsString('Bulletin de Koné', $question);
        }
        $this->assertStringNotContainsString('Bulletin de Koné', $tour->recap['description']);
    }

    public function test_une_personne_bloquee_est_classee_bloque(): void
    {
        $tour = $this->guide->tour(Intention::PROBLEME, FilDeSupport::depuis([
            ['role' => 'personne', 'texte' => 'Je ne peux plus encaisser.'],
            ['role' => 'nanan', 'texte' => GuideDeSupport::QUESTION_URGENCE],
            ['role' => 'personne', 'texte' => GuideDeSupport::CHOIX_BLOQUE],
        ]), [], 7, true);

        $this->assertSame('BLOQUE', $tour->recap['categorie']);
    }

    public function test_le_titre_de_page_perd_son_suffixe_klassci_quel_que_soit_le_tiret(): void
    {
        foreach (['Bulletins - KLASSCI', 'Bulletins – KLASSCI', 'Bulletins — KLASSCI', 'Bulletins | KLASSCI'] as $titre) {
            $this->assertSame('Bulletins', GuideDeSupport::titrePage(['titre' => $titre]));
        }
    }

    public function test_sans_titre_de_page_la_question_est_ouverte(): void
    {
        $tour = $this->guide->tour(Intention::PROBLEME, FilDeSupport::depuis([['role' => 'personne', 'texte' => 'Erreur 500']]), [], 6);

        $this->assertSame('Sur quelle page ou quel écran cela se passe-t-il ?', $tour->texte);
        $this->assertSame([], $tour->choix);
    }

    public function test_une_question_d_usage_sans_modele_est_transmise_telle_quelle(): void
    {
        $tour = $this->guide->tour(Intention::COMMENT, FilDeSupport::depuis([['role' => 'personne', 'texte' => 'Comment imprimer un reçu ?']]), [], 6);

        $this->assertSame(TourDeSupport::RECAPITULATIF, $tour->action);
        $this->assertSame('QUESTION', $tour->recap['categorie']);
        $this->assertStringContainsString('je transmets votre question', $tour->texte);
    }

    public function test_le_plafond_de_questions_force_le_recapitulatif(): void
    {
        $tour = $this->guide->tour(Intention::PROBLEME, FilDeSupport::depuis([
            ['role' => 'personne', 'texte' => 'Ça bloque.'],
            ['role' => 'nanan', 'texte' => 'Où ?'],
            ['role' => 'personne', 'texte' => 'Partout.'],
        ]), [], 1);

        $this->assertSame(TourDeSupport::RECAPITULATIF, $tour->action);
    }

    public function test_fil_vide_premiere_question_selon_l_intention(): void
    {
        foreach (Intention::cases() as $intention) {
            $tour = $this->guide->tour($intention, FilDeSupport::depuis([]), [], 6);
            $this->assertSame(TourDeSupport::QUESTION, $tour->action);
            $this->assertNotSame('', $tour->texte);
        }
    }

    public function test_titre_court_coupe_sur_un_mot(): void
    {
        $titre = GuideDeSupport::titreCourt(str_repeat('bulletin ', 30));

        $this->assertLessThanOrEqual(81, mb_strlen($titre));
        $this->assertStringEndsWith('…', $titre);
        $this->assertSame("Demande d'aide", GuideDeSupport::titreCourt('   '));
    }
}
