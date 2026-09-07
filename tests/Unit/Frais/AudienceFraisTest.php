<?php

namespace Tests\Unit\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Services\ApplicableFraisResolver;
use App\Services\FraisScopeResolver;
use App\Services\TenantScolariteSettings;
use Tests\TestCase;

/**
 * A qui s'applique un frais reserve aux entrants.
 *
 * La regle decide si un etudiant paie, ou non, la tenue. Elle a coute 50 000 F a
 * un ancien d'ISLG en septembre 2026 : son inscription ne portait pas de statut,
 * et un statut absent est lu comme « nouveau ». Ce qui est fige ici, c'est
 * surtout ce comportement-la — pour qu'on ne le decouvre plus par une facture.
 */
class AudienceFraisTest extends TestCase
{
    private ApplicableFraisResolver $resolveur;

    protected function setUp(): void
    {
        parent::setUp();

        // La methode testee ne touche ni la base ni les reglages : elle ne lit que
        // l'audience de la categorie et le statut passe. Les dependances du
        // constructeur ne servent qu'aux autres methodes.
        $this->resolveur = new ApplicableFraisResolver(
            $this->createMock(FraisScopeResolver::class),
            $this->createMock(TenantScolariteSettings::class),
        );
    }

    private function categorie(string $audience): ESBTPFraisCategory
    {
        $categorie = new ESBTPFraisCategory();
        $categorie->audience = $audience;

        return $categorie;
    }

    public function test_un_frais_reserve_aux_entrants_epargne_un_ancien(): void
    {
        $tenue = $this->categorie(ESBTPFraisCategory::AUDIENCE_NOUVEAUX);

        $this->assertFalse($this->resolveur->categoryAppliesToStudent(
            $tenue,
            ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN
        ));
        $this->assertTrue($this->resolveur->categoryAppliesToStudent(
            $tenue,
            ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU
        ));
    }

    public function test_un_statut_absent_est_traite_comme_un_nouvel_arrivant(): void
    {
        // C'EST LE PIEGE. Un champ vide ne veut pas dire « nouveau », il veut dire
        // « on ne sait pas » — mais la regle tranche en faveur du nouveau, donc en
        // faveur de la facturation. Tant qu'il en est ainsi, TOUT chemin qui cree
        // une inscription doit renseigner le statut : le guichet de caisse et le
        // service de reinscription ne le faisaient pas.
        $this->assertTrue($this->resolveur->categoryAppliesToStudent(
            $this->categorie(ESBTPFraisCategory::AUDIENCE_NOUVEAUX),
            null
        ));
    }

    public function test_un_frais_reserve_aux_anciens_epargne_un_entrant(): void
    {
        $categorie = $this->categorie(ESBTPFraisCategory::AUDIENCE_ANCIENS);

        $this->assertTrue($this->resolveur->categoryAppliesToStudent(
            $categorie,
            ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN
        ));
        $this->assertFalse($this->resolveur->categoryAppliesToStudent($categorie, null));
        $this->assertFalse($this->resolveur->categoryAppliesToStudent(
            $categorie,
            ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU
        ));
    }

    public function test_un_frais_sans_restriction_s_applique_a_tout_le_monde(): void
    {
        $categorie = $this->categorie(ESBTPFraisCategory::AUDIENCE_TOUS);

        foreach ([null, '', ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN, ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU] as $statut) {
            $this->assertTrue($this->resolveur->categoryAppliesToStudent($categorie, $statut));
        }
    }
}
