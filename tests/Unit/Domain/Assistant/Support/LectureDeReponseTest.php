<?php

namespace Tests\Unit\Domain\Assistant\Support;

use App\Domain\Assistant\Support\Intention;
use App\Domain\Assistant\Support\LectureDeReponse;
use App\Domain\Assistant\Support\TourDeSupport;
use PHPUnit\Framework\TestCase;

/**
 * Ce que le modèle écrit n'atteint l'écran que s'il respecte le format convenu.
 */
class LectureDeReponseTest extends TestCase
{
    private const CATEGORIES = ['PROBLEME', 'QUESTION', 'SUGGESTION', 'AUTRE'];

    private LectureDeReponse $lecture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lecture = new LectureDeReponse();
    }

    public function test_texte_libre_ou_action_inconnue_est_refuse(): void
    {
        $this->assertNull($this->lecture->lire('Bien sûr !', Intention::PROBLEME, self::CATEGORIES));
        $this->assertNull($this->lecture->lire('{"action":"supprimer","texte":"x"}', Intention::PROBLEME, self::CATEGORIES));
        $this->assertNull($this->lecture->lire('{"action":"question","texte":"   "}', Intention::PROBLEME, self::CATEGORIES));
        $this->assertNull($this->lecture->lire('{"action":"recapitulatif","recap":{"titre":"T"}}', Intention::PROBLEME, self::CATEGORIES));
    }

    public function test_json_entoure_de_balises_est_lu_et_les_choix_sont_bornes(): void
    {
        $tour = $this->lecture->lire("```json\n{\"action\":\"question\",\"texte\":\"Quelle classe ?\",\"choix\":[\"A\",\"\",\"A\",\"B\",\"C\",\"D\",\"E\"]}\n```", Intention::PROBLEME, self::CATEGORIES);

        $this->assertSame(TourDeSupport::QUESTION, $tour->action);
        $this->assertSame(['A', 'B', 'C', 'D'], $tour->choix);
        $this->assertSame('ia', $tour->source);
    }

    public function test_categorie_hors_liste_remplacee_par_celle_de_l_intention(): void
    {
        $tour = $this->lecture->lire('{"action":"recapitulatif","recap":{"titre":"Ligne\nsautée","description":"D","categorie":"URGENT"}}', Intention::COMMENT, self::CATEGORIES);

        $this->assertSame('QUESTION', $tour->recap['categorie']);
        $this->assertSame('Ligne sautée', $tour->recap['titre']);
    }

    public function test_une_promesse_de_delai_est_retiree(): void
    {
        $tour = $this->lecture->lire('{"action":"reponse","texte":"Ouvrez Paiements. Le support vous répondra sous 24 h. Bonne journée."}', Intention::COMMENT, self::CATEGORIES);

        $this->assertSame('Ouvrez Paiements. Bonne journée.', $tour->texte);
        $this->assertSame('Rien de promis.', LectureDeReponse::sansPromesseDeDelai('Rien de promis.'));
    }
}
