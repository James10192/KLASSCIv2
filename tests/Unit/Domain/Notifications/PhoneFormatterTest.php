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
        PhoneNormalizer::definirResolveurReglages(
            static fn (string $cle): ?string => $cle === PhoneNormalizer::CLE_INDICATIF ? '229' : null
        );

        $this->assertSame('+229 01 42 34 56 78', PhoneFormatter::toReadable('0142345678'));
        $this->assertSame('+229 01 42 34 56 78', PhoneFormatter::toReadable('+229 01 42 34 56 78'));
    }

    public function test_un_indicatif_inconnu_reste_non_groupe(): void
    {
        // « +33 61 23 45 678 » se lirait plus mal que la forme canonique.
        $this->assertSame('+33612345678', PhoneFormatter::toReadable('+33 6 12 34 56 78'));
    }
}
