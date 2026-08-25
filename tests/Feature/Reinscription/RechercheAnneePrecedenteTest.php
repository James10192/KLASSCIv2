<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * `precedantAnnee` designe l'inscription qui sert de reference au redoublement.
 * Les deux portes d'entree — le service de reinscription et la pre-inscription
 * en caisse — l'appellent : une erreur ici se paie sur un document officiel,
 * dans six ecoles.
 */
class RechercheAnneePrecedenteTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $niveau;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));

        $this->filiere = ESBTPFiliere::factory()->create();
        $this->niveau = ESBTPNiveauEtude::factory()->create();
    }

    public function test_elle_designe_l_annee_chronologiquement_precedente(): void
    {
        $ancienne = $this->annee('2023-2024', '2023-09-01');
        $recente = $this->annee('2024-2025', '2024-09-01');
        $cible = $this->annee('2025-2026', '2025-09-01');

        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $ancienne);
        $attendue = $this->inscrire($etudiant, $recente);

        $this->assertSame(
            $attendue->id,
            ESBTPInscription::precedantAnnee($etudiant->id, $cible)?->id
        );
    }

    public function test_elle_ignore_l_ordre_des_identifiants(): void
    {
        // Configuration reelle d'ESBTP Abidjan : l'annee la plus ancienne porte
        // l'identifiant le plus eleve.
        $cible = $this->annee('2025-2026', '2025-09-01');
        $recente = $this->annee('2024-2025', '2024-09-01');
        $ancienne = $this->annee('2023-2024', '2023-09-01');

        $this->assertGreaterThan($recente->id, $ancienne->id);

        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $ancienne);
        $attendue = $this->inscrire($etudiant, $recente);

        $this->assertSame(
            $attendue->id,
            ESBTPInscription::precedantAnnee($etudiant->id, $cible)?->id,
            "Le tri doit suivre la date de debut, jamais l'identifiant."
        );
    }

    public function test_elle_ignore_l_annee_cible_elle_meme(): void
    {
        // Le cas que le repli precedent rejouait : une reinscription rejouee sur
        // l'annee en cours ne doit pas se prendre elle-meme pour reference.
        $cible = $this->annee('2025-2026', '2025-09-01');
        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $cible);

        $this->assertNull(
            ESBTPInscription::precedantAnnee($etudiant->id, $cible),
            "Sans annee anterieure, il n'y a aucune reference : ni l'annee cible, ni un repli."
        );
    }

    public function test_elle_ignore_les_annees_posterieures(): void
    {
        $cible = $this->annee('2025-2026', '2025-09-01');
        $future = $this->annee('2026-2027', '2026-09-01');

        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $future);

        $this->assertNull(ESBTPInscription::precedantAnnee($etudiant->id, $cible));
    }

    public function test_une_annee_cible_sans_date_est_signalee_et_non_devinee(): void
    {
        // `start_date` est nullable sur cette table. Sans garde, la comparaison
        // SQL serait indeterminee et rendrait « pas de redoublement » en silence.
        $ancienne = $this->annee('2024-2025', '2024-09-01');
        $cible = $this->annee('2025-2026', null);

        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $ancienne);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'sans date de debut'));

        $this->assertNull(ESBTPInscription::precedantAnnee($etudiant->id, $cible));
    }

    public function test_une_annee_anterieure_sans_date_est_ecartee_sans_planter(): void
    {
        $sansDate = $this->annee('annee importee', null);
        $datee = $this->annee('2024-2025', '2024-09-01');
        $cible = $this->annee('2025-2026', '2025-09-01');

        $etudiant = ESBTPEtudiant::factory()->create();
        $this->inscrire($etudiant, $sansDate);
        $attendue = $this->inscrire($etudiant, $datee);

        $this->assertSame(
            $attendue->id,
            ESBTPInscription::precedantAnnee($etudiant->id, $cible)?->id
        );
    }

    public function test_elle_ne_melange_pas_les_etudiants(): void
    {
        $precedente = $this->annee('2024-2025', '2024-09-01');
        $cible = $this->annee('2025-2026', '2025-09-01');

        $unAutre = ESBTPEtudiant::factory()->create();
        $this->inscrire($unAutre, $precedente);

        $etudiant = ESBTPEtudiant::factory()->create();

        $this->assertNull(ESBTPInscription::precedantAnnee($etudiant->id, $cible));
    }

    private function annee(string $nom, ?string $debut): ESBTPAnneeUniversitaire
    {
        return ESBTPAnneeUniversitaire::factory()->create([
            'name' => $nom,
            'start_date' => $debut,
            'end_date' => $debut === null ? null : date('Y-m-d', strtotime($debut.' +10 months')),
            'is_current' => false,
        ]);
    }

    private function inscrire(ESBTPEtudiant $etudiant, ESBTPAnneeUniversitaire $annee): ESBTPInscription
    {
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'is_active' => true,
        ]);

        return ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
    }
}
