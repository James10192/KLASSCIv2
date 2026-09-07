<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\Setting;
use App\Services\Reinscription\DiagnosticPortailReinscription;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\TenantScolariteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Le diagnostic doit nommer LE verrou qui a ferme, pas un verrou plausible.
 *
 * C'est tout son interet : le portail public rend la meme reponse pour cinq
 * causes distinctes, et une scolarite au telephone ne peut pas les separer.
 * Un diagnostic qui se tromperait de cause serait pire que rien — il enverrait
 * corriger une fiche quand c'est un reglage d'annee qui bloque toute l'ecole.
 *
 * Chaque test isole donc UNE cause et verifie qu'aucune autre n'est annoncee.
 */
class DiagnosticPortailReinscriptionTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $anneePassee;

    private ESBTPAnneeUniversitaire $anneeCible;

    private ESBTPClasse $classe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ouvrirLeCanal();

        // SettingsHelper met en cache la valeur lue, y compris l'absence de
        // ligne : purger AVANT d'ecrire laisserait le canal ferme quoi qu'on
        // enregistre ensuite.
        Cache::flush();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $this->anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => false,
        ]);
        $this->anneeCible = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_current' => true,
        ]);

        $this->classe = ESBTPClasse::factory()->create([
            'name' => 'BTS1 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true,
        ]);
    }

    public function test_un_dossier_servi_par_le_portail_est_annonce_comme_trouve(): void
    {
        $etudiant = $this->etudiantAvecAnneePrecedente('MESBTP25-0070', '2007-09-15');

        $rapport = $this->diagnostiquer('MESBTP25-0070', '2007-09-15');

        $this->assertTrue($rapport['trouve']);
        $this->assertTrue($rapport['eligible']);
        $this->assertNull($rapport['cause']);
        $this->assertSame($etudiant->id, $rapport['etudiant']['id']);
        $this->assertSame('BTS1 A', $rapport['inscription_precedente']['classe']);
    }

    public function test_une_date_de_naissance_differente_est_nommee_avec_celle_de_la_base(): void
    {
        $this->etudiantAvecAnneePrecedente('MESBTP25-0070', '2007-09-15');

        $rapport = $this->diagnostiquer('MESBTP25-0070', '2007-09-16');

        $this->assertFalse($rapport['trouve']);
        $this->assertSame(
            DiagnosticPortailReinscription::CAUSE_DATE_NAISSANCE_DIFFERENTE,
            $rapport['cause'],
        );
        // La date en base est ce que la scolarite doit dicter a la famille :
        // sans elle, le diagnostic dit « ce n'est pas ca » sans dire quoi.
        $this->assertStringContainsString('2007-09-15', $rapport['explication']);
    }

    public function test_un_etudiant_sans_annee_anterieure_est_distingue_d_un_matricule_inconnu(): void
    {
        // Entre en 2026-2027 : rien a reinscrire, le portail le refuse — mais
        // pour une raison qui n'a rien a voir avec une faute de saisie.
        $etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => 'MESBTP26-0001', 'date_naissance' => '2008-01-10',
        ]);
        $this->inscrire($etudiant, $this->anneeCible);

        $rapport = $this->diagnostiquer('MESBTP26-0001', '2008-01-10');

        $this->assertFalse($rapport['trouve']);
        $this->assertSame(
            DiagnosticPortailReinscription::CAUSE_AUCUNE_INSCRIPTION_ANTERIEURE,
            $rapport['cause'],
        );
    }

    public function test_une_annee_precedente_sans_date_de_debut_est_signalee_comme_ignoree(): void
    {
        // La panne silencieuse par excellence : l'inscription existe, elle
        // s'affiche partout dans l'application, et le portail la refuse parce
        // que precedantAnnee() ecarte les annees sans date de debut.
        $this->anneePassee->update(['start_date' => null]);

        $this->etudiantAvecAnneePrecedente('MESBTP25-0070', '2007-09-15');

        $rapport = $this->diagnostiquer('MESBTP25-0070', '2007-09-15');

        $this->assertSame(
            DiagnosticPortailReinscription::CAUSE_AUCUNE_INSCRIPTION_ANTERIEURE,
            $rapport['cause'],
        );
        $this->assertFalse($rapport['inscriptions'][0]['anteriorite_utilisable']);
        $this->assertNull($rapport['inscriptions'][0]['annee_start_date']);
    }

    public function test_une_annee_cible_sans_date_de_debut_annonce_une_panne_generale(): void
    {
        $this->anneeCible->update(['start_date' => null]);
        $this->etudiantAvecAnneePrecedente('MESBTP25-0070', '2007-09-15');

        $rapport = $this->diagnostiquer('MESBTP25-0070', '2007-09-15');

        $this->assertSame(
            DiagnosticPortailReinscription::CAUSE_ANNEE_CIBLE_SANS_DATE,
            $rapport['cause'],
        );
        // « TOUS les etudiants » : c'est ce mot qui evite de faire chercher une
        // erreur de saisie pendant qu'une ecole entiere est fermee.
        $this->assertStringContainsString('TOUS', $rapport['explication']);
    }

    public function test_un_matricule_ecrit_autrement_est_propose(): void
    {
        $this->etudiantAvecAnneePrecedente('MESBTP25-0070', '2007-09-15');

        $rapport = $this->diagnostiquer('mesbtp25 70', '2007-09-15');

        $this->assertSame(
            DiagnosticPortailReinscription::CAUSE_MATRICULE_INCONNU,
            $rapport['cause'],
        );
        $this->assertSame(['MESBTP25-0070'], $rapport['matricules_proches']);
    }

    public function test_un_dossier_supprime_est_nomme_comme_tel(): void
    {
        $etudiant = $this->etudiantAvecAnneePrecedente('MESBTP25-0070', '2007-09-15');
        $etudiant->delete();

        $rapport = $this->diagnostiquer('MESBTP25-0070', '2007-09-15');

        $this->assertSame(
            DiagnosticPortailReinscription::CAUSE_ETUDIANT_SUPPRIME,
            $rapport['cause'],
        );
    }

    public function test_le_diagnostic_ne_depose_aucune_demande(): void
    {
        $this->etudiantAvecAnneePrecedente('MESBTP25-0070', '2007-09-15');

        $this->diagnostiquer('MESBTP25-0070', '2007-09-15');

        // Lecture seule : diagnostiquer le portail ne doit jamais remplir la
        // corbeille que la scolarite depouille.
        $this->assertDatabaseCount('esbtp_reinscription_demandes', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnostiquer(string $matricule, ?string $date): array
    {
        return app(DiagnosticPortailReinscription::class)->pour($matricule, $date);
    }

    private function etudiantAvecAnneePrecedente(string $matricule, string $dateNaissance): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => $matricule,
            'nom' => 'KOUASSI',
            'prenoms' => 'Ama Grace',
            'date_naissance' => $dateNaissance,
        ]);

        $this->inscrire($etudiant, $this->anneePassee);

        return $etudiant;
    }

    private function inscrire(ESBTPEtudiant $etudiant, ESBTPAnneeUniversitaire $annee): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
    }

    private function ouvrirLeCanal(): void
    {
        foreach ([
            TenantScolariteSettings::REINSCRIPTION_EN_LIGNE => '1',
            PortailReinscriptionService::REGLAGE_OUVERTURE => '',
            PortailReinscriptionService::REGLAGE_FERMETURE => '',
        ] as $cle => $valeur) {
            Setting::updateOrCreate(['key' => $cle], [
                'value' => $valeur,
                'type' => $cle === TenantScolariteSettings::REINSCRIPTION_EN_LIGNE ? 'boolean' : 'string',
                'group' => 'scolarite',
                // Setting::get filtre sur is_active : sans ce drapeau la ligne
                // existe mais reste invisible, et le defaut s'applique.
                'is_active' => true,
            ]);
        }
    }
}
