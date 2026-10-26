<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le bloc `cohorte` du diagnostic doit compter comme le lecteur d'instantanés :
 * soft-deletes respectés, ligne propre prime sur ligne sale, année courante explicite.
 */
class AcademicPilotageDiagnoseCohorteTest extends AcademicPilotageDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('esbtp_annee_universitaires')) {
            Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
                $table->id();
                $table->string('nom')->nullable();
                $table->unsignedSmallInteger('annee_debut')->nullable();
                $table->boolean('is_active')->default(false);
                $table->boolean('is_current')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('esbtp_classes')) {
            Schema::create('esbtp_classes', function (Blueprint $table): void {
                $table->id();
                $table->string('systeme_academique')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('annee_universitaire_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        DB::table('esbtp_annee_universitaires')->insert([
            'id' => 1, 'nom' => '2026-2027', 'annee_debut' => 2026, 'is_active' => true, 'is_current' => true,
        ]);
        // Mêmes colonnes sur chaque ligne : Query\Builder::insert compile les colonnes de la première.
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'systeme_academique' => 'BTS', 'is_active' => true, 'annee_universitaire_id' => 1, 'deleted_at' => null],
            ['id' => 11, 'systeme_academique' => 'BTS', 'is_active' => true, 'annee_universitaire_id' => 1, 'deleted_at' => null],
            ['id' => 12, 'systeme_academique' => 'BTS', 'is_active' => true, 'annee_universitaire_id' => 1, 'deleted_at' => now()],
            ['id' => 13, 'systeme_academique' => 'BTS', 'is_active' => false, 'annee_universitaire_id' => 1, 'deleted_at' => null],
            ['id' => 14, 'systeme_academique' => 'XYZ', 'is_active' => true, 'annee_universitaire_id' => 1, 'deleted_at' => null],
        ]);
    }

    public function test_inscription_supprimee_est_exclue_et_classe_supprimee_reste_lue(): void
    {
        $this->inscription(1, 10);
        $this->inscription(2, 10, deletedAt: now());
        $this->inscription(3, 12);

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(2, $cohorte['couples_cohorte']);
        $this->assertSame(2, $cohorte['sans_ligne']);
        $this->assertSame(0, $cohorte['systeme_non_classable']);
    }

    public function test_cohorte_exclut_statut_workflow_et_autre_annee(): void
    {
        $this->inscription(1, 10);
        $this->inscription(2, 10, status: 'annulee');
        $this->inscription(3, 10, workflow: 'prospect');
        $this->inscription(4, 10, annee: 2);

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(1, $cohorte['couples_cohorte']);
        $this->assertSame(1, $cohorte['sans_ligne']);
    }

    public function test_classe_inactive_reste_dans_la_cohorte_comme_pour_la_sante_de_classe(): void
    {
        $this->inscription(1, 10);
        $this->inscription(2, 13);

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(2, $cohorte['couples_cohorte']);
    }

    public function test_option_classe_ne_compte_que_cette_classe(): void
    {
        $this->inscription(1, 10);
        $this->inscription(2, 11);

        $cohorte = $this->diagnoseCohorte(['--class-id' => 10]);

        $this->assertSame(1, $cohorte['couples_cohorte']);
    }

    public function test_option_classe_vide_vaut_absente(): void
    {
        $this->inscription(1, 10);
        $this->inscription(2, 11);

        $cohorte = $this->diagnoseCohorte(['--class-id' => '']);

        $this->assertSame(2, $cohorte['couples_cohorte']);
    }

    public function test_option_classe_non_numerique_donne_un_motif(): void
    {
        $this->inscription(1, 10);

        $cohorte = $this->diagnoseCohorte(['--class-id' => 'abc']);

        $this->assertNull($cohorte['couples_cohorte']);
        $this->assertStringContainsString('non valide', $cohorte['motif']);
    }

    public function test_periode_invalide_donne_un_motif(): void
    {
        $this->inscription(1, 10);

        $cohorte = $this->diagnoseCohorte(['--period' => 'nimporte']);

        $this->assertNull($cohorte['sans_instantane_exploitable']);
        $this->assertStringContainsString('période', $cohorte['motif']);
    }

    public function test_systeme_non_classable_est_compte_sans_interrompre(): void
    {
        $this->inscription(1, 14);
        $this->inscription(2, 10);

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(1, $cohorte['systeme_non_classable']);
        $this->assertSame(1, $cohorte['sans_ligne']);
    }

    public function test_option_annee_vide_vaut_absente_et_non_numerique_donne_un_motif(): void
    {
        $this->inscription(1, 10);

        $vide = $this->diagnoseCohorte(['--year-id' => '']);
        $this->assertSame(1, $vide['couples_cohorte']);

        $invalide = $this->diagnoseCohorte(['--year-id' => 'abc']);
        $this->assertNull($invalide['couples_cohorte']);
        $this->assertStringContainsString('année', $invalide['motif']);
    }

    public function test_classe_zero_donne_un_motif(): void
    {
        $this->inscription(1, 10);

        $cohorte = $this->diagnoseCohorte(['--class-id' => 0]);

        $this->assertNull($cohorte['couples_cohorte']);
        $this->assertStringContainsString('classe', $cohorte['motif']);
    }

    public function test_les_compteurs_additionnent_les_couples(): void
    {
        $this->inscription(1, 10);
        $this->snapshot(1, 10, isDirty: false, hash: 'propre');
        $this->inscription(2, 10);
        $this->snapshot(2, 10, isDirty: true, hash: 'sale');
        $this->inscription(3, 10);
        $this->inscription(4, 14);

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(
            $cohorte['couples_cohorte'],
            $cohorte['exploitable'] + $cohorte['sans_ligne'] + $cohorte['ligne_sale'] + $cohorte['systeme_non_classable'],
        );
        $this->assertSame(1, $cohorte['exploitable']);
        $this->assertSame(1, $cohorte['ligne_sale']);
        $this->assertSame(1, $cohorte['sans_ligne']);
        $this->assertSame(1, $cohorte['systeme_non_classable']);
    }

    public function test_une_ligne_propre_prime_sur_une_ligne_sale_pour_le_meme_couple(): void
    {
        $this->inscription(1, 10, 'active');
        $this->snapshot(1, 10, isDirty: true, hash: 'sale');
        $this->snapshot(1, 10, isDirty: false, hash: 'propre');

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(1, $cohorte['exploitable']);
        $this->assertSame(0, $cohorte['ligne_sale']);
        $this->assertSame(0, $cohorte['sans_ligne']);
    }

    public function test_seule_une_ligne_sale_est_comptee_sale(): void
    {
        $this->inscription(1, 10, 'active');
        $this->snapshot(1, 10, isDirty: true, hash: 'sale');

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(0, $cohorte['exploitable']);
        $this->assertSame(1, $cohorte['ligne_sale']);
        $this->assertSame(1, $cohorte['sans_instantane_exploitable']);
    }

    public function test_sans_annee_courante_le_diagnostic_le_dit_au_lieu_d_afficher_zero(): void
    {
        DB::table('esbtp_annee_universitaires')->update(['is_active' => false, 'is_current' => false]);

        $cohorte = $this->diagnoseCohorte();

        $this->assertNull($cohorte['annee_universitaire_id']);
        $this->assertNull($cohorte['sans_instantane_exploitable']);
        $this->assertStringContainsString('aucune année', $cohorte['motif']);
    }

    private function inscription(
        int $etudiantId,
        int $classeId,
        string $status = 'active',
        string $workflow = 'etudiant_cree',
        int $annee = 1,
        $deletedAt = null,
    ): void {
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => $etudiantId,
            'classe_id' => $classeId,
            'annee_universitaire_id' => $annee,
            'status' => $status,
            'workflow_step' => $workflow,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }

    private function snapshot(int $etudiantId, int $classeId, bool $isDirty, string $hash): void
    {
        DB::table('esbtp_academic_metric_snapshots')->insert([
            'context_hash' => $hash.'-'.$etudiantId.'-'.$classeId,
            'scope_type' => 'student',
            'scope_id' => $etudiantId,
            'academic_system' => 'BTS',
            'annee_universitaire_id' => 1,
            'semester' => 'semestre1',
            'classe_id' => $classeId,
            'etudiant_id' => $etudiantId,
            'coverage_pct' => 0,
            'confidence_pct' => 0,
            'level' => 'insufficient_data',
            'metrics' => '{}',
            'evidence_hash' => $hash,
            'engine_version' => '1.0.0',
            'is_dirty' => $isDirty,
            'calculated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    private function diagnoseCohorte(array $options = []): array
    {
        Artisan::call('academic-pilotage:diagnose', ['--json' => true] + $options);
        $diagnosis = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        return $diagnosis['cohorte'];
    }
}
