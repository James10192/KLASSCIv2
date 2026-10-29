<?php

namespace Tests\Unit\Deployment;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * La production met la configuration en cache (`php artisan config:cache`).
 * Dès lors, Laravel ne charge plus le `.env` et `env()` rend null partout
 * hors de config/ : un appel posé dans le code se met à rendre sa valeur par
 * défaut, sans erreur. Ce test échoue dès qu'un tel appel réapparaît.
 *
 * Le remède : une clé dans le fichier config/ le plus proche, lue par
 * `env('X', défaut)`, et `config('…')` dans le code.
 *
 * `getenv()` n'est pas visé : il lit la variable du PROCESSUS, que le cache
 * ne fige pas (`PoulsPlanificateur`, le PATH du CLI de déploiement).
 *
 * Aucune exception n'est admise aujourd'hui. S'il en faut une, l'ajouter à
 * EXCEPTIONS avec la raison.
 */
class AucunEnvHorsDeConfigTest extends TestCase
{
    private const DOSSIERS = ['app', 'routes', 'resources', 'bootstrap', 'database'];

    /** Chemin relatif => raison. */
    private const EXCEPTIONS = [];

    public function test_aucun_appel_env_hors_de_config(): void
    {
        $racine = dirname(__DIR__, 3);
        $fautes = [];

        foreach (self::DOSSIERS as $dossier) {
            $iterateur = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($racine . '/' . $dossier, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterateur as $fichier) {
                if (! $fichier->isFile() || substr($fichier->getFilename(), -4) !== '.php') {
                    continue;
                }

                $relatif = substr($fichier->getPathname(), strlen($racine) + 1);
                if (isset(self::EXCEPTIONS[$relatif])) {
                    continue;
                }

                $source = file_get_contents($fichier->getPathname());
                $lignes = str_ends_with($relatif, '.blade.php')
                    ? self::appelsDansUneVue($source)
                    : self::appelsDansDuPhp($source);

                foreach ($lignes as $ligne) {
                    $fautes[] = $relatif . ':' . $ligne;
                }
            }
        }

        $this->assertSame([], $fautes, "env() hors de config/ rend null sous config:cache. "
            . "Déplacer la lecture dans un fichier config/ et lire config('…') :\n" . implode("\n", $fautes));
    }

    public function test_le_detecteur_voit_un_appel_et_ignore_les_faux_amis(): void
    {
        $php = "<?php\n// env('X') en commentaire\n\$a = env('A');\n\$b = \$o->env('B');\n\$c = getenv('C');\n\$d = \\env('D');\n";
        $this->assertSame([3, 6], self::appelsDansDuPhp($php));

        $vue = "<style>.x { padding: env(safe-area-inset-top); }</style>\n{{ env('APP_NAME') }}\n";
        $this->assertSame([2], self::appelsDansUneVue($vue));
    }

    /** @return int[] numéros de ligne des appels à la fonction globale env() */
    private static function appelsDansDuPhp(string $source): array
    {
        $jetons = array_values(array_filter(
            token_get_all($source),
            fn ($j) => ! is_array($j) || ! in_array($j[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
        ));

        $lignes = [];
        foreach ($jetons as $i => $jeton) {
            $nom = is_array($jeton) && in_array($jeton[0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)
                ? ltrim(strtolower($jeton[1]), '\\')
                : null;

            if ($nom !== 'env' || ($jetons[$i + 1] ?? null) !== '(') {
                continue;
            }

            $precedent = $jetons[$i - 1] ?? null;
            $type = is_array($precedent) ? $precedent[0] : $precedent;
            if (in_array($type, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
                continue;
            }

            $lignes[] = $jeton[2];
        }

        return $lignes;
    }

    /**
     * Une vue mêle PHP et CSS, où `env(safe-area-inset-top)` est légitime.
     * Un appel PHP se reconnaît à sa chaîne entre guillemets.
     *
     * @return int[]
     */
    private static function appelsDansUneVue(string $source): array
    {
        $lignes = [];
        foreach (preg_split('/\R/', $source) as $n => $ligne) {
            if (preg_match('/(?<![\w>:$.-])\\\\?env\(\s*[\'"]/', $ligne)) {
                $lignes[] = $n + 1;
            }
        }

        return $lignes;
    }
}
