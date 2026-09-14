<?php

namespace Tests\Unit\Domain\Notifications;

use App\Domain\Notifications\PhoneFormatter;
use App\Domain\Notifications\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        PhoneNormalizer::definirResolveurReglages(null);
    }

    protected function tearDown(): void
    {
        PhoneNormalizer::definirResolveurReglages(null);

        parent::tearDown();
    }

    public function test_null_returns_null(): void
    {
        $this->assertNull(PhoneFormatter::toReadable(null));
        $this->assertNull(PhoneFormatter::toReadable(''));
    }

    public function test_invalid_returns_null(): void
    {
        $this->assertNull(PhoneFormatter::toReadable('abc'));
        $this->assertNull(PhoneFormatter::toReadable('99 99 99 99 99'));
    }

    public function test_national_format_pairs(): void
    {
        $this->assertSame('+225 07 07 12 34 56', PhoneFormatter::toReadable('0707123456'));
    }

    public function test_orange_format(): void
    {
        $this->assertSame('+225 05 12 34 56 78', PhoneFormatter::toReadable('0512345678'));
    }

    public function test_with_spaces(): void
    {
        $this->assertSame('+225 07 07 12 34 56', PhoneFormatter::toReadable('07 07 12 34 56'));
    }

    public function test_already_e164(): void
    {
        $this->assertSame('+225 07 07 12 34 56', PhoneFormatter::toReadable('+2250707123456'));
    }

    /**
     * L'indicatif rendu est celui qui a été RECONNU, pas un « +225 » recollé.
     *
     * Ce formateur écrivait l'indicatif en dur : corrigé seul, le normaliseur
     * aurait été neutralisé ici — la fiche que le comptable lit avant d'appeler,
     * et la colonne téléphone de l'export de recouvrement.
     */
    public function test_l_indicatif_rendu_est_celui_qui_a_ete_reconnu(): void
    {
        $this->reglerSurLeBenin();

        $this->assertSame('+229 01 42 34 56 78', PhoneFormatter::toReadable('0142345678'));
        $this->assertSame('+229 01 42 34 56 78', PhoneFormatter::toReadable('+229 01 42 34 56 78'));
    }

    /**
     * Le vrai service rendu par les préfixes déclarés : REFUSER ce qui n'existe
     * pas au Bénin.
     *
     * `0707123456` est un mobile ivoirien parfaitement valide. Sur une instance
     * béninoise il ne désigne personne — la renumérotation ARCEP du 30 novembre
     * 2024 a préfixé `01` à tous les numéros du pays. Sans les préfixes
     * déclarés, il passerait, deviendrait `+2290707123456`, et le message
     * partirait sans arriver ni lever d'erreur.
     */
    public function test_un_numero_ivoirien_est_refuse_sur_une_instance_beninoise(): void
    {
        $this->reglerSurLeBenin();

        $this->assertNull(PhoneFormatter::toReadable('0707123456'));
        $this->assertNull(PhoneFormatter::toReadable('0512345678'));

        // Écrit en entier, il reste accepté : son indicatif est explicite, et
        // une famille de la diaspora a le droit d'un numéro étranger. Il sort
        // non groupé — hors de l'indicatif de l'instance, on ne sait pas où
        // couper, et le découper demanderait la table des indicatifs mondiaux.
        $this->assertSame('+2250707123456', PhoneFormatter::toReadable('+2250707123456'));
    }

    /**
     * Les deux réglages vont par paire — `TelephoneSettingsService` refuse
     * désormais de n'en poser qu'un. Ce jeu d'essai décrit donc la SEULE
     * configuration béninoise qu'une école puisse réellement enregistrer.
     */
    private function reglerSurLeBenin(): void
    {
        PhoneNormalizer::definirResolveurReglages(static fn (string $cle): ?string => match ($cle) {
            PhoneNormalizer::CLE_INDICATIF => '229',
            PhoneNormalizer::CLE_PREFIXES => '01',
            default => null,
        });
    }

    public function test_un_indicatif_inconnu_reste_non_groupe(): void
    {
        // « +33 61 23 45 678 » se lirait plus mal que la forme canonique.
        $this->assertSame('+33612345678', PhoneFormatter::toReadable('+33 6 12 34 56 78'));
    }
}
