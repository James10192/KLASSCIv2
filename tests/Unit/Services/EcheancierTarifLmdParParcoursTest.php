<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Services\EcheancierComputationService;
use App\Services\EcheancierPaymentAllocationService;
use App\Services\EcheancierProjectionService;
use App\Services\EcheancierResolverService;
use Mockery;
use Tests\TestCase;

/**
 * Une classe LMD se tarife par parcours. Avant, l'échéancier (et donc la
 * relance) ne cherchait la configuration que par filière + niveau : un
 * étudiant LMD sans souscription retombait sur le montant par défaut de la
 * catégorie, et la relance réclamait un montant que personne n'avait fixé.
 */
class EcheancierTarifLmdParParcoursTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_une_inscription_lmd_prend_le_tarif_de_son_parcours(): void
    {
        $item = $this->premierFrais($this->inscription('LMD', parcoursId: 7), [
            $this->configuration(id: 1, filiereId: 10, parcoursId: null, montant: 400_000),
            $this->configuration(id: 2, filiereId: null, parcoursId: 7, montant: 750_000),
        ]);

        $this->assertSame(750_000.0, $item['amount']);
        $this->assertSame(2, $item['source_id']);
    }

    public function test_sans_configuration_de_parcours_l_ancienne_cle_filiere_reste_valable(): void
    {
        $item = $this->premierFrais($this->inscription('LMD', parcoursId: 7), [
            $this->configuration(id: 1, filiereId: 10, parcoursId: null, montant: 400_000),
        ]);

        $this->assertSame(400_000.0, $item['amount']);
    }

    public function test_une_inscription_bts_ignore_les_tarifs_de_parcours(): void
    {
        $item = $this->premierFrais($this->inscription('BTS', parcoursId: 7), [
            $this->configuration(id: 1, filiereId: 10, parcoursId: null, montant: 400_000),
            $this->configuration(id: 2, filiereId: null, parcoursId: 7, montant: 750_000),
        ]);

        $this->assertSame(400_000.0, $item['amount']);
    }

    /** @param array<int, ESBTPFraisConfiguration> $configurations */
    private function premierFrais(ESBTPInscription $inscription, array $configurations): array
    {
        $resolver = Mockery::mock(EcheancierResolverService::class);
        $resolver->shouldReceive('resolveForConfiguration')->andReturn(null);
        $resolver->shouldReceive('resolveForOptionAssignment')->andReturn(null);
        $resolver->shouldReceive('findBestAssignmentForInscription')->andReturn(null);
        $service = new EcheancierComputationService($resolver, new EcheancierProjectionService(), new EcheancierPaymentAllocationService());

        $categorie = new ESBTPFraisCategory();
        $categorie->id = 1;
        $categorie->name = 'Scolarité';
        $categorie->is_mandatory = true;
        $categorie->payment_deadline_days = 30;
        $categorie->default_amount = 100_000;

        // Groupé comme le font RelanceCalculationService et EcheancierSnapshotService.
        $groupees = collect($configurations)->groupBy(fn ($c) => $c->frais_category_id.'_'.$c->filiere_id.'_'.$c->niveau_id);

        return $service->buildScheduleForInscription($inscription, collect([$categorie]), $groupees, collect())['items'][0];
    }

    private function inscription(string $systeme, int $parcoursId): ESBTPInscription
    {
        $classe = new ESBTPClasse();
        $classe->systeme_academique = $systeme;
        $classe->parcours_id = $parcoursId;

        $inscription = new ESBTPInscription();
        $inscription->id = 99;
        $inscription->filiere_id = 10;
        $inscription->niveau_id = 20;
        $inscription->affectation_status = 'affecté';
        $inscription->date_inscription = '2026-10-01';
        $inscription->setRelation('classe', $classe);

        return $inscription;
    }

    private function configuration(int $id, ?int $filiereId, ?int $parcoursId, float $montant): ESBTPFraisConfiguration
    {
        $c = new ESBTPFraisConfiguration();
        $c->id = $id;
        $c->frais_category_id = 1;
        $c->filiere_id = $filiereId;
        $c->parcours_id = $parcoursId;
        $c->niveau_id = 20;
        $c->is_active = true;
        $c->amount = $montant;
        $c->amount_affecte = $montant;
        $c->payment_deadline_days = 30;

        return $c;
    }
}
