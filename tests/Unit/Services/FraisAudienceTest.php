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

    /**
     * Un statut inconnu ne declenche AUCUN frais restreint.
     *
     * La regle disait l'inverse jusqu'en septembre 2026 : « nouveaux » se
     * contentait de « pas ancien », donc un champ vide suffisait a facturer. Un
     * ancien d'ISLG a paye 50 000 F de tenue pour cette seule raison, et il a
     * fallu reventiler son versement pour le lui rendre — une souscription payee
     * ne se retire pas, sous peine de laisser l'argent sans affectation.
     *
     * L'ecole a tranche : mieux vaut sous-facturer que sur-facturer. Un frais
     * oublie se reclame encore ; un frais encaisse a tort, beaucoup moins.
     */
    public function test_aucun_frais_restreint_quand_le_statut_est_inconnu(): void
    {
        foreach ([ESBTPFraisCategory::AUDIENCE_NOUVEAUX, ESBTPFraisCategory::AUDIENCE_ANCIENS] as $audience) {
            $category = new ESBTPFraisCategory(['audience' => $audience]);

            foreach ([null, ''] as $statutInconnu) {
                $this->assertFalse(
                    $this->resolver(false)->categoryAppliesToStudent($category, $statutInconnu),
                    $audience
                );
                $this->assertFalse(
                    $this->resolver(true)->categoryAppliesToStudent($category, $statutInconnu),
                    $audience
                );
            }
        }
    }

    public function test_un_frais_sans_restriction_s_applique_meme_sans_statut(): void
    {
        // La bascule ne doit toucher QUE les audiences restreintes : la scolarite
        // et l'inscription se facturent a tout le monde, statut connu ou non.
        $category = new ESBTPFraisCategory(['audience' => ESBTPFraisCategory::AUDIENCE_TOUS]);

        $this->assertTrue($this->resolver(true)->categoryAppliesToStudent($category, null));
        $this->assertTrue($this->resolver(false)->categoryAppliesToStudent($category, ''));
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
