<?php

namespace Tests\Unit\Paiements;

use App\Domain\Comptabilite\Paiements\Services\IdentiteEncaisseurRecu;
use PHPUnit\Framework\TestCase;

class ReceiptCashierDisplayTest extends TestCase
{
    public function test_tenant_can_choose_full_name_or_initials(): void
    {
        $this->assertSame('Jean Christ Adai', IdentiteEncaisseurRecu::afficher('Jean Christ Adai', 'nom'));
        $this->assertSame('J. C. A.', IdentiteEncaisseurRecu::afficher('Jean Christ Adai', 'initiales'));
        $this->assertSame('É. K.', IdentiteEncaisseurRecu::afficher('Élodie Kouassi', 'initiales'));
        $this->assertSame('J. M. A.', IdentiteEncaisseurRecu::afficher('Jean-Marc Adai', 'initiales'));
        $this->assertSame('—', IdentiteEncaisseurRecu::afficher(null, 'initiales'));
        $this->assertSame('Jean Christ Adai', IdentiteEncaisseurRecu::afficher('Jean Christ Adai', 'non_valide'));
    }

    public function test_both_receipts_use_the_same_setting_and_do_not_change_signatory(): void
    {
        $path = __DIR__.'/../../../resources/views/esbtp/paiements/';
        $preview = file_get_contents($path.'preview.blade.php');
        $printed = file_get_contents($path.'partials/recu-exemplaire.blade.php');

        $this->assertStringContainsString('IdentiteEncaisseurRecu::afficher', $preview);
        $this->assertStringContainsString('IdentiteEncaisseurRecu::afficher', $printed);
        $this->assertStringContainsString('$paiement->signataire', $preview);
        $this->assertStringContainsString('$paiement->signataire', $printed);
        $this->assertStringContainsString('validation_recorded_at', $preview);
        $this->assertStringContainsString('validation_recorded_at', $printed);
    }

    public function test_pdf_and_bulletin_settings_remain_in_one_non_destructive_form(): void
    {
        $view = file_get_contents(__DIR__.'/../../../resources/views/esbtp/settings/index.blade.php');
        $controller = file_get_contents(__DIR__.'/../../../app/Http/Controllers/ESBTP/ESBTPSettingsController.php');

        $this->assertSame(1, substr_count($view, '<form action="{{ route(\'esbtp.settings.update\') }}"'));
        foreach (['identite', 'mise', 'recus', 'apercu'] as $key) {
            $this->assertStringContainsString('data-settings-pane="'.$key.'"', $view);
        }
        foreach (['apparence', 'calculs', 'appreciations', 'scolarite', 'mentions'] as $key) {
            $this->assertStringContainsString('data-settings-pane="'.$key.'"', $view);
        }
        $this->assertStringContainsString('settings-topic-pane[hidden]', $view);
        $this->assertStringContainsString('klassci-settings-main-tab', $view);
        $this->assertStringContainsString('settings.partials._recu-identite', $view);
        $this->assertStringContainsString('ensureReceiptCashierDisplaySetting', $controller);
        $this->assertStringContainsString('IdentiteEncaisseurRecu::options()', $controller);
    }
}
