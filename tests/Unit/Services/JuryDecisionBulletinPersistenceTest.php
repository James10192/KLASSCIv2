<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\OfficialDocuments\Services\JuryPvIssuanceGuard;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentService;
use App\Domain\OfficialDocuments\Services\PvNumberSequenceService;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDJury;
use App\Services\JuryDeliberationService;
use App\Services\LMD\LmdDecisionProjectionService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Unit\Domain\OfficialDocuments\OfficialDocumentDatabaseTestCase;

final class JuryDecisionBulletinPersistenceTest extends OfficialDocumentDatabaseTestCase
{
    private JuryDeliberationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new JuryDeliberationService(
            Mockery::mock(OfficialDocumentService::class),
            Mockery::mock(OfficialDocumentIntegrityService::class),
            Mockery::mock(JuryPvIssuanceGuard::class),
            Mockery::mock(PvNumberSequenceService::class),
            new LmdDecisionProjectionService,
        );
    }

    public function test_auto_decision_persists_the_resolved_bulletin_id(): void
    {
        $jury = $this->seedScopedJury();
        DB::table('esbtp_lmd_jury_decisions')->where('id', 1)->update(['bulletin_id' => null]);

        $updated = $this->service->appliquerDecisionsAuto($jury);

        $this->assertSame(1, $updated);
        $this->assertSame(100, DB::table('esbtp_lmd_jury_decisions')->where('id', 1)->value('bulletin_id'));
    }

    public function test_sans_president_aucune_decision_automatique_n_est_ecrite(): void
    {
        $jury = $this->seedScopedJury();
        DB::table('esbtp_lmd_jury_membres')->where('role', 'president')->update(['present' => false]);
        DB::table('esbtp_lmd_jury_decisions')->delete();

        try {
            $this->service->appliquerDecisionsAuto($jury);
            $this->fail('Le quorum aurait dû être exigé.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Quorum non atteint', $e->getMessage());
        }

        $this->assertSame(0, DB::table('esbtp_lmd_jury_decisions')->count());
    }

    public function test_un_dossier_sans_moyenne_est_incomplet_et_non_defere(): void
    {
        $jury = $this->seedScopedJury();
        DB::table('esbtp_lmd_bulletins')->where('id', 100)->update(['moyenne_generale' => null]);
        // Un inscrit sans bulletin entre dans la cohorte et ressort incomplet.
        DB::table('esbtp_etudiants')->insert(['id' => 12, 'matricule' => 'LMD003', 'nom' => 'BAMBA', 'prenoms' => 'Ali']);
        DB::table('esbtp_inscriptions')->insert(['etudiant_id' => 12, 'classe_id' => 1, 'annee_universitaire_id' => 1, 'status' => 'active']);

        $resultat = $this->service->appliquerDecisionsAutoDetaillees($jury);

        $incomplets = collect($resultat['incompletes'])->pluck('etudiant_id')->sort()->values()->all();
        $this->assertSame([10, 12], $incomplets);
        $this->assertNull(DB::table('esbtp_lmd_jury_decisions')->where('etudiant_id', 10)->whereNull('deleted_at')->first());
        $this->assertSame(0, DB::table('esbtp_lmd_jury_decisions')->where('decision', 'defere')->count());
    }

    public function test_override_decision_persists_the_resolved_bulletin_id(): void
    {
        $jury = $this->seedScopedJury();
        DB::table('esbtp_lmd_jury_decisions')->where('id', 2)->update(['bulletin_id' => null]);

        $decision = $this->service->overrideDecision(
            $jury,
            ESBTPEtudiant::query()->findOrFail(11),
            'admis_sous_condition',
            'Validation jury documentee',
            'majorite',
        );

        $this->assertSame(101, $decision->bulletin_id);
        $this->assertSame(101, DB::table('esbtp_lmd_jury_decisions')->where('id', 2)->value('bulletin_id'));
    }

    public function test_un_nouveau_jury_reprend_la_composition_du_dernier_jury_du_parcours(): void
    {
        $precedent = $this->seedScopedJury();
        $nouveau = ESBTPLMDJury::query()->create([
            'annee_universitaire_id' => 1,
            'parcours_id' => $precedent->parcours_id,
            'semestre' => 2,
            'libelle' => 'Jury S2',
            'status' => 'preparation',
        ]);

        $repris = $this->service->reprendreLaDerniereComposition($nouveau);

        $this->assertSame(2, $repris);
        $membres = DB::table('esbtp_lmd_jury_membres')->where('jury_id', $nouveau->id)->orderBy('user_id')->get();
        $this->assertSame([1, 2], $membres->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(['president', 'assesseur'], $membres->pluck('role')->all());
        // Rien de la délibération précédente ne suit : ni signature, ni décision.
        $this->assertSame(0, $membres->whereNotNull('signature_at')->count());
        $this->assertSame(0, DB::table('esbtp_lmd_jury_decisions')->where('jury_id', $nouveau->id)->count());
    }

    public function test_un_compte_desactive_n_est_pas_repris(): void
    {
        $precedent = $this->seedScopedJury();
        DB::table('users')->where('id', 2)->update(['is_active' => false]);
        $nouveau = ESBTPLMDJury::query()->create([
            'annee_universitaire_id' => 1, 'parcours_id' => $precedent->parcours_id, 'libelle' => 'Jury S2', 'status' => 'preparation',
        ]);

        $this->assertSame(1, $this->service->reprendreLaDerniereComposition($nouveau));
    }

    private function seedScopedJury(): ESBTPLMDJury
    {
        $jury = $this->seedIssuableJury();
        DB::table('esbtp_lmd_bulletins')->where('id', 100)->update([
            'moyenne_generale' => 14,
            'credits_capitalises' => 30,
            'credits_totaux' => 30,
        ]);
        DB::table('esbtp_lmd_bulletins')->where('id', 101)->update([
            'moyenne_generale' => 9,
            'credits_capitalises' => 24,
            'credits_totaux' => 30,
        ]);

        return $jury->fresh();
    }
}
