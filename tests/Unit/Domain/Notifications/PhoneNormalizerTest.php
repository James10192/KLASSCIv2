<?php

namespace Tests\Unit\Domain\Notifications;

use App\Domain\Notifications\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    /**
     * L'indicatif de l'instance est un état statique : sans cette remise à
     * zéro, un test qui en configure un le laisserait aux suivants, et les cas
     * ivoiriens ci-dessous échoueraient selon l'ordre d'exécution.
     */
    protected function tearDown(): void
    {
        PhoneNormalizer::definirResolveurIndicatif(null);

        parent::tearDown();
    }

    public function test_null_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164(null));
        $this->assertNull(PhoneNormalizer::toE164(''));
        $this->assertNull(PhoneNormalizer::toE164('   '));
    }

    public function test_national_format_mtn(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('0707123456'));
    }

    public function test_national_format_orange(): void
    {
        $this->assertSame('+2250512345678', PhoneNormalizer::toE164('0512345678'));
    }

    public function test_national_format_with_spaces(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('07 07 12 34 56'));
    }

    public function test_national_format_with_dashes(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('07-07-12-34-56'));
    }

    public function test_already_e164(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('+2250707123456'));
    }

    public function test_double_zero_country_code(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('002250707123456'));
    }

    /**
     * La forme canonique écrite sans le « + » — c'est ce que rend
     * `toWhatsAppId()`, et ce que certains stockages ont conservé. Le chatbot
     * parent s'en sert : `normalize('2250707123456')`.
     */
    public function test_e164_sans_le_plus(): void
    {
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('2250707123456'));
        $this->assertSame(
            '+2250707123456',
            PhoneNormalizer::toE164(PhoneNormalizer::toWhatsAppId('07 07 12 34 56'))
        );
    }

    /**
     * Mais pas pour un AUTRE pays : sans « + », `33612345678` et une saisie
     * nationale sont indistinguables, et rien ne permettrait de trancher.
     */
    public function test_e164_sans_le_plus_refuse_un_autre_pays(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('33612345678'));
    }

    public function test_invalid_prefix_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('1234567890'));
        $this->assertNull(PhoneNormalizer::toE164('99 99 99 99 99'));
    }

    public function test_too_short_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('07071234'));
    }

    public function test_too_long_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('070712345678901'));
    }

    public function test_letters_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('abc'));
    }

    public function test_to_whatsapp_id_strips_plus(): void
    {
        $this->assertSame('2250707123456', PhoneNormalizer::toWhatsAppId('0707123456'));
        $this->assertNull(PhoneNormalizer::toWhatsAppId('invalid'));
    }

    public function test_is_valid(): void
    {
        $this->assertTrue(PhoneNormalizer::isValid('0707123456'));
        $this->assertFalse(PhoneNormalizer::isValid('invalid'));
        $this->assertFalse(PhoneNormalizer::isValid(null));
    }

    // ------------------------------------------------------------------
    // Hors de Côte d'Ivoire — ajouté pour `ucao-benin`, septembre 2026.
    //
    // Les cas ci-dessus décrivent le plan ivoirien et ne bougent pas : leur
    // forme canonique est l'index UNIQUE d'`esbtp_candidatures`.
    // ------------------------------------------------------------------

    /**
     * Le refus de cette écriture est ce qui rendait l'instance béninoise
     * inutilisable : elle acceptait la saisie nationale EN LA CORROMPANT
     * (`0142345678` → `+2250142345678`, un abonné Moov ivoirien réel) et
     * refusait la seule écriture qui disait juste.
     */
    public function test_une_ecriture_internationale_etrangere_est_crue(): void
    {
        $this->assertSame('+2290142345678', PhoneNormalizer::toE164('+229 01 42 34 56 78'));
        $this->assertSame('+2290142345678', PhoneNormalizer::toE164('00229 01 42 34 56 78'));
        $this->assertSame('2290142345678', PhoneNormalizer::toWhatsAppId('+229 01 42 34 56 78'));
    }

    /**
     * On croit l'indicatif écrit, et on ne rejoue PAS sur lui le contrôle de
     * préfixe d'un plan qui n'est pas le sien.
     */
    public function test_un_indicatif_etranger_n_est_pas_mesure_au_plan_local(): void
    {
        $this->assertSame('+33612345678', PhoneNormalizer::toE164('+33 6 12 34 56 78'));
        $this->assertFalse(PhoneNormalizer::estMobileNational('+33 6 12 34 56 78'));
    }

    /**
     * Sur NOTRE indicatif en revanche, on connaît le plan : on l'applique.
     * Sans cela `+225` suivi de n'importe quoi passerait sur la seule forme, et
     * deux refus que le portail tient aujourd'hui tomberaient.
     */
    public function test_le_plan_national_reste_applique_a_notre_propre_indicatif(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('+225 27 20 30 10 20'));
        $this->assertNull(PhoneNormalizer::toE164('+225 07 07 12 34'));
    }

    public function test_une_forme_e164_hors_bornes_est_refusee(): void
    {
        $this->assertNull(PhoneNormalizer::toE164('+229 01 42'));
        $this->assertNull(PhoneNormalizer::toE164('+2290142345678901234'));
    }

    public function test_l_indicatif_configure_change_la_saisie_nationale(): void
    {
        PhoneNormalizer::definirResolveurIndicatif(static fn (): string => '229');

        $this->assertSame('+2290142345678', PhoneNormalizer::toE164('0142345678'));
        $this->assertTrue(PhoneNormalizer::estMobileNational('0142345678'));

        // Et l'ivoirien devient, sur cette instance, un étranger bien formé :
        // conservé tel quel, joignable, mais hors du périmètre « national ».
        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('+2250707123456'));
        $this->assertFalse(PhoneNormalizer::estMobileNational('+2250707123456'));
    }

    /**
     * Un réglage mal saisi ne doit pas corrompre une base : on retombe sur le
     * défaut plutôt que de produire des numéros que personne ne rattrapera.
     */
    public function test_un_reglage_illisible_retombe_sur_le_defaut(): void
    {
        PhoneNormalizer::definirResolveurIndicatif(static fn (): string => 'Bénin');

        $this->assertSame('+2250707123456', PhoneNormalizer::toE164('0707123456'));
    }

    public function test_est_mobile_national(): void
    {
        $this->assertTrue(PhoneNormalizer::estMobileNational('0707123456'));
        $this->assertTrue(PhoneNormalizer::estMobileNational('+2250707123456'));
        $this->assertFalse(PhoneNormalizer::estMobileNational('27 20 30 10 20'));
        $this->assertFalse(PhoneNormalizer::estMobileNational(null));
    }

    public function test_decomposer_separe_ce_qu_il_sait_separer(): void
    {
        $this->assertSame(
            ['indicatif' => '225', 'national' => '0707123456'],
            PhoneNormalizer::decomposer('07 07 12 34 56')
        );

        // Indicatif inconnu de cette instance : on ne prétend pas savoir où le
        // couper — découper demanderait la table des indicatifs mondiaux.
        $this->assertSame(
            ['indicatif' => null, 'national' => '33612345678'],
            PhoneNormalizer::decomposer('+33 6 12 34 56 78')
        );
    }
}
