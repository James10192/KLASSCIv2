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

        // Dérivé de l'énumération, et NON recopié. La liste était écrite ici en
        // toutes lettres ; quand le garde a cessé de la recopier lui aussi, ce
        // test est devenu la troisième copie — et la seule restée à cinq modes
        // alors que Djamo et Celtiis Cash étaient entrés. Une assertion qui
        // recopie la source qu'elle vérifie ne vérifie qu'elle-même.
        $attendus = array_values(array_map(
            fn (ModePaiement $mode) => $mode->value,
            array_filter(ModePaiement::cases(), fn (ModePaiement $mode) => $mode->estMobile()),
        ));

        $this->assertSame($attendus, $guard->allowedModes($user));

        // Ce que l'assertion dérivée ne peut plus dire toute seule : que la
        // liste n'est pas vide, et qu'elle exclut bien l'espèce. C'est
        // l'intention du garde, et elle se vérifie par des valeurs nommées.
        $this->assertNotEmpty($attendus);
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
