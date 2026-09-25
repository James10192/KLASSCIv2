<?php

namespace Tests\Unit\Emails;

use App\Enums\CanalPortailPublic;
use App\Enums\CanalVerification;
use App\Enums\EtatEmail;
use App\Enums\NaturePortailPublic;
use App\Enums\StatutVerificationContact;
use Tests\TestCase;

/**
 * Les enums portent une partie du contrat publie au site vitrine : leurs
 * valeurs sont verrouillees ici.
 */
class EnumsVerificationTest extends TestCase
{
    public function test_le_canal_annonce_le_statut_et_le_masque_du_contrat(): void
    {
        $this->assertSame('verification_email_requise', CanalVerification::Email->statutPublic());
        $this->assertSame('email_masque', CanalVerification::Email->cleMasque());
        $this->assertSame(StatutVerificationContact::EmailNonVerifie, CanalVerification::Email->statutEnAttente());

        $this->assertSame('verification_telephone_requise', CanalVerification::Telephone->statutPublic());
        $this->assertSame('telephone_masque', CanalVerification::Telephone->cleMasque());
        $this->assertSame(StatutVerificationContact::TelephoneNonVerifie, CanalVerification::Telephone->statutEnAttente());
    }

    public function test_les_etats_de_verification_et_leurs_badges(): void
    {
        $this->assertSame(['email_non_verifie', 'telephone_non_verifie'], StatutVerificationContact::valeursEnAttente());
        $this->assertSame('Contact non vérifié', StatutVerificationContact::badge('email_non_verifie'));
        $this->assertSame('Contact non vérifié', StatutVerificationContact::badge('telephone_non_verifie'));
        $this->assertNull(StatutVerificationContact::badge('verification_expiree'));
        $this->assertSame('Contact non vérifiable', StatutVerificationContact::badge('verification_impossible'));
        $this->assertSame('Contact à reconfirmer', StatutVerificationContact::badge('contact_a_reconfirmer'));
        $this->assertSame(['email_non_verifie', 'telephone_non_verifie', 'verification_impossible', 'contact_a_reconfirmer'], StatutVerificationContact::valeursAConfirmer());
        $this->assertNull(StatutVerificationContact::badge('verifie'));
        $this->assertNull(StatutVerificationContact::badge(null));
    }

    public function test_etat_email_joignable_et_type_suspect(): void
    {
        $this->assertTrue(EtatEmail::Valide->joignable());
        $this->assertTrue(EtatEmail::FauteProbable->joignable());
        foreach ([EtatEmail::Vide, EtatEmail::Invalide, EtatEmail::Factice, EtatEmail::FauteDeFrappe, EtatEmail::SansMx] as $etat) {
            $this->assertFalse($etat->joignable(), $etat->value);
        }

        $this->assertSame('factice', EtatEmail::Factice->typeSuspect());
        $this->assertSame('faute_de_frappe', EtatEmail::FauteDeFrappe->typeSuspect());
        $this->assertSame('sans_mx', EtatEmail::SansMx->typeSuspect());
        $this->assertNull(EtatEmail::Valide->typeSuspect());
        $this->assertNull(EtatEmail::FauteProbable->typeSuspect());
    }

    public function test_le_canal_de_verification_du_portail_est_toujours_ouvert_et_a_ses_seaux(): void
    {
        $canal = CanalPortailPublic::depuis('verification');

        $this->assertSame(CanalPortailPublic::Verification, $canal);
        $this->assertTrue($canal->ouvert());
        $this->assertNotSame('', $canal->messageFerme());

        $cles = array_map(fn ($seau) => $seau->cle, $canal->seaux(NaturePortailPublic::Identite, 'empreinte'));
        $this->assertSame(['rp-ip:verification:empreinte', 'rp-global:verification'], $cles);
    }
}
