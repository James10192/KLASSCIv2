<?php

namespace Tests\Unit\Services\Security;

use App\Services\NotesWindowGuard;
use App\Services\PermissionRegistry;
use App\Services\Security\DiagnosticAcces;
use App\Services\Security\VerdictAcces;
use Tests\TestCase;

class DiagnosticAccesTest extends TestCase
{
    private function service(): DiagnosticAcces
    {
        return new DiagnosticAcces(
            new PermissionRegistry(),
            $this->createMock(NotesWindowGuard::class),
        );
    }

    public function test_un_compte_desactive_est_refuse_avant_la_permission(): void
    {
        $verdict = $this->service()->interpreter(false, true, 'Awa', 'Voir les notes');

        $this->assertFalse($verdict->autorise);
        $this->assertSame(VerdictAcces::COMPTE_INACTIF, $verdict->cause);
        $this->assertStringContainsString('désactivé', $verdict->motif);
        $this->assertStringNotContainsString('notes.view', $verdict->motif);
    }

    public function test_une_permission_manquante_parle_francais(): void
    {
        $verdict = $this->service()->interpreter(true, false, 'Awa', 'Voir les notes');

        $this->assertFalse($verdict->autorise);
        $this->assertSame(VerdictAcces::PERMISSION_MANQUANTE, $verdict->cause);
        $this->assertStringContainsString('Voir les notes', $verdict->motif);
        $this->assertStringNotContainsString('notes.view', $verdict->motif);
    }

    public function test_une_fenetre_fermee_se_distingue_d_une_permission(): void
    {
        $verdict = $this->service()->interpreter(true, true, 'Awa', 'Saisir des notes', true);

        $this->assertFalse($verdict->autorise);
        $this->assertSame(VerdictAcces::FENETRE_FERMEE, $verdict->cause);
    }

    public function test_un_droit_ouvert_est_autorise(): void
    {
        $verdict = $this->service()->interpreter(true, true, 'Awa', 'Voir les notes');

        $this->assertTrue($verdict->autorise);
        $this->assertSame(VerdictAcces::OK, $verdict->cause);
    }
}
