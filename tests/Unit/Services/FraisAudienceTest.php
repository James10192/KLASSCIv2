<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Services\ApplicableFraisResolver;
use App\Services\FraisScopeResolver;
use App\Services\TenantScolariteSettings;
use Tests\TestCase;

class FraisAudienceTest extends TestCase
{
    private function resolver(bool $confirmationEnabled): ApplicableFraisResolver
    {
        $settings = $this->createMock(TenantScolariteSettings::class);
        $settings->method('confirmerStatutEtablissement')->willReturn($confirmationEnabled);

        return new ApplicableFraisResolver(
            $this->createMock(FraisScopeResolver::class),
            $settings,
        );
    }

    public function test_tous_applies_even_to_ancien(): void
    {
        $category = new ESBTPFraisCategory(['audience' => ESBTPFraisCategory::AUDIENCE_TOUS]);

        $this->assertTrue($this->resolver(true)->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
        ));
    }

    public function test_nouveaux_skipped_for_ancien_when_setting_on(): void
    {
        $category = new ESBTPFraisCategory(['audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX]);

        $this->assertFalse($this->resolver(true)->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
        ));
    }

    public function test_nouveaux_applies_to_nouveau_when_setting_on(): void
    {
        $category = new ESBTPFraisCategory(['audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX]);

        $this->assertTrue($this->resolver(true)->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
        ));
    }

    public function test_nouveaux_skipped_for_ancien_even_when_setting_off(): void
    {
        $category = new ESBTPFraisCategory(['audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX]);

        $this->assertFalse($this->resolver(false)->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
        ));
    }

    public function test_nouveaux_applies_when_statut_unknown(): void
    {
        $category = new ESBTPFraisCategory(['audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX]);

        $this->assertTrue($this->resolver(false)->categoryAppliesToStudent($category, null));
    }

    public function test_anciens_applies_only_to_ancien(): void
    {
        $category = new ESBTPFraisCategory(['audience' => ESBTPFraisCategory::AUDIENCE_ANCIENS]);

        $this->assertTrue($this->resolver(true)->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
        ));
        $this->assertFalse($this->resolver(true)->categoryAppliesToStudent(
            $category,
            ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
        ));
        $this->assertFalse($this->resolver(true)->categoryAppliesToStudent($category, null));
    }
}
