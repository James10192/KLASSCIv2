<?php

namespace Tests\Unit\Attendances;

use PHPUnit\Framework\TestCase;

/**
 * La page des presences a longtemps calcule des agregats que personne n'affichait,
 * dont une boucle de quatre comptages PAR ETUDIANT. Sur une instance a plus de deux
 * mille inscrits, c'etait le seul endroit de la page ou le nombre de requetes
 * croissait avec l'effectif, et le resultat partait a la poubelle.
 *
 * Ce cout ne se voit pas a la relecture : une variable calculee puis jetee ressemble
 * a une variable utile. Le test ancre donc l'invariant des deux cotes a la fois —
 * ni le controleur ne la fabrique, ni la vue ne la lit. Restaurer un seul des deux
 * cotes redonnerait soit le cout sans l'affichage, soit une variable indefinie au
 * rendu ; ce test echoue dans les deux cas.
 */
class PresencesIndexSansCalculMortTest extends TestCase
{
    /** Variables retirees : leur resultat n'etait affiche nulle part. */
    private const VARIABLES_RETIREES = [
        'statsParEtudiant',
        'statsTotal',
        'totalAttendances',
        'attendancesThisMonth',
        'averageAttendanceRate',
        'classesWithAttendance',
        'teachers',
    ];

    /** Variables conservees : la vue les affiche reellement. */
    private const VARIABLES_AFFICHEES = [
        'etudiants',
        'classeStats',
    ];

    public function test_le_controleur_ne_calcule_plus_les_agregats_jamais_affiches(): void
    {
        $index = $this->corpsDeLaMethodeIndex();

        foreach (self::VARIABLES_RETIREES as $variable) {
            $this->assertStringNotContainsString(
                '$' . $variable,
                $index,
                "ESBTPAttendanceController::index() calcule a nouveau \${$variable}, "
                . "que la vue esbtp.attendances.index n'affiche pas."
            );
        }
    }

    public function test_la_vue_ne_lit_aucune_des_variables_retirees(): void
    {
        $vue = $this->sourceDeLaVue();

        foreach (self::VARIABLES_RETIREES as $variable) {
            $this->assertStringNotContainsString(
                '$' . $variable,
                $vue,
                "La vue lit \${$variable}, que le controleur ne fournit plus : "
                . 'variable indefinie au rendu.'
            );
        }
    }

    public function test_les_variables_reellement_affichees_restent_fournies(): void
    {
        $index = $this->corpsDeLaMethodeIndex();
        $vue = $this->sourceDeLaVue();

        foreach (self::VARIABLES_AFFICHEES as $variable) {
            $this->assertStringContainsString(
                "'" . $variable . "'",
                $index,
                "ESBTPAttendanceController::index() ne transmet plus {$variable}, "
                . 'que la vue affiche.'
            );
            $this->assertStringContainsString(
                '$' . $variable,
                $vue,
                "La vue n'affiche plus \${$variable} : le controleur peut cesser de le calculer."
            );
        }
    }

    /**
     * Isole index() du reste du controleur : les autres methodes ont leurs propres
     * variables locales, et certaines portent les memes noms sans le meme cout.
     */
    private function corpsDeLaMethodeIndex(): string
    {
        $source = $this->lire('app/Http/Controllers/ESBTPAttendanceController.php');

        $debut = strpos($source, 'public function index(Request $request)');
        $this->assertNotFalse($debut, 'index() est introuvable dans ESBTPAttendanceController.');

        // La methode suivante commence a la premiere declaration `public function`
        // rencontree apres index().
        $fin = strpos($source, "\n    public function ", $debut + 1);

        return $fin === false
            ? substr($source, $debut)
            : substr($source, $debut, $fin - $debut);
    }

    private function sourceDeLaVue(): string
    {
        return $this->lire('resources/views/esbtp/attendances/index.blade.php');
    }

    /**
     * Chemin relatif a la racine du depot : ce test ne demarre pas l'application,
     * donc base_path() n'est pas disponible.
     */
    private function lire(string $cheminRelatif): string
    {
        $chemin = dirname(__DIR__, 3) . '/' . $cheminRelatif;
        $this->assertFileExists($chemin);

        return (string) file_get_contents($chemin);
    }
}
