<?php

/**
 * Verifie que les noms de classe NON QUALIFIES d'un fichier PHP se resolvent.
 *
 * Ce qui est couvert, exhaustivement — un outil dont le contrat est plus large
 * que le code rassure a tort, et vaut moins que pas d'outil du tout :
 *
 *   Foo::x()          new Foo           catch (Foo $e)      f(Foo $p)
 *   extends Foo       implements Foo    instanceof Foo      #[Foo]
 *   private Foo $x;   : Foo             ?Foo                Foo|Bar
 *
 * Ce qui ne l'est PAS : les noms construits a l'execution (`$classe::x()`,
 * `app($nom)`), les chaines (`config('...')`, `Foo::class` en litteral dans un
 * tableau), et les noms absolus (`\Foo\Bar`) — ces derniers ne dependant
 * d'aucun `use`, ils ne peuvent pas souffrir de l'oubli qu'on traque.
 *
 * `php -l` ne fait rien de tout cela : il analyse la syntaxe, jamais les noms.
 * Un `use` oublie passe donc le lint, passe la revue de diff — le nom parait
 * correct — et n'echoue qu'a l'execution, avec un « Class not found » non
 * capte. Le `catch` est le pire des cas et figure ci-dessus a ce titre : un
 * `catch` sur une classe absente ne leve rien du tout, il ne correspond
 * simplement jamais, et l'erreur remonte au 500 generique.
 *
 * C'est arrive sur ce depot en aout 2026 : une extraction de classe a laisse
 * `ESBTPCandidature` sans import dans PortailCandidaturePublication. PHP
 * resolvait alors `App\Services\Inscription\ESBTPCandidature`, qui n'existe
 * pas. Le catalogue du portail public rendait 500 pour les six ecoles, et le
 * site vitrine presentait la panne au candidat comme une ecole injoignable.
 *
 * Aucune dependance, aucune base : on lit les jetons du fichier, on reconstruit
 * la table des `use`, et on demande a l'autochargeur si la classe existe.
 *
 * Usage — sur ce qu'on vient d'ecrire, c'est la que ca compte :
 *   php bin/verifier-classes.php $(git diff --name-only HEAD | grep '\.php$')
 *
 * L'integration continue le lance sur le diff de chaque PR (etape « Check
 * unresolved class names » de .github/workflows/laravel-ci.yml). Sans cela, un
 * outil ecrit pour empecher la recidive d'un incident serait protege par la
 * meme discipline humaine qui avait laisse passer cet incident.
 *
 * Pourquoi pas PHPStan, qui couvrirait tout ceci et davantage : il le ferait,
 * et c'est la bonne destination. Deux obstacles concrets pour aujourd'hui —
 * l'installation echoue depuis cette machine (delai reseau depasse), et le
 * depot porte une soixantaine de noms irresolus anterieurs qui feraient echouer
 * le premier passage sur toute base de code existante. La bascule se prepare :
 * elle supprimera ce fichier, ce qui est une bonne nouvelle pour lui.
 *
 * Sur `app/` entier, il remonte une soixantaine de noms irresolus ANTERIEURS a
 * cet outil : des relations Eloquent vers des modeles qui n'ont jamais existe
 * (`ESBTPFraisVariant`, `ESBTPDepense`, `Formation`...), donc des 500 en
 * sommeil sur les chemins qui les appellent. Ce sont de vraies fautes, pas du
 * bruit — mais elles ne se corrigent pas au detour d'un autre chantier :
 * chacune demande de decider si le modele doit naitre ou la relation mourir.
 *
 * Ce balayage-la s'interrompt aujourd'hui sur `App\Exports\
 * NotesClasseMatiereExport`, qui declare `WithColumnFormatting` sans
 * implementer `columnFormats()`. PHP le refuse a la DECLARATION, et cette
 * erreur-la n'est pas rattrapable : elle arrete le processus. Autre faute
 * dormante du meme lot a traiter, et raison de plus de lancer l'outil sur le
 * diff plutot que sur tout le depot.
 */

require __DIR__.'/../vendor/autoload.php';

/** Noms qui ne designent pas une classe a charger. */
const RESERVES = [
    'self', 'static', 'parent', 'class', 'int', 'float', 'string', 'bool',
    'array', 'object', 'mixed', 'void', 'null', 'true', 'false', 'iterable',
    'callable', 'never', 'fn', 'function',
];

/**
 * Les references de classe d'un fichier, avec la ligne ou elles paraissent.
 *
 * On ne s'interesse qu'aux noms NON QUALIFIES : un nom absolu (`\Foo\Bar`) ne
 * depend d'aucun import, et c'est precisement l'oubli d'import qu'on traque.
 *
 * @return array<string, int> nom court => premiere ligne
 */
function referencesDe(string $code): array
{
    $jetons = token_get_all($code);
    $refs = [];
    $total = count($jetons);

    /**
     * Le voisin SIGNIFIANT, espaces et commentaires sautes.
     *
     * Sans cela, `extends Foo` ne se voyait pas : le jeton juste avant `Foo`
     * est l'espace, pas le mot-cle. La premiere version de cet outil ratait
     * ainsi extends, implements, instanceof, les types de retour et les
     * proprietes typees — la moitie des positions qu'elle pretendait couvrir.
     */
    $indexVoisin = static function (int $depuis, int $pas) use ($jetons, $total): ?int {
        for ($j = $depuis + $pas; $j >= 0 && $j < $total; $j += $pas) {
            $candidat = $jetons[$j];

            if (is_array($candidat) && in_array($candidat[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $j;
        }

        return null;
    };

    $voisin = static function (int $depuis, int $pas) use ($jetons, $indexVoisin) {
        $j = $indexVoisin($depuis, $pas);

        return $j === null ? null : $jetons[$j];
    };

    for ($i = 0; $i < $total; $i++) {
        $jeton = $jetons[$i];

        if (! is_array($jeton) || $jeton[0] !== T_STRING) {
            continue;
        }

        $nom = $jeton[1];

        if (in_array(strtolower($nom), RESERVES, true)) {
            continue;
        }

        // Precede d'un antislash ou d'une fleche : nom absolu, ou appel de
        // methode / propriete. Ni l'un ni l'autre ne depend d'un `use`.
        $avant = $voisin($i, -1);

        if (is_array($avant) && in_array($avant[0], [T_NS_SEPARATOR, T_OBJECT_OPERATOR, T_FUNCTION, T_NAMESPACE, T_CONST], true)) {
            continue;
        }

        $apres = $voisin($i, 1);

        // Toutes les positions ou un T_STRING designe une classe.
        //
        // Le `catch` figure ici, et ce n'est pas un detail : c'est le construit
        // que ce depot documente comme son echec silencieux type — un `catch`
        // sur une classe absente ne leve rien, il ne correspond jamais. Un
        // outil qui verifiait `Foo::` et `new Foo` mais pas `catch (Foo $e)`
        // aurait rassure a tort sur exactement le cas le plus couteux.
        //
        // `T_STRING` immediatement suivi d'une variable couvre d'un coup le
        // `catch`, les types de parametre et les proprietes typees — les trois
        // s'ecrivent « Type $x ».
        // Le type de retour se reconnait au `)` qui precede son `:`. Sans cette
        // precision, un `:` suffisant matcherait toute branche de ternaire et
        // tout argument nomme — l'outil signalerait `implode`, `count`, `app`
        // par centaines et cesserait d'etre lu.
        //
        // On remonte d'abord la declaration de type elle-meme : `?`, `|`, `&`
        // et les autres noms d'une union en font partie. La version precedente
        // exigeait le `:` COLLE au nom, donc ne voyait que `: Foo` — pas
        // `: ?Foo`, qui est la forme majoritaire des methodes qui peuvent ne
        // rien rendre, ni `: A|B`. L'en-tete du fichier, lui, les annoncait
        // toutes les trois : un outil qui promet plus qu'il ne verifie rassure
        // a tort, ce qui est precisement ce qu'il existe pour empecher.
        $typeDeRetour = false;

        // `) : nom` reste ambigu : type de retour, ou branche « sinon » d'un
        // ternaire dont la condition finit par une parenthese —
        // `$x ? back()->a() : back()->b()`. Ce qui tranche est ce qui SUIT :
        // un type de retour n'est jamais suivi d'une parenthese ouvrante.
        if ($apres !== '(') {
            $curseur = $indexVoisin($i, -1);

            while ($curseur !== null) {
                $precedent = $jetons[$curseur];
                $partieDuType = $precedent === '?' || $precedent === '|' || $precedent === '&'
                    || (is_array($precedent) && $precedent[0] === T_STRING);

                if (! $partieDuType) {
                    break;
                }

                $curseur = $indexVoisin($curseur, -1);
            }

            if ($curseur !== null && $jetons[$curseur] === ':') {
                $typeDeRetour = $voisin($curseur, -1) === ')';
            }
        }

        $estClasse = (is_array($apres) && in_array($apres[0], [T_DOUBLE_COLON, T_VARIABLE], true))
            || (is_array($avant) && in_array($avant[0], [T_NEW, T_EXTENDS, T_IMPLEMENTS, T_INSTANCEOF, T_ATTRIBUTE], true))
            || $typeDeRetour;

        if ($estClasse && ! isset($refs[$nom])) {
            $refs[$nom] = $jeton[2];
        }
    }

    return $refs;
}

/**
 * La table des imports du fichier : nom court => nom pleinement qualifie.
 *
 * @return array{0: string, 1: array<string, string>} namespace, imports
 */
function contexteDe(string $code): array
{
    $espace = '';
    $imports = [];

    if (preg_match('/^\s*namespace\s+([^;]+);/m', $code, $m) === 1) {
        $espace = trim($m[1]);
    }

    if (preg_match_all('/^\s*use\s+([^;(]+);/m', $code, $tous) !== false) {
        foreach ($tous[1] as $ligne) {
            $ligne = trim($ligne);

            if (str_starts_with($ligne, 'function ') || str_starts_with($ligne, 'const ')) {
                continue;
            }

            if (preg_match('/^(.+)\s+as\s+(\S+)$/i', $ligne, $alias) === 1) {
                $imports[$alias[2]] = ltrim($alias[1], '\\');

                continue;
            }

            $court = substr($ligne, (int) strrpos($ligne, '\\') + 1);
            $imports[$court] = ltrim($ligne, '\\');
        }
    }

    return [$espace, $imports];
}

$chemins = array_slice($argv, 1);

if ($chemins === []) {
    fwrite(STDERR, "Usage : php bin/verifier-classes.php <fichier|dossier>...\n");
    exit(2);
}

$fichiers = [];

foreach ($chemins as $chemin) {
    if (is_file($chemin) && str_ends_with($chemin, '.php')) {
        $fichiers[] = $chemin;

        continue;
    }

    if (! is_dir($chemin)) {
        continue;
    }

    $iterateur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chemin));

    foreach ($iterateur as $entree) {
        if ($entree->isFile() && $entree->getExtension() === 'php') {
            $fichiers[] = $entree->getPathname();
        }
    }
}

$fautes = 0;

foreach ($fichiers as $fichier) {
    $code = (string) file_get_contents($fichier);
    [$espace, $imports] = contexteDe($code);

    foreach (referencesDe($code) as $nom => $ligne) {
        // Resolution PHP : l'import d'abord, sinon le namespace courant, sinon
        // la racine.
        $candidats = isset($imports[$nom])
            ? [$imports[$nom]]
            : array_filter([$espace !== '' ? $espace.'\\'.$nom : null, $nom]);

        foreach ($candidats as $candidat) {
            // `class_exists` CHARGE la classe, donc execute son fichier — avec
            // tout ce qu'un fichier peut faire en s'executant.
            //
            // Ce qui est rattrape ici : ce qui est LEVE pendant le chargement.
            // Un autochargeur qui echoue, un `require` manquant, une exception
            // dans du code de niveau fichier. On note et on continue, parce
            // qu'un outil qui s'arrete au premier obstacle ne dit rien des
            // mille fichiers suivants.
            //
            // Ce qui ne l'est PAS : les fatales de DECLARATION. Une methode
            // abstraite non implementee, une signature incompatible avec le
            // parent — PHP les refuse au moment de lier la classe, et cela
            // n'est pas rattrapable : le processus s'arrete, ce `catch`
            // compris. L'en-tete de ce fichier en cite le cas connu du depot
            // (App\Exports\NotesClasseMatiereExport). C'est la raison pour
            // laquelle l'outil se lance sur un DIFF et non sur `app/` entier :
            // sur le diff, l'obstacle est dans les fichiers qu'on vient
            // d'ecrire, donc devant celui qui peut le lever.
            try {
                $existe = class_exists($candidat)
                    || interface_exists($candidat)
                    || trait_exists($candidat)
                    || enum_exists($candidat);
            } catch (\Throwable $e) {
                fwrite(STDERR, sprintf(
                    "%s:%d  %s : chargement impossible (%s)\n",
                    $fichier,
                    $ligne,
                    $candidat,
                    $e->getMessage()
                ));
                $fautes++;

                continue 2;
            }

            if ($existe) {
                continue 2;
            }
        }

        $fautes++;
        fwrite(STDERR, sprintf(
            "%s:%d  %s introuvable (essaye : %s)\n",
            $fichier,
            $ligne,
            $nom,
            implode(', ', $candidats)
        ));
    }
}

printf("%d fichier(s) verifie(s), %d nom(s) irresolu(s).\n", count($fichiers), $fautes);

exit($fautes === 0 ? 0 : 1);
