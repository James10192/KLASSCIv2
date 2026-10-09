<?php

namespace Tests\Unit\Admissions;

use App\Models\ESBTPCandidatureWorkflow;
use App\Services\Admissions\EtatInvitationCompteEtudiant;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class EtatInvitationCompteEtudiantTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_aucun_lien(): void
    {
        $this->assertSame('absent', EtatInvitationCompteEtudiant::lire(new ESBTPCandidatureWorkflow())['code']);
    }

    public function test_lien_cree_ne_vaut_pas_preuve_de_remise(): void
    {
        Carbon::setTestNow('2026-10-09 12:00:00');
        $w = new ESBTPCandidatureWorkflow([
            'activation_token_hash' => str_repeat('a', 64),
            'activation_token_expires_at' => Carbon::now()->addHours(48),
        ]);
        $r = EtatInvitationCompteEtudiant::lire($w);

        $this->assertSame('cree', $r['code']);
        $this->assertStringContainsString('non confirmée', $r['libelle']);
        $this->assertInstanceOf(Carbon::class, $r['expiration']);
        $this->assertArrayNotHasKey('token', $r);
        $this->assertArrayNotHasKey('url', $r);
    }

    public function test_lien_expire(): void
    {
        Carbon::setTestNow('2026-10-09 12:00:00');
        $w = new ESBTPCandidatureWorkflow([
            'activation_token_hash' => str_repeat('b', 64),
            'activation_token_expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->assertSame('expire', EtatInvitationCompteEtudiant::lire($w)['code']);
    }

    public function test_lien_utilise_ne_donne_pas_une_nouvelle_activation(): void
    {
        $w = new ESBTPCandidatureWorkflow(['activation_token_used_at' => now()]);
        $this->assertSame('utilise', EtatInvitationCompteEtudiant::lire($w)['code']);
    }

    public function test_compte_active_a_priorite_sur_les_autres_signaux(): void
    {
        $w = new ESBTPCandidatureWorkflow([
            'access_activated_at' => now(),
            'activation_token_used_at' => now(),
            'activation_token_expires_at' => now()->subHour(),
        ]);

        $this->assertSame('active', EtatInvitationCompteEtudiant::lire($w)['code']);
    }
}
