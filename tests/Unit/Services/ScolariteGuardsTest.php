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

        // DEUX propriétés distinctes, et il faut les deux.
        //
        // 1. Le CONTENU : le guichet mobile offre exactement les modes que
        //    l'énumération classe mobiles. C'est le contrat du garde, et c'est
        //    ce qui rattrape la panne d'origine — une liste écrite en dur qui
        //    oublie un mode neuf. Elle a coûté Djamo et Celtiis Cash une fois
        //    déjà, silencieusement : un mode absent d'ici est REFUSÉ au guichet.
        //
        //    Cette ligne-là a été retirée une fois, au motif qu'elle recopiait
        //    le corps de `mobileMoneyModes()` « donc ne pouvait pas échouer ».
        //    C'était faux : elle pouvait échouer, et elle l'a fait. Ce qu'elle
        //    duplique est l'IMPLÉMENTATION, pas le contrat — et c'est
        //    exactement ce qu'un test de contrat doit faire quand les deux
        //    coïncident. La retirer a laissé le dépôt sans aucun test rouge si
        //    le garde repartait en liste en dur.
        $attendus = array_values(array_map(
            fn (ModePaiement $mode) => $mode->value,
            array_filter(ModePaiement::cases(), fn (ModePaiement $mode) => $mode->estMobile()),
        ));

        $this->assertSame($attendus, $guard->mobileMoneyModes());

        // 2. L'AIGUILLAGE : ce guichet reçoit cette liste-là, et pas une autre.
        //    Distinct du point 1 — une branche dévoyée rendrait les douze modes
        //    sans que le contenu de la liste ait bougé.
        $this->assertSame($guard->mobileMoneyModes(), $guard->allowedModes($user));

        // 3. L'intention, par des valeurs nommées : la liste n'est pas vide,
        //    elle contient les trois modes que des écoles utilisent vraiment,
        //    et elle exclut l'espèce.
        $this->assertNotEmpty($attendus);
        $this->assertTrue($guard->allowsMode($user, ModePaiement::WAVE->value));
        $this->assertTrue($guard->allowsMode($user, ModePaiement::DJAMO->value));
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
