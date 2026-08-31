<?php

namespace Tests\Feature\Routing;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tout nom de route ecrit en dur existe-t-il vraiment ?
 *
 * Un nom de route est une chaine, resolue a l'execution. Ni `php -l` ni la
 * compilation des vues ne peuvent dire qu'elle est fausse : le defaut n'apparait
 * que le jour ou quelqu'un emprunte le chemin qui l'utilise.
 *
 * Et il n'apparait pas toujours bruyamment. Un route() dans un service enveloppe
 * de try/catch se contente de journaliser, et la notification n'est jamais
 * envoyee — c'est ce qui privait les responsables d'ISLG des alertes de paiement
 * a valider, trois fois le 31 aout, sans que personne ne s'en apercoive. Un
 * autre suivait un DB::commit() : le bulletin etait cree, l'utilisateur lisait
 * un echec, recommencait, et creait un doublon.
 *
 * Quinze references cassees sur huit noms au moment d'ecrire ce test. Ce n'est
 * pas un oubli isole, c'est une derive : les routes ont ete regroupees sous un
 * prefixe et les appelants n'ont pas suivi.
 */
class RouteNamesExistTest extends TestCase
{
    /**
     * Les repertoires ou l'on ecrit des noms de route.
     */
    private const DOSSIERS = ['app', 'resources/views', 'routes'];

    /**
     * Prefixes de noms qui appartiennent a l'application.
     *
     * On ne balaie pas TOUTE chaine passee a route() : les paquets tiers
     * definissent les leurs, et un nom construit dynamiquement
     * (`route('esbtp.'.$x)`) n'est pas verifiable ici. On se limite donc aux
     * familles metier, ecrites en litteral, ou la derive s'est produite.
     */
    private const FAMILLES = [
        'esbtp', 'paiements', 'etudiants', 'inscriptions', 'bulletins',
        'frais', 'classes', 'notes', 'evaluations', 'reinscription',
    ];

    /**
     * L'arriere deja present le jour ou ce test a ete ecrit.
     *
     * Quarante-quatre noms, tous en esbtp.*, et par familles entieres :
     * fee-categories, salles, partnerships, bons_sortie. Ce sont des ecrans dont
     * les routes ont ete retirees sans que les vues suivent — du code mort qui
     * rendrait un 500 si quelqu'un y arrivait encore.
     *
     * On les inscrit ici plutot que de faire echouer le test des sa naissance :
     * un test rouge en permanence ne protege plus rien, on apprend a l'ignorer.
     * Cette liste rend la dette VISIBLE et empeche qu'elle grossisse — toute
     * NOUVELLE reference cassee fait echouer le test.
     *
     * Elle doit retrecir, jamais grandir. Retirer une entree ici quand on
     * nettoie l'ecran correspondant fait partie du travail.
     */
    private const DETTE_CONNUE = [
        'esbtp.admin.presence.store',
        'esbtp.admin.presence.update',
        'esbtp.admin.update-password',
        'esbtp.admin.update-profile',
        'esbtp.attendances.edit-seance',
        'esbtp.attendances.report',
        'esbtp.attendances.show-seance',
        'esbtp.bons_sortie.index',
        'esbtp.bons_sortie.show',
        'esbtp.bulletins.recalculer',
        'esbtp.comptabilite.bons-sortie.show',
        'esbtp.etudiant.bulletins',
        'esbtp.etudiant.dashboard',
        'esbtp.fee-categories.create',
        'esbtp.fee-categories.destroy',
        'esbtp.fee-categories.edit',
        'esbtp.fee-categories.index',
        'esbtp.fee-categories.rules.destroy',
        'esbtp.fee-categories.rules.edit',
        'esbtp.fee-categories.rules.installments.destroy',
        'esbtp.fee-categories.rules.installments.edit',
        'esbtp.fee-categories.rules.installments.store',
        'esbtp.fee-categories.rules.store',
        'esbtp.fee-categories.rules.update',
        'esbtp.fee-categories.show',
        'esbtp.fee-categories.store',
        'esbtp.fee-categories.update',
        'esbtp.frais.payments',
        'esbtp.partnerships.attach-department',
        'esbtp.partnerships.detach-department',
        'esbtp.partnerships.force-delete',
        'esbtp.partnerships.restore',
        'esbtp.planning-general.configure-avance',
        'esbtp.resultats.dashboard',
        'esbtp.salles.create',
        'esbtp.salles.destroy',
        'esbtp.salles.edit',
        'esbtp.salles.index',
        'esbtp.salles.show',
        'esbtp.salles.store',
        'esbtp.salles.update',
        'esbtp.specialties.force-delete',
        'esbtp.student.profile.store',
        'esbtp.teachers.index',
    ];

    public function test_tous_les_noms_de_route_ecrits_en_dur_existent(): void
    {
        $connues = collect(Route::getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->flip();

        $familles = implode('|', self::FAMILLES);
        $motif = "/route\(\s*'(({$familles})\.[A-Za-z0-9_.\-]+)'/";

        $cassees = [];

        foreach ($this->fichiersAScanner() as $fichier) {
            $contenu = file_get_contents($fichier);

            if ($contenu === false || ! preg_match_all($motif, $contenu, $trouvailles)) {
                continue;
            }

            foreach (array_unique($trouvailles[1]) as $nom) {
                if (! $connues->has($nom) && ! in_array($nom, self::DETTE_CONNUE, true)) {
                    $cassees[] = $nom.'  ('.$this->cheminRelatif($fichier).')';
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($cassees)),
            "Ces noms de route n'existent pas. Une route renommee ou regroupee sous un ".
            "prefixe laisse ses appelants derriere elle, et l'erreur ne se voit qu'a ".
            "l'execution :\n  ".implode("\n  ", array_unique($cassees))
        );
    }

    /**
     * @return iterable<string>
     */
    private function fichiersAScanner(): iterable
    {
        foreach (self::DOSSIERS as $dossier) {
            $racine = base_path($dossier);

            if (! is_dir($racine)) {
                continue;
            }

            $iterateur = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($racine, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterateur as $fichier) {
                if ($fichier->isFile() && in_array($fichier->getExtension(), ['php'], true)) {
                    yield $fichier->getPathname();
                }
            }
        }
    }

    private function cheminRelatif(string $chemin): string
    {
        return strtr(str_replace(base_path().DIRECTORY_SEPARATOR, '', $chemin), [DIRECTORY_SEPARATOR => '/']);
    }
}
