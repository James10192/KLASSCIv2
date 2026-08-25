<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\ReeinscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'analyse des decisions de reinscription porte sur l'annee PRECEDENTE : pour
 * reinscrire vers 2025-2026, on regarde les inscriptions de 2024-2025.
 *
 * Une ecole qui demarre n'a pas d'annee precedente. Ce cas doit rendre un
 * resultat vide, pas lever une exception qui casserait la page de
 * reinscription. Ces tests executent le service ; ils remplacent un ancien
 * test qui se contentait de chercher des morceaux de texte dans le fichier
 * source, et qui serait donc reste vert sur du code casse.
 */
class AnalyseDecisionsReinscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La factory des evaluations pointe en dur sur l'utilisateur 1.
        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));
    }

    public function test_sans_annee_precedente_l_analyse_rend_un_resultat_vide_sans_lever_d_exception(): void
    {
        // Une seule annee existe : c'est la premiere rentree de l'ecole.
        $anneeCourante = $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);
        $this->etudiantInscritEn($anneeCourante);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision($anneeCourante->name);

        $this->assertSame(
            ['passages' => [], 'rattrapages' => [], 'redoublements' => [], 'errors' => []],
            $resultat,
            "Sans annee precedente, l'analyse doit rendre les quatre listes vides."
        );
    }

    public function test_avec_une_annee_precedente_l_analyse_parcourt_reellement_les_inscriptions(): void
    {
        // Ce test est le garde-fou du precedent : sans lui, une methode qui
        // renverrait toujours un resultat vide passerait pour correcte.
        $anneePrecedente = $this->annee('2024-2025', '2024-09-01', '2025-07-31');
        $anneeCourante = $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);

        $etudiant = $this->etudiantInscritEn($anneePrecedente);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision($anneeCourante->name);

        $tousLesEtudiants = collect($resultat)
            ->flatten(1)
            ->pluck('etudiant.id')
            ->filter()
            ->all();

        $this->assertContains(
            $etudiant->id,
            $tousLesEtudiants,
            "Un etudiant inscrit l'annee precedente et non reinscrit doit apparaitre dans l'analyse."
        );
    }

    public function test_un_etudiant_deja_reinscrit_est_exclu_de_l_analyse(): void
    {
        $anneePrecedente = $this->annee('2024-2025', '2024-09-01', '2025-07-31');
        $anneeCourante = $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);

        $etudiant = $this->etudiantInscritEn($anneePrecedente);
        // Il a deja son inscription pour l'annee a venir : il n'a plus rien a faire ici.
        $this->etudiantInscritEn($anneeCourante, $etudiant);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision($anneeCourante->name);

        $tousLesEtudiants = collect($resultat)
            ->flatten(1)
            ->pluck('etudiant.id')
            ->filter()
            ->all();

        $this->assertNotContains($etudiant->id, $tousLesEtudiants);
    }

    public function test_sans_annee_courante_l_analyse_refuse_explicitement(): void
    {
        // Aucune annee marquee courante : le service doit le dire, pas rendre
        // un resultat vide qu'on prendrait pour « rien a reinscrire ».
        $this->annee('2024-2025', '2024-09-01', '2025-07-31');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucune année universitaire courante définie');

        app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');
    }

    private function annee(string $nom, string $debut, string $fin, bool $courante = false): ESBTPAnneeUniversitaire
    {
        return ESBTPAnneeUniversitaire::factory()->create([
            'name' => $nom,
            'start_date' => $debut,
            'end_date' => $fin,
            'is_current' => $courante,
        ]);
    }

    private function etudiantInscritEn(ESBTPAnneeUniversitaire $annee, ?ESBTPEtudiant $etudiant = null): ESBTPEtudiant
    {
        $etudiant ??= ESBTPEtudiant::factory()->create();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => true,
        ]);

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return $etudiant;
    }
}
