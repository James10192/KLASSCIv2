<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Ce que reçoit quelqu'un dont l'écran n'affiche que des compteurs.
 *
 * La route qui sert le bandeau accepte `academic_health.view_own`, pour que
 * l'enseignant qui saisit voie ce qui manque. Le constat complet porte
 * pourtant, pour CHAQUE évaluation et CHAQUE élève, la note chiffrée et le nom
 * de qui l'a saisie — que seul le tableau de bord du pilotage affiche, et qui
 * exige le droit global.
 */
class SansLesNotesNiLeursAuteursTest extends TestCase
{
    private function service(): AcademicNoteCoverageService
    {
        return (new ReflectionClass(AcademicNoteCoverageService::class))->newInstanceWithoutConstructor();
    }

    private function payload(): array
    {
        return [
            'ok' => true,
            'summary' => ['expected_results' => 20, 'missing_results' => 1, 'actors_count' => 2],
            'subjects' => [[
                'id' => 31,
                'name' => 'Pathologie',
                'statut' => 'partielle',
                'missing_count' => 1,
                'evaluations_count' => 1,
                'enseignant' => ['id' => null, 'name' => 'M TOURE', 'phone' => null, 'source' => 'bulletin'],
                'actor_ids' => [7],
                'actors' => [['id' => 7, 'name' => 'Mme KONE', 'role' => 'saisie']],
                'missing_students' => [['id' => 1444, 'name' => 'KOUASSI AFFOUE GRACE RUCHAMA']],
                'evaluations' => [[
                    'id' => 1526,
                    'students' => [[
                        'student' => ['id' => 1443, 'name' => 'KOUASSI AFFOUE GRACE'],
                        'status' => 'numeric',
                        'note' => 12.0,
                        'created_by' => 'Mme KONE',
                    ]],
                ]],
            ]],
            'incomplete_students' => [['id' => 1444, 'name' => 'KOUASSI AFFOUE GRACE RUCHAMA']],
            // Forme REELLE : `doublonsProbables()` met toujours nom et
            // matricule. Une donnee d'essai a identifiants nus rendait
            // l'assertion « rien de nominatif » vraie sans rien garantir.
            'doublons_probables' => [[
                'a' => ['id' => 1443, 'name' => 'KOUASSI AFFOUE GRACE', 'matricule' => 'FESBTP23-0322'],
                'b' => ['id' => 1444, 'name' => 'KOUASSI AFFOUE GRACE RUCHAMA', 'matricule' => 'FESBTP24-0022'],
            ]],
        ];
    }

    public function test_les_notes_et_leurs_auteurs_disparaissent(): void
    {
        $allege = $this->service()->sansLesNotesNiLeursAuteurs($this->payload());
        $matiere = $allege['subjects'][0];

        $this->assertArrayNotHasKey('evaluations', $matiere);
        $this->assertArrayNotHasKey('missing_students', $matiere);
        $this->assertArrayNotHasKey('actors', $matiere);
        $this->assertArrayNotHasKey('actor_ids', $matiere);

        // Ce qui est reellement garanti : aucune note, et aucun nom de qui
        // l'a saisie ou corrigee. PAS « rien de nominatif » — les doublons
        // gardent leurs noms, et le test suivant le verifie.
        $json = json_encode($allege, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Mme KONE', $json);
        $this->assertStringNotContainsString('"note"', $json);
    }

    /** Ce que le bandeau affiche doit survivre intact. */
    public function test_ce_que_l_ecran_affiche_reste(): void
    {
        $allege = $this->service()->sansLesNotesNiLeursAuteurs($this->payload());
        $matiere = $allege['subjects'][0];

        $this->assertSame('Pathologie', $matiere['name']);
        $this->assertSame('partielle', $matiere['statut']);
        $this->assertSame(1, $matiere['missing_count']);
        $this->assertSame('M TOURE', $matiere['enseignant']['name']);
        $this->assertSame(20, $allege['summary']['expected_results']);
        $this->assertSame($this->payload()['doublons_probables'], $allege['doublons_probables']);
    }

    /**
     * Vide plutôt qu'absente : une clé qui disparaît casse un appelant qui la
     * parcourt, une clé vide non.
     */
    public function test_la_liste_des_eleves_incomplets_reste_presente_mais_vide(): void
    {
        $allege = $this->service()->sansLesNotesNiLeursAuteurs($this->payload());

        $this->assertArrayHasKey('incomplete_students', $allege);
        $this->assertSame([], $allege['incomplete_students']);
    }

    /** Un constat en echec (`ok: false`) n'a pas de `subjects` : rien ne doit casser. */
    public function test_un_constat_vide_traverse_sans_erreur(): void
    {
        $vide = ['ok' => false, 'message' => 'Classe introuvable.'];

        $this->assertSame($vide, $this->service()->sansLesNotesNiLeursAuteurs($vide));
    }

    /**
     * Les doublons gardent noms et matricules, et c'est VOULU.
     *
     * Le bandeau les affiche a qui detient `academic_health.view_own`. Les
     * retirer priverait l'enseignant de la seule information qui explique
     * pourquoi une note « manque » — mais il faut que ce soit ecrit, sinon la
     * prochaine lecture du nom de la methode conclura l'inverse.
     */
    public function test_les_doublons_gardent_noms_et_matricules(): void
    {
        $allege = $this->service()->sansLesNotesNiLeursAuteurs($this->payload());
        $json = json_encode($allege, JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('FESBTP24-0022', $json);
        $this->assertStringContainsString('KOUASSI AFFOUE GRACE RUCHAMA', $json);
    }
}
