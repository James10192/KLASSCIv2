<?php

namespace Tests\Unit\Enums;

use App\Enums\ModeSeparationDesDevoirs;
use PHPUnit\Framework\TestCase;

/**
 * La lecture d'un mode de séparation des devoirs.
 *
 * Le cas qui porte le risque est la RÉTROCOMPATIBILITÉ : les réglages écrits
 * avant l'existence du troisième état portent un booléen, et la colonne
 * `settings.value` le rend tantôt en booléen, tantôt en chaîne selon le type
 * déclaré. Un `tryFrom()` seul rendrait `null` sur toutes ces valeurs et ferait
 * retomber la règle sur son défaut — sans un mot, et dans le sens permissif.
 */
class ModeSeparationDesDevoirsTest extends TestCase
{
    /** @return array<string, array{mixed, ModeSeparationDesDevoirs}> */
    public static function ecrituresLisibles(): array
    {
        return [
            'mode explicite' => ['bloquant', ModeSeparationDesDevoirs::BLOQUANT],
            'mode en majuscules' => ['OBSERVATION', ModeSeparationDesDevoirs::OBSERVATION],
            'mode entoure d espaces' => ['  inactif ', ModeSeparationDesDevoirs::INACTIF],
            'booleen vrai' => [true, ModeSeparationDesDevoirs::BLOQUANT],
            'booleen faux' => [false, ModeSeparationDesDevoirs::INACTIF],
            'chaine un' => ['1', ModeSeparationDesDevoirs::BLOQUANT],
            'chaine zero' => ['0', ModeSeparationDesDevoirs::INACTIF],
            'chaine true' => ['true', ModeSeparationDesDevoirs::BLOQUANT],
            'chaine false' => ['false', ModeSeparationDesDevoirs::INACTIF],
            'deja un mode' => [ModeSeparationDesDevoirs::OBSERVATION, ModeSeparationDesDevoirs::OBSERVATION],
        ];
    }

    /** @dataProvider ecrituresLisibles */
    public function test_les_ecritures_connues_sont_lues(mixed $valeur, ModeSeparationDesDevoirs $attendu): void
    {
        $this->assertSame($attendu, ModeSeparationDesDevoirs::depuisReglage($valeur));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function ecrituresIllisibles(): array
    {
        return [
            'nul' => [null],
            'chaine vide' => [''],
            'mot inconnu' => ['peut-etre'],
            'nombre hors sujet' => [42],
            'tableau' => [['bloquant']],
        ];
    }

    /** @dataProvider ecrituresIllisibles */
    public function test_une_ecriture_inconnue_rend_null_plutot_qu_un_mode_arbitraire(mixed $valeur): void
    {
        // Rendre un mode ici ferait le repli muet que cette branche corrige
        // ailleurs : c'est à l'appelant de décider, et de le journaliser.
        $this->assertNull(ModeSeparationDesDevoirs::depuisReglage($valeur));
    }

    public function test_seul_le_mode_bloquant_refuse(): void
    {
        $this->assertTrue(ModeSeparationDesDevoirs::BLOQUANT->refuse());
        $this->assertFalse(ModeSeparationDesDevoirs::OBSERVATION->refuse());
        $this->assertFalse(ModeSeparationDesDevoirs::INACTIF->refuse());
    }

    public function test_l_observation_s_applique_quand_meme(): void
    {
        // C'est ce qui la distingue de l'inactivité : elle constate et
        // journalise. Si elle ne s'appliquait pas, elle ne vaudrait rien.
        $this->assertTrue(ModeSeparationDesDevoirs::OBSERVATION->sApplique());
        $this->assertTrue(ModeSeparationDesDevoirs::BLOQUANT->sApplique());
        $this->assertFalse(ModeSeparationDesDevoirs::INACTIF->sApplique());
    }

    public function test_chaque_mode_est_presentable_a_l_ecran(): void
    {
        $options = ModeSeparationDesDevoirs::options();

        $this->assertCount(count(ModeSeparationDesDevoirs::cases()), $options);

        foreach (ModeSeparationDesDevoirs::cases() as $mode) {
            $this->assertArrayHasKey($mode->value, $options);
            $this->assertNotSame('', $mode->label());
            $this->assertNotSame('', $mode->hint());
        }
    }
}
