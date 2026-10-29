<?php

namespace Tests\Feature\Bts;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * c7 — Les matières utilisées par bulletin store() proviennent du résolveur
 * tronc-commun-aware (C10).
 *
 * Une classe de spécialité (filière fille d'un TC) doit voir, au moment de la
 * génération du bulletin, l'union de ses propres matières et des matières
 * communes définies au niveau de la filière mère (tronc commun). Les matières
 * inactives sont exclues. Sans matière au niveau, on retombe sur le pivot legacy.
 *
 * On exerce ici le collaborateur partagé `BtsBulletinSubjectResolver` que store()
 * délègue désormais — l'assertion porte sur la source des matières du bulletin.
 *
 * BTS uniquement (LMD intouché). DB : klassci_testing (RefreshDatabase).
 */
class BulletinStoreTcAwareTest extends TestCase
{
    use RefreshDatabase;

    private BtsBulletinSubjectResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(BtsBulletinSubjectResolver::class);
    }

    /** @test */
    public function it_unions_tronc_commun_parent_subjects_for_specialite_classe(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);

        $tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $specialite = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => $tc->id]);

        $matiereTc = ESBTPMatiere::factory()->create(['name' => 'Maths Commune TC', 'is_active' => true]);
        $matiereSpe = ESBTPMatiere::factory()->create(['name' => 'Structure Spe', 'is_active' => true]);

        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiereTc->id,
            'filiere_id' => $tc->id,
            'niveau_etude_id' => $niveau->id,
        ]);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiereSpe->id,
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $niveau->id,
            'systeme_academique' => 'BTS',
        ]);
        $classe->load('filiere');

        $ids = $this->resolver->subjectsForClasse($classe)->pluck('id')->all();

        $this->assertContains($matiereTc->id, $ids, 'La matière du tronc commun doit être incluse au bulletin');
        $this->assertContains($matiereSpe->id, $ids, 'La matière de spécialité doit être incluse au bulletin');
    }

    /** @test */
    public function it_excludes_inactive_subjects(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        $active = ESBTPMatiere::factory()->create(['name' => 'Active Matiere', 'is_active' => true]);
        $inactive = ESBTPMatiere::factory()->create(['name' => 'Inactive Matiere', 'is_active' => false]);

        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $active->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $inactive->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'systeme_academique' => 'BTS',
        ]);
        $classe->load('filiere');

        $ids = $this->resolver->subjectsForClasse($classe)->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids, 'Une matière inactive ne doit jamais apparaître au bulletin');
    }

    /** @test */
    public function it_falls_back_to_pivot_when_no_subject_defined_at_niveau(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'systeme_academique' => 'BTS',
        ]);

        // Aucune ligne esbtp_matiere_filiere_niveau → fallback pivot legacy.
        $matierePivot = ESBTPMatiere::factory()->create(['name' => 'Pivot Legacy', 'is_active' => true]);
        $classe->matieres()->attach($matierePivot->id, ['is_active' => true]);
        $classe->load('filiere');

        $ids = $this->resolver->subjectsForClasse($classe)->pluck('id')->all();

        $this->assertContains($matierePivot->id, $ids, 'Le fallback pivot doit ramener les matières attachées directement');
    }
}
