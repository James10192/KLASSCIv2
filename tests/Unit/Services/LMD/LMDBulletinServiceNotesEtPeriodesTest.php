<?php

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Verrouille deux surfaces du bulletin universitaire (LMD) qu'aucun test ne couvrait :
 * les libelles de periode qui relient une evaluation a son semestre, et les garde-fous
 * du calcul de moyenne d'un element constitutif quand une evaluation est incomplete.
 *
 * Aucune base n'est ouverte : le service est construit avec un profil de regles dont
 * le resolveur de reglages est simule, et recoit des notes deja chargees sous forme
 * d'objets simples (le service ne les lit que par `note`, `is_absent` et `evaluation`).
 *
 * Les scenarios de compensation et de capitalisation vivent dans
 * LMDBulletinServiceCalculTest et LMDBulletinServiceCompensationTest.
 */
class LMDBulletinServiceNotesEtPeriodesTest extends TestCase
{
    /** Construit le service avec les reglages d'une ecole donnee. */
    private function service(array $reglages = []): LMDBulletinService
    {
        $profil = new LmdAcademicRuleProfile(
            fn (string $cle, mixed $defaut = null): mixed => $reglages[$cle] ?? $defaut
        );

        return new LMDBulletinService($profil);
    }

    /**
     * Note d'evaluation minimale, telle que le calcul de moyenne la consomme.
     * `$bareme` et `$coefficient` acceptent null pour eprouver les valeurs de repli.
     */
    private function note(
        float $valeur,
        mixed $bareme = 20,
        mixed $coefficient = 1,
        bool $absent = false
    ): stdClass {
        $evaluation = new stdClass();
        $evaluation->bareme = $bareme;
        $evaluation->coefficient = $coefficient;

        $note = new stdClass();
        $note->note = $valeur;
        $note->is_absent = $absent;
        $note->evaluation = $evaluation;

        return $note;
    }

    /** Note orpheline : l'evaluation a disparu (suppression, jointure incomplete). */
    private function noteSansEvaluation(float $valeur): stdClass
    {
        $note = new stdClass();
        $note->note = $valeur;
        $note->is_absent = false;
        $note->evaluation = null;

        return $note;
    }

    /** Raccourci : seuls les deux derniers arguments portent du sens ici. */
    private function moyenne(Collection $notes, array $reglages = []): ?float
    {
        return $this->service($reglages)->calculerMoyenneECUE(1, 2, 3, 1, 4, $notes);
    }

    // ---------------------------------------------------------------------
    // Libelles de periode : la cle de jointure entre evaluation et semestre
    // ---------------------------------------------------------------------

    /**
     * Les evaluations n'enregistrent pas toutes la periode de la meme facon selon
     * l'ecran qui les a creees. Perdre une de ces formes vide silencieusement le
     * bulletin : les notes existent mais ne sont plus rattachees au semestre.
     */
    public function test_toutes_les_formes_de_libelle_de_periode_sont_reconnues(): void
    {
        $variantes = $this->service()->getPeriodeVariants(3);

        $this->assertSame(
            ['3', 'semestre3', 'S3', 'Semestre 3', 'semestre 3'],
            $variantes
        );
    }

    /** @dataProvider semestresProvider */
    public function test_le_numero_nu_du_semestre_reste_une_forme_acceptee(int $semestre): void
    {
        $variantes = $this->service()->getPeriodeVariants($semestre);

        $this->assertContains((string) $semestre, $variantes);
        $this->assertContains('semestre' . $semestre, $variantes);
        $this->assertContains('Semestre ' . $semestre, $variantes);
    }

    public static function semestresProvider(): array
    {
        return [
            'Licence 1, premier semestre' => [1],
            'Licence 2, quatrieme semestre' => [4],
            'Licence 3, sixieme semestre' => [6],
            'Master 2, dixieme semestre' => [10],
        ];
    }

    public function test_deux_semestres_differents_ne_partagent_aucun_libelle(): void
    {
        $premier = $this->service()->getPeriodeVariants(1);
        $second = $this->service()->getPeriodeVariants(2);

        $this->assertSame([], array_intersect($premier, $second));
    }

    // ---------------------------------------------------------------------
    // Garde-fous du calcul de moyenne d'un element constitutif (ECUE)
    // ---------------------------------------------------------------------

    public function test_une_note_dont_l_evaluation_a_disparu_est_ecartee_du_calcul(): void
    {
        $notes = new Collection([
            $this->note(12, 20, 1),
            $this->noteSansEvaluation(4),
        ]);

        // Seule la note rattachee a une evaluation pese : 12, et non la moyenne des deux (8).
        $this->assertSame(12.0, $this->moyenne($notes));
    }

    public function test_des_notes_toutes_orphelines_laissent_l_element_sans_moyenne(): void
    {
        $notes = new Collection([
            $this->noteSansEvaluation(12),
            $this->noteSansEvaluation(15),
        ]);

        $this->assertNull(
            $this->moyenne($notes),
            'Sans evaluation exploitable, mieux vaut aucune moyenne qu\'une moyenne inventee.'
        );
    }

    public function test_un_bareme_absent_est_traite_comme_une_note_sur_vingt(): void
    {
        $this->assertSame(15.0, $this->moyenne(new Collection([$this->note(15, null, 1)])));
        $this->assertSame(15.0, $this->moyenne(new Collection([$this->note(15, 0, 1)])));
    }

    public function test_un_coefficient_absent_compte_pour_un(): void
    {
        $notes = new Collection([
            $this->note(10, 20, 0),
            $this->note(20, 20, 1),
        ]);

        // Le coefficient nul est ramene a 1 : (10 + 20) / 2 = 15, et non une division par zero.
        $this->assertSame(15.0, $this->moyenne($notes));
    }

    public function test_la_moyenne_est_arrondie_au_centieme(): void
    {
        $notes = new Collection([
            $this->note(10, 20, 1),
            $this->note(15, 20, 2),
        ]);

        // (10 + 30) / 3 = 13.3333... arrondi a 13.33
        $this->assertSame(13.33, $this->moyenne($notes));
    }

    public function test_une_absence_est_comptee_zero_meme_sur_un_bareme_different_de_vingt(): void
    {
        $notes = new Collection([
            $this->note(50, 50, 1),            // 50/50 -> 20/20
            $this->note(50, 50, 1, absent: true), // absent -> 0, sans passer par le bareme
        ]);

        $this->assertSame(10.0, $this->moyenne($notes));
    }

    // ---------------------------------------------------------------------
    // L'ancienne cle de compensation reste honoree
    // ---------------------------------------------------------------------

    /**
     * Une ecole qui avait desactive la compensation avec l'ancienne cle
     * (`lmd_compensation_enabled`) ne doit pas la voir se rallumer toute seule.
     */
    public function test_l_ancienne_cle_de_compensation_desactive_toujours_la_compensation(): void
    {
        $faible = new stdClass();
        $faible->id = 2;
        $faible->moyenne = 8.0;
        $faible->credit = 4;

        $acquise = new stdClass();
        $acquise->id = 1;
        $acquise->moyenne = 14.0;
        $acquise->credit = 6;

        $service = $this->service(['lmd_compensation_enabled' => '0']);

        $this->assertSame(6, $service->appliquerCompensation([$acquise, $faible], 11.6));
    }
}
