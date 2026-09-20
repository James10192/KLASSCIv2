<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Http\Controllers\AcademicPilotage\AcademicCoverageController;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Deux endroits qui lisaient une periode « a la lettre », et se trompaient.
 *
 * Les periodes ne viennent pas toutes du meme endroit : la requete HTTP porte
 * ce que l'ecran a mis dans l'URL, la base porte ce que les imports y ont
 * ecrit. `AcademicPeriodNormalizer::databaseVariants()` enumere les ecritures
 * reellement rencontrees — « 2 », « S2 », « s2 », « Semestre 2 », « semester2 ».
 * Comparer sans normaliser, c'est se tromper sur la moitie d'entre elles, en
 * silence.
 *
 * Herite de `Tests\TestCase` et non de `PHPUnit\Framework\TestCase` : le repli
 * sur periode inconnue appelle `Log::warning()`, et une facade sans application
 * demarree leve « A facade root has not been set » — ou pire, passe par hasard
 * parce qu'un test Laravel a tourne avant dans le meme processus. Un test dont
 * le resultat depend de l'ordre d'execution ne prouve rien.
 */
class PeriodeCanoniqueTest extends TestCase
{
    private function semestreDeLaCohorte(string $periode): int
    {
        $m = new ReflectionMethod(BtsClassCohortCounter::class, 'semesterNumber');
        $m->setAccessible(true);

        return $m->invoke(new BtsClassCohortCounter(new BtsPhaseResolver(), new AcademicPeriodNormalizer()), $periode);
    }

    /**
     * Le defaut : « S2 » tombait sur le `default` du `match` et rendait 1.
     * Une cohorte de semestre 2 devenait celle du semestre 1 sans un signal —
     * donc, sur une classe de tronc commun, des bulletins generes pour les
     * mauvais etudiants.
     *
     * @dataProvider ecrituresDuSecondSemestre
     */
    public function test_toutes_les_ecritures_du_second_semestre_donnent_2(string $periode): void
    {
        $this->assertSame(2, $this->semestreDeLaCohorte($periode));
    }

    public static function ecrituresDuSecondSemestre(): array
    {
        return [
            ['2'],
            ['S2'],
            ['s2'],
            ['semestre2'],
            ['Semestre 2'],
            ['semester2'],
            ['SEMESTRE 2'],
        ];
    }

    /** @dataProvider ecrituresDuPremierSemestre */
    public function test_toutes_les_ecritures_du_premier_semestre_donnent_1(string $periode): void
    {
        $this->assertSame(1, $this->semestreDeLaCohorte($periode));
    }

    public static function ecrituresDuPremierSemestre(): array
    {
        return [
            ['1'],
            ['S1'],
            ['semestre1'],
            ['Semestre 1'],
        ];
    }

    /**
     * « Annuel » reste le semestre 2 : c'est l'invariant d'exclusivite
     * documente sur `etudiantIdsPourPeriode()`, pas un oubli.
     */
    public function test_annuel_reste_le_second_semestre(): void
    {
        $this->assertSame(2, $this->semestreDeLaCohorte('annuel'));
        $this->assertSame(2, $this->semestreDeLaCohorte('annual'));
    }

    /**
     * Une periode que personne ne reconnait garde l'ancien repli — mais elle
     * n'est plus muette. Le journal EST la correction : sans lui, une cohorte
     * fausse ne laisse aucune trace permettant de la retrouver. Le test le
     * verifie donc, plutot que de se contenter de la valeur de repli.
     */
    public function test_une_periode_inconnue_retombe_sur_le_premier_semestre_et_le_dit(): void
    {
        Log::spy();

        $this->assertSame(1, $this->semestreDeLaCohorte('trimestre 3'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $contexte): bool => str_contains($message, 'période non reconnue')
                && ($contexte['periode'] ?? null) === 'trimestre 3');
    }

    /**
     * La cle de cache et l'invalidation doivent tomber sur la MEME chaine.
     * Avant, une demande `?periode=S1` remplissait `…s1` et l'invalidation
     * oubliait `…semestre1` : l'entree survivait a chaque saisie de note.
     */
    public function test_la_cle_de_cache_ne_depend_pas_de_l_ecriture_de_la_periode(): void
    {
        $canonique = AcademicCoverageController::cle(46, 4, 'semestre1');

        foreach (['S1', 's1', '1', 'Semestre 1', 'semester1'] as $ecriture) {
            $this->assertSame(
                $canonique,
                AcademicCoverageController::cle(46, 4, $ecriture),
                "L'écriture « {$ecriture} » doit donner la même clé que « semestre1 »."
            );
        }
    }

    public function test_la_cle_distingue_toujours_les_semestres_et_l_annuel(): void
    {
        $this->assertNotSame(
            AcademicCoverageController::cle(46, 4, 'S1'),
            AcademicCoverageController::cle(46, 4, 'S2')
        );
        $this->assertNotSame(
            AcademicCoverageController::cle(46, 4, 'S2'),
            AcademicCoverageController::cle(46, 4, 'annuel')
        );
    }

    /** Une periode illisible garde sa forme brute plutot que de toutes les confondre. */
    public function test_une_periode_illisible_garde_sa_forme(): void
    {
        $this->assertSame(
            'pilotage.couverture.46.4.trimestre 3',
            AcademicCoverageController::cle(46, 4, 'Trimestre 3')
        );
    }
}
