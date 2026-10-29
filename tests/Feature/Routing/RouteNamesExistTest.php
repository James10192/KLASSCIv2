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
     * L'arriere tolere. Vide, et il doit le rester.
     *
     * Les quarante-quatre noms inscrits ici a la naissance du test ont tous ete
     * traites : les fautes de prefixe corrigees vers la route reelle, les ecrans
     * dont plus aucune route ni aucun controleur ne repondait supprimes.
     *
     * Une entree ajoutee ici est une dette assumee, pas un contournement : elle
     * exige un motif ecrit disant pourquoi la reference reste cassee et ce qui
     * la reparera. Sans ce motif, on corrige l'appel plutot que de l'inscrire.
     */
    private const DETTE_CONNUE = [];

    public function test_tous_les_noms_de_route_ecrits_en_dur_existent(): void
    {
        $connues = collect(Route::getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->flip();

        $familles = implode('|', self::FAMILLES);

        // Les deux styles de guillemets comptent. La moitie du code ecrit
        // route("esbtp.classes.show"), et ne chercher que l'apostrophe laissait
        // cette moitie entierement hors du filet.
        $motif = "/route\(\s*(['\"])(({$familles})\.[A-Za-z0-9_.\-]+)\\1/";

        $cassees = [];

        foreach ($this->fichiersAScanner() as $fichier) {
            $contenu = file_get_contents($fichier);

            if ($contenu === false || ! preg_match_all($motif, $contenu, $trouvailles)) {
                continue;
            }

            // 1 = le guillemet capture pour la backreference, 2 = le nom lui-meme.
            foreach (array_unique($trouvailles[2]) as $nom) {
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
