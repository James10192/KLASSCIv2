<?php

namespace Tests\Feature\Bts;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * c7 — La prévisualisation de bulletin utilise le résolveur de matières
 * tronc-commun-aware (C10) et refuse les classes LMD.
 *
 * - Une classe LMD passée en preview retourne 422 (séparation BTS/LMD).
 * - Les matières de preview proviennent de l'union TC (incluant le parent) afin que
 *   les overrides manuels (ESBTPResultat) sur ces matières puissent être appliqués.
 * - Les matières inactives sont exclues.
 *
 * BTS uniquement (LMD intouché). DB : klassci_testing (RefreshDatabase).
 */
class BulletinPreviewTcAwareTest extends TestCase
{
    use RefreshDatabase;

    private BtsBulletinSubjectResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(BtsBulletinSubjectResolver::class);
    }

    /** @test */
    public function preview_rejects_lmd_classe_with_422(): void
    {
        $this->withoutMiddleware();
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');
        $this->actingAs($user);

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $classeLmd = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'systeme_academique' => 'LMD',
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();

        $response = $this->get(route('esbtp.bulletins.preview', [
            'etudiant' => $etudiant->id,
            'classe' => $classeLmd->id,
            'annee' => $annee->id,
        ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function preview_subjects_include_tronc_commun_union(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);

        $tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $specialite = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => $tc->id]);

        $matiereTc = ESBTPMatiere::factory()->create(['name' => 'Francais TC', 'is_active' => true]);
        $matiereSpe = ESBTPMatiere::factory()->create(['name' => 'Beton Arme', 'is_active' => true]);

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

        $this->assertContains($matiereTc->id, $ids);
        $this->assertContains($matiereSpe->id, $ids);
    }

    /** @test */
    public function preview_subjects_respect_is_active(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        $active = ESBTPMatiere::factory()->create(['name' => 'Visible', 'is_active' => true]);
        $inactive = ESBTPMatiere::factory()->create(['name' => 'Masquee', 'is_active' => false]);

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
        $this->assertNotContains($inactive->id, $ids);
    }
}
