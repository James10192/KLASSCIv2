<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;

/**
 * Poser le drapeau en base ne suffit pas : encore faut-il que le bulletin lise
 * la BONNE inscription.
 *
 * Les gabarits lisaient $etudiant->inscriptions->first(). La relation n'est pas
 * triee, donc cela rendait l'inscription de plus petit identifiant, c'est-a-dire
 * la toute premiere de l'etudiant. Or une reinscription exige une inscription
 * anterieure : le drapeau ne pouvait donc jamais se trouver sur celle-la, et la
 * mention « Redoublant » restait « Non » pour tout le monde, redoublants inclus.
 *
 * Ces tests verrouillent le fait que le bulletin reçoit l'inscription de la
 * classe et de l'annee affichees.
 */
class RedoublementVisibleSurBulletinTest extends TestCase
{
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $niveau;

    private ESBTPAnneeUniversitaire $anneePassee;

    private ESBTPAnneeUniversitaire $anneeAffichee;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));

        $this->filiere = ESBTPFiliere::factory()->create();
        $this->niveau = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 2eme ANNEE', 'year' => 2]);

        $this->anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'is_current' => false,
        ]);
        $this->anneeAffichee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
        ]);
    }

    public function test_le_bulletin_recoit_l_inscription_de_l_annee_affichee_et_non_la_premiere(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $classe = $this->classe('BTS2 A');

        // La toute premiere inscription, non redoublante : c'est celle que les
        // gabarits lisaient a tort.
        $premiere = $this->inscription($etudiant, $classe, $this->anneePassee, redoublant: false);
        // L'inscription de l'annee affichee, marquee redoublante.
        $courante = $this->inscription($etudiant, $classe, $this->anneeAffichee, redoublant: true);

        $this->assertLessThan(
            $courante->id,
            $premiere->id,
            "Le scenario n'a de sens que si la premiere inscription porte le plus petit identifiant."
        );

        $donnees = $this->payloadBulletin($etudiant, $classe);

        $this->assertArrayHasKey('inscription', $donnees, 'Le bulletin doit recevoir une inscription.');
        $this->assertSame(
            $courante->id,
            $donnees['inscription']?->id,
            "Le bulletin doit recevoir l'inscription de l'annee affichee, pas la premiere de l'etudiant."
        );
        $this->assertTrue(
            (bool) $donnees['inscription']->is_redoublant,
            'La mention « Redoublant » du bulletin doit refleter cette inscription.'
        );
    }

    public function test_un_etudiant_non_redoublant_reste_non_redoublant(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $classe = $this->classe('BTS2 B');
        $this->inscription($etudiant, $classe, $this->anneeAffichee, redoublant: false);

        $donnees = $this->payloadBulletin($etudiant, $classe);

        $this->assertFalse((bool) $donnees['inscription']->is_redoublant);
    }

    private function payloadBulletin(ESBTPEtudiant $etudiant, ESBTPClasse $classe): array
    {
        // Le gabarit exige un bulletin configure pour cette classe.
        $this->seedConfiguredBulletin($etudiant->id, $classe->id, $this->anneeAffichee->id, 'semestre1');

        return app(BulletinService::class)->genererDonneesBulletinPreview(
            $etudiant->id,
            $classe->id,
            $this->anneeAffichee->id,
            'semestre1'
        );
    }

    private function classe(string $nom): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'name' => $nom,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'is_active' => true,
        ]);
    }

    private function inscription(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        bool $redoublant
    ): ESBTPInscription {
        return ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'is_redoublant' => $redoublant,
        ]);
    }
}
