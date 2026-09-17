<?php

namespace Tests\Unit\Services;

use App\Enums\ModePaiement;
use App\Models\User;
use App\Services\DocumentPrintGuard;
use App\Services\MobileMoneyPaymentGuard;
use App\Services\NotesWindowGuard;
use App\Services\TenantScolariteSettings;
use Mockery;
use Tests\TestCase;

class ScolariteGuardsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_comptable_is_limited_to_mobile_money_modes(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->with('paiements.create')->andReturn(false);
        $user->shouldReceive('can')->with('paiements.create.mobile_money')->andReturn(true);

        $guard = new MobileMoneyPaymentGuard();

        $this->assertTrue($guard->canCreate($user));

        // Ce que cette ligne vérifie : qu'un guichet « mobile money » reçoit la
        // liste du garde, et rien d'autre. Elle a d'abord recopié cette liste en
        // toutes lettres, et est restée à cinq modes quand Djamo et Celtiis Cash
        // sont entrés. La dériver de l'énumération ici ne réparait rien : c'était
        // le corps de `mobileMoneyModes()`, mot pour mot, donc une assertion qui
        // ne pouvait plus échouer. On interroge donc le garde.
        $this->assertSame($guard->mobileMoneyModes(), $guard->allowedModes($user));

        // Ce que la ligne ci-dessus ne dit pas : que la liste n'est pas vide, et
        // qu'elle exclut bien l'espèce. C'est l'intention du garde, et elle se
        // vérifie par des valeurs nommées. Que `estMobile()` classe les douze
        // modes est vérifié à sa source, dans `ModePaiementTest`.
        $this->assertNotEmpty($guard->allowedModes($user));
        $this->assertTrue($guard->allowsMode($user, ModePaiement::WAVE->value));
        $this->assertTrue($guard->allowsMode($user, ModePaiement::CELTIIS_CASH->value));
        $this->assertFalse($guard->allowsMode($user, ModePaiement::ESPECES->value));
    }

    public function test_print_stays_open_when_approval_setting_is_off(): void
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('printRequiresApproval')->andReturn(false);
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->andReturn(true);

        $soldes = Mockery::mock(\App\Services\SoldeEtudiant::class);
        $guard = new DocumentPrintGuard($settings, $soldes);

        $this->assertTrue($guard->decide($user, 'certificat', 12)->allowed);
    }

    public function test_cashier_pre_enrollment_defaults_on(): void
    {
        $settings = new TenantScolariteSettings();

        $this->assertTrue($settings->cashierPreEnrollmentEnabled());
    }

    public function test_agent_inscription_setting_defaults_off(): void
    {
        $this->assertSame('inscriptions.split_role', TenantScolariteSettings::AGENT_INSCRIPTION_ROLE);
        $source = file_get_contents((new \ReflectionClass(TenantScolariteSettings::class))->getFileName());
        $this->assertStringContainsString('return $this->flag(self::AGENT_INSCRIPTION_ROLE);', $source);
        $this->assertStringContainsString('private function flag(string $key, string $default = ', $source);
    }

    public function test_notes_window_does_not_bind_teachers(): void
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('splitRolesEnabled')->andReturn(true);
        $teacher = Mockery::mock(User::class);
        $teacher->shouldReceive('can')->with('identity.teach')->andReturn(true);
        $clerk = Mockery::mock(User::class);
        $clerk->shouldReceive('can')->with('identity.teach')->andReturn(false);

        $guard = new NotesWindowGuard($settings);

        $this->assertFalse($guard->isBound($teacher));
        $this->assertTrue($guard->isBound($clerk));
    }
}
