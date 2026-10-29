<?php

namespace Tests\Unit\Http\Controllers;

use PHPUnit\Framework\TestCase;

/**
 * Verrouille le contrat entre DashboardController::superAdminDashboard() et la vue
 * dashboard/superadmin.blade.php, dans les deux sens.
 *
 * Ce qu'il protege. La methode calculait une quarantaine de variables pour une vue
 * qui n'en lit que douze : compteurs de presences, de notes, de bulletins, d'examens,
 * de seances, messages et notifications recents, annonces a venir, statistiques
 * d'assiduite. Sur esbtp-abidjan et esbtp-yakro, qui depassent 2000 inscrits, ce
 * travail etait paye a chaque ouverture du tableau de bord sans jamais atteindre
 * l'ecran. Le retirer se verifie par lecture, mais rien n'empechait ensuite de le
 * reintroduire, ni de supprimer par erreur une variable reellement affichee.
 *
 * Le sens « la vue est servie » est le garde-fou de regression : une variable lue par
 * le gabarit mais plus fournie casse la page. Le sens « rien de superflu » est le
 * garde-fou de derive : il refuse une nouvelle requete dont personne n'affichera le
 * resultat.
 *
 * Le test lit les fichiers et n'ouvre aucune base : il decrit une relation entre deux
 * sources, pas un comportement a l'execution.
 */
class TableauDeBordSuperAdminContratTest extends TestCase
{
    /**
     * Fournie a la vue sans y etre lue, et volontairement conservee : c'est
     * l'utilisateur deja resolu par Auth, donc zero requete supplementaire.
     */
    private const TOLEREES = ['user'];

    private function cheminVue(): string
    {
        return dirname(__DIR__, 4) . '/resources/views/dashboard/superadmin.blade.php';
    }

    private function cheminControleur(): string
    {
        return dirname(__DIR__, 4) . '/app/Http/Controllers/DashboardController.php';
    }

    /**
     * Variables reellement lues par la vue, hors variables de boucle : `$filiere` et
     * `$inscription` sont introduites par les `@foreach`, le controleur n'a pas a les
     * fournir.
     */
    private function variablesLuesParLaVue(): array
    {
        $source = file_get_contents($this->cheminVue());
        $this->assertNotFalse($source, 'La vue dashboard/superadmin.blade.php est illisible.');

        preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $source, $trouvees);
        preg_match_all('/\bas\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/', $source, $boucles);

        $lues = array_diff(array_unique($trouvees[1]), array_unique($boucles[1]));
        sort($lues);

        return array_values($lues);
    }

    /**
     * Corps de superAdminDashboard() seul : les autres tableaux de bord (secretaire,
     * comptable, caissier...) affichent legitimement d'autres chiffres.
     */
    private function corpsDeLaMethode(): string
    {
        $source = file_get_contents($this->cheminControleur());
        $this->assertNotFalse($source, 'DashboardController.php est illisible.');

        $debut = strpos($source, 'private function superAdminDashboard()');
        $this->assertNotFalse($debut, 'superAdminDashboard() est introuvable — methode renommee ?');

        $fin = strpos($source, 'private function secretaireDashboard()', $debut);
        $this->assertNotFalse($fin, 'secretaireDashboard() est introuvable — borne de fin perdue.');

        return substr($source, $debut, $fin - $debut);
    }

    /**
     * Variables que la methode pose dans $data : les affectations `$data['x']` et les
     * clefs du tableau initial `$data = [...]`.
     *
     * Ce tableau initial est lu a part, borne a sa propre fermeture : chercher les
     * `'clef' =>` dans tout le corps ramasserait aussi les clefs internes des tableaux
     * construits pour filiereStats et monthlyStats ('name', 'color', 'month'...), qui
     * ne sont pas des variables de vue.
     */
    private function variablesFourniesParLaMethode(): array
    {
        $corps = $this->corpsDeLaMethode();

        preg_match_all('/\$data\[\'([a-zA-Z_][a-zA-Z0-9_]*)\'\]/', $corps, $affectees);

        $clefsInitiales = [];
        $ouverture = strpos($corps, '$data = [');
        if ($ouverture !== false) {
            $fermeture = strpos($corps, '];', $ouverture);
            $this->assertNotFalse($fermeture, 'Le tableau initial $data n\'est pas referme.');
            $litteral = substr($corps, $ouverture, $fermeture - $ouverture);
            preg_match_all('/\'([a-zA-Z_][a-zA-Z0-9_]*)\'\s*=>/', $litteral, $clefs);
            $clefsInitiales = $clefs[1];
        }

        $fournies = array_unique(array_merge($affectees[1], $clefsInitiales));
        sort($fournies);

        return array_values($fournies);
    }

    public function test_chaque_variable_affichee_est_bien_fournie(): void
    {
        $lues = $this->variablesLuesParLaVue();
        $fournies = $this->variablesFourniesParLaMethode();

        $this->assertNotEmpty($lues, 'Aucune variable detectee dans la vue : le motif de lecture a change.');

        $manquantes = array_diff($lues, $fournies);

        $this->assertSame([], array_values($manquantes), sprintf(
            "La vue dashboard/superadmin lit des variables que superAdminDashboard() ne fournit plus : %s.\n"
            . "La page tombera a l'affichage. Reintroduis le calcul, ou retire l'affichage.",
            implode(', ', $manquantes)
        ));
    }

    public function test_aucune_variable_calculee_sans_etre_affichee(): void
    {
        $lues = $this->variablesLuesParLaVue();
        $fournies = $this->variablesFourniesParLaMethode();

        $superflues = array_diff($fournies, $lues, self::TOLEREES);

        $this->assertSame([], array_values($superflues), sprintf(
            "superAdminDashboard() calcule des variables que personne n'affiche : %s.\n"
            . "Chacune coute au moins une requete a chaque ouverture du tableau de bord, sur des\n"
            . "bases de plus de 2000 inscrits. Affiche-la dans la vue, ou ne la calcule pas.",
            implode(', ', $superflues)
        ));
    }
}
