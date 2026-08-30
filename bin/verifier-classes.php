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
 * Les traits (`use MonTrait;` dans un corps de classe) n'y sont pas non plus,
 * et cette absence-la est un vrai trou, pas une exclusion de principe : un
 * trait absent est une fatale au chargement, exactement ce que l'outil existe
 * pour attraper. Il est nomme ici plutot que tu, parce qu'un manque connu et
 * ecrit se comble un jour, tandis qu'un manque tu se prend pour une garantie.
 *
 * Deux resolutions que PHP seul ne fait pas, et qu'il faut donc refaire ici :
 * les alias de facade (`PDF`, `DB`), que seul le demarrage de Laravel installe
 * et qui se relisent dans config/app.php et dans les paquets ; et les classes
 * d'extension nommees dans EXTENSIONS_PHP, signalees a part car leur existence
 * depend du php.ini qui execute l'outil, pas du code qu'il juge.
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
 * depot porte une cinquantaine de noms irresolus anterieurs qui feraient echouer
 * le premier passage sur toute base de code existante. La bascule se prepare :
 * elle supprimera ce fichier, ce qui est une bonne nouvelle pour lui.
 *
 * Sur `app/` entier, il remontait en aout 2026 une cinquantaine de noms
 * irresolus ANTERIEURS a cet outil : des relations Eloquent vers des modeles
 * qui n'ont jamais existe (`ESBTPFraisVariant`, `ESBTPDepense`, `Formation`),
 * et des imports oublies vers des modeles bien presents. Les uns et les autres
 * etaient des 500 en sommeil ; dix etaient sur des routes servies. Ils ont ete
 * traites un par un — creer l'import, corriger le nom, ou supprimer le code
 * mort — et le balayage rend zero depuis.
 *
 * Une part de ces constats venait des defauts de l'outil lui-meme : un `use` de
 * trait lu comme un import ecrasait l'import homonyme (vingt-deux modeles
 * signales a tort sur `Auditable`, le motif le plus courant du depot), les
 * alias de facade n'etaient pas resolus, et un `strrpos` sans antislash
 * mangeait la premiere lettre des imports racine.
 *
 * La mesure se refait a tout moment, et c'est ce qui la rend utile. La revision
 * a citer est celle d'AVANT la reparation — cc27dc91, et non HEAD~1, qui la
 * contient deja et rend donc zero :
 *   git show cc27dc91:bin/verifier-classes.php > bin/_a.php
 *   php bin/_a.php app database routes config ; rm bin/_a.php
 * L'ancienne version crie 35 fois sur un arbre ou la nouvelle ne dit rien —
 * 22 `Auditable`, 9 `PDF`, 3 `DB`, 1 `ZipArchive`, tous demontrables comme
 * faux. Un outil branche en CI qui crie faux sur le motif dominant du depot
 * apprend a ses lecteurs a l'ignorer, et il ne sert alors plus a rien.
 *
 * Une limite demeure, et il faut la connaitre : les fatales de DECLARATION.
 * `App\Exports\NotesClasseMatiereExport` declarait `WithColumnFormatting` sans
 * implementer `columnFormats()` — un contrat d'interface non tenu. PHP refuse
 * une telle classe au moment de la LIER, et ce refus n'est pas rattrapable :
 * il arrete le processus, `catch` compris, donc le balayage entier. Celle-la
 * est corrigee (c'etait aussi un 500 sur `GET .../notes/export-excel`), mais
 * une autre du meme genre arreterait de nouveau l'outil au fichier fautif.
 * C'est une raison de plus de le lancer sur le DIFF, ou l'obstacle est dans ce
 * qu'on vient d'ecrire, donc devant celui qui peut le lever.
 */

require __DIR__.'/../vendor/autoload.php';

/** Noms qui ne designent pas une classe a charger. */
const RESERVES = [
    'self', 'static', 'parent', 'class', 'int', 'float', 'string', 'bool',
    'array', 'object', 'mixed', 'void', 'null', 'true', 'false', 'iterable',
    'callable', 'never', 'fn', 'function',
];

/**
 * Les classes d'extension PHP que le depot utilise.
 *
 * Leur existence depend des extensions chargees par le php.ini qui execute CET
 * outil, pas du code qu'il juge : sans ext-zip, `ZipArchive` parait introuvable
 * ici et resolu sur le serveur. Un verdict qui change avec la machine n'est pas
 * un verdict.
 *
 * La liste est explicite, et c'est voulu. La regle qui semblait plus elegante —
 * « un nom sans antislash appartient a PHP » — est fausse : dans un fichier
 * SANS namespace, tout nom applicatif est nu lui aussi. Elle aurait eteint
 * l'outil sur les 375 migrations, sur routes/ et sur config/, c'est-a-dire sur
 * des fichiers que la CI lui donne vraiment a lire. Une ligne a ajouter de temps
 * en temps coute moins cher qu'un angle mort de quatre cents fichiers.
 */
const EXTENSIONS_PHP = [
    'ZipArchive',
];

/**
 * Le voisin SIGNIFIANT d'un jeton, espaces et commentaires sautes.
 *
 * Sans cela, `extends Foo` ne se voyait pas : le jeton juste avant `Foo` est
 * l'espace, pas le mot-cle. La premiere version de cet outil ratait ainsi
 * extends, implements, instanceof, les types de retour et les proprietes
 * typees — la moitie des positions qu'elle pretendait couvrir.
 *
 * @param  int  $pas  -1 pour remonter, +1 pour avancer
 */
function indexVoisin(array $jetons, int $depuis, int $pas): ?int
{
    $total = count($jetons);

    for ($j = $depuis + $pas; $j >= 0 && $j < $total; $j += $pas) {
        $candidat = $jetons[$j];

        if (is_array($candidat) && in_array($candidat[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        return $j;
    }

    return null;
}

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

    $indexVoisin = static fn (int $depuis, int $pas): ?int => indexVoisin($jetons, $depuis, $pas);

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

    // Les imports se lisent aux JETONS, et a la profondeur d'accolade zero.
    //
    // Un `use` en debut de ligne n'est pas forcement un import : dans un corps
    // de classe, c'est un trait. Une expression reguliere ne les distingue pas,
    // et la confusion ne se contentait pas d'ajouter du bruit — elle ECRASAIT
    // de vrais imports. Le motif Laravel le plus courant du depot suffisait :
    //
    //     use OwenIt\Auditing\Contracts\Auditable;          // l'import
    //     class X extends Model implements Auditable {
    //         use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    //
    // La seconde ligne etait lue comme un import, son dernier segment est
    // « Auditable », et elle remplacait le premier par une chaine qui n'est pas
    // un nom de classe. Resultat : vingt-deux modeles signales a tort, sur une
    // interface parfaitement importee. Un outil qui crie faux sur le motif le
    // plus repandu du depot cesse d'etre lu — et il etait branche en CI.
    // Pourquoi une profondeur, et non « tout ce qui precede la premiere classe
    // est un import » — qui serait plus court : parce qu'un import place APRES
    // une declaration de classe est du PHP legal, et fonctionne (verifie a
    // l'execution). Ce depot n'en contient aucun aujourd'hui : la profondeur
    // defend donc une construction que le langage autorise, pas un cas observe.
    // C'est un filet, et il faut le lire comme tel.
    $jetons = token_get_all($code);
    $profondeur = 0;
    $total = count($jetons);

    for ($i = 0; $i < $total; $i++) {
        $jeton = $jetons[$i];

        // Une accolade ouvrante n'est pas toujours le caractere `{` : dans une
        // chaine interpolee, `"{$x}"` et `"${x}"` s'ouvrent sur un jeton
        // TABLEAU (T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES) mais se ferment
        // sur un `}` brut. Ne compter que le caractere faisait donc descendre
        // le compteur sans jamais le remonter. Mesure sur app + routes + config
        // + database + tests : 338 fichiers sur 1844 finissaient a une
        // profondeur negative, et un corps de classe y repassait pour du niveau
        // fichier — soit exactement le defaut que cette fonction existe pour
        // corriger. Avec les deux jetons comptes, la derive tombe a zero.
        if ($jeton === '{'
            || (is_array($jeton) && in_array($jeton[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
            $profondeur++;

            continue;
        }

        if ($jeton === '}') {
            $profondeur--;

            continue;
        }

        if (! is_array($jeton) || $jeton[0] !== T_USE || $profondeur !== 0) {
            continue;
        }

        // `function () use ($x)` : un `use` de fermeture, pas un import. Une
        // fermeture declaree au niveau fichier en porterait un a profondeur
        // zero ; ce depot n'en a aucun, donc ce garde protege lui aussi une
        // possibilite du langage et non un cas observe. Il se reconnait a la
        // parenthese qui le precede.
        $avantIndex = indexVoisin($jetons, $i, -1);

        if ($avantIndex !== null && $jetons[$avantIndex] === ')') {
            continue;
        }

        // On rassemble le texte jusqu'au `;`. On consomme les accolades d'un
        // import groupe au passage : elles sont equilibrees a l'interieur de ce
        // qu'on avale, donc le compteur reste juste. S'arreter sur `{` sans
        // consommer le `}` correspondant — ce que faisait la version
        // precedente — desequilibrait la profondeur et faisait disparaitre tous
        // les imports suivants.
        $declaration = '';

        for ($j = $i + 1; $j < $total; $j++) {
            if ($jetons[$j] === ';') {
                $i = $j;
                break;
            }

            $declaration .= is_array($jetons[$j]) ? $jetons[$j][1] : $jetons[$j];
        }

        $declaration = trim(preg_replace('/\s+/', ' ', $declaration));

        if ($declaration === '' || str_starts_with($declaration, 'function ') || str_starts_with($declaration, 'const ')) {
            continue;
        }

        // `use Illuminate\Support\{Str, Collection};` — import groupe. On le
        // developpe : le prefixe se distribue sur chaque entree des accolades.
        $entrees = [];

        if (preg_match('/^(.*\\\\)\{(.+)\}$/', $declaration, $groupe) === 1) {
            foreach (explode(',', $groupe[2]) as $membre) {
                $entrees[] = $groupe[1].trim($membre);
            }
        } else {
            $entrees = explode(',', $declaration);
        }

        foreach ($entrees as $entree) {
            $entree = trim($entree);

            if ($entree === '') {
                continue;
            }

            // La table est indexee en minuscules : PHP resout les noms de
            // classe sans egard a la casse, donc `use ...\Pdf;` doit repondre a
            // `PDF::`.
            if (preg_match('/^(.+)\s+as\s+(\S+)$/i', $entree, $alias) === 1) {
                $imports[strtolower($alias[2])] = ltrim(trim($alias[1]), '\\');

                continue;
            }

            // Le nom court est le dernier segment. Attention au cas sans
            // antislash — `use ZipArchive;` — ou strrpos rend `false` : le
            // transtyper en entier donne 0, et `+1` mangeait alors la premiere
            // lettre. L'import s'enregistrait sous « ipArchive », donc le vrai
            // nom n'etait jamais reconnu.
            $dernier = strrpos($entree, '\\');
            $court = $dernier === false ? $entree : substr($entree, $dernier + 1);

            // Un groupe a virgule finale — `use A\{B, C,};` — laisse un membre
            // vide, donc un nom court vide. Une cle vide dans la table des
            // imports n'est jamais consultee, mais elle decrit un etat que le
            // type de retour ne prevoit pas.
            if ($court === '') {
                continue;
            }

            $imports[strtolower($court)] = ltrim($entree, '\\');
        }
    }

    return [$espace, $imports];
}

$chemins = array_slice($argv, 1);

if ($chemins === []) {
    fwrite(STDERR, "Usage : php bin/verifier-classes.php <fichier|dossier>...\n");
    exit(2);
}

/**
 * Les alias de facade, que seul le demarrage de Laravel installe.
 *
 * `PDF::loadView(...)` et `DB::table(...)` ne designent aucune classe declaree :
 * ce sont des raccourcis que l'AliasLoader enregistre au boot. Un outil qui lit
 * des fichiers sans demarrer le framework ne les voit pas et les signale comme
 * introuvables — huit fois dans ce depot, sur du code parfaitement correct.
 *
 * Attention : un alias vit a la RACINE. Il ne sauve un nom nu que dans un
 * fichier SANS namespace. Voir la resolution, plus bas, pour la raison.
 *
 * Ils se lisent pourtant sans rien demarrer, aux deux endroits ou Laravel les
 * declare : le tableau `aliases` de config/app.php, et la cle
 * `extra.laravel.aliases` de chaque paquet installe (c'est de la que vient
 * `PDF`, fourni par barryvdh/laravel-dompdf).
 */
function aliasDeFacades(string $racine): array
{
    $alias = [];

    $config = $racine.'/config/app.php';

    // `require` execute le fichier. Il peut rendre autre chose qu'un tableau,
    // ou lever — et cet echec-la viendrait de la CONFIGURATION, pas du code
    // juge. Le laisser remonter arreterait tout le balayage sur un obstacle
    // hors sujet, ce que ce fichier reproche par ailleurs a `class_exists`.
    // Sans alias, l'outil rend au pire quelques faux positifs ; sans verdict,
    // il ne rend rien.
    if (is_file($config)) {
        try {
            $charge = require $config;
        } catch (\Throwable $e) {
            $charge = null;
        }

        foreach ((is_array($charge) ? ($charge['aliases'] ?? []) : []) as $court => $cible) {
            if (is_string($court) && is_string($cible)) {
                $alias[$court] = ltrim($cible, '\\');
            }
        }
    }

    $installes = $racine.'/vendor/composer/installed.json';

    if (is_file($installes)) {
        $manifeste = json_decode((string) file_get_contents($installes), true);

        foreach ((is_array($manifeste) ? ($manifeste['packages'] ?? $manifeste) : []) as $paquet) {
            foreach (($paquet['extra']['laravel']['aliases'] ?? []) as $court => $cible) {
                if (is_string($court) && is_string($cible)) {
                    $alias[$court] = ltrim($cible, '\\');
                }
            }
        }
    }

    return $alias;
}

$alias = aliasDeFacades(dirname(__DIR__));

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
        // Les noms de classe sont insensibles a la casse en PHP : `use ...\Pdf;`
        // suivi de `PDF::loadView(...)` resout parfaitement. Une table indexee
        // sur la casse exacte raterait l'import et declarerait le nom irresolu.
        $import = $imports[strtolower($nom)] ?? null;

        // Resolution PHP : l'import d'abord, sinon le namespace courant, sinon
        // la racine.
        $candidats = $import !== null
            ? [$import]
            : array_filter([$espace !== '' ? $espace.'\\'.$nom : null, $nom]);

        // Les alias de facade vivent a la RACINE, et PHP ne fait pas de repli
        // global pour les classes : dans `namespace App\Models`, un `DB::raw()`
        // sans import vise `App\Models\DB` et rien d'autre — verifie a
        // l'execution, alias reellement installes. C'est une fatale dormante,
        // et le depot en portait une, que ce garde a fait ressortir :
        // app/Models/ESBTPKPI.php appelait `DB::raw()` sans importer la facade.
        // Elle est corrigee depuis ; l'outil ne la signale donc plus, et c'est
        // le resultat attendu, pas une regression.
        //
        // L'alias n'est donc un candidat que dans deux cas : le fichier n'a pas
        // de namespace, ou le nom est importe nu (`use PDF;`). Une premiere
        // version acceptait aussi le nom sans import dans un fichier namespace,
        // ce qui rendait muet l'outil sur les quarante-trois facades — soit la
        // liste des oublis d'import les plus courants de tout Laravel, et
        // precisement ce qu'il existe pour attraper.
        $aliasApplicable = $espace === ''
            || ($import !== null && ! str_contains($import, '\\'));

        if ($aliasApplicable && isset($alias[$nom])) {
            $candidats[] = $alias[$nom];
        }

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

        if (in_array($nom, EXTENSIONS_PHP, true)) {
            fwrite(STDERR, sprintf(
                // Le prefixe compte : cette ligne n'est PAS un constat, elle ne
                // fait pas echouer, et rien d'autre ne la distingue dans un
                // journal de CI par ailleurs vert.
                "IGNORE  %s:%d  %s : extension PHP non chargee sur cette machine\n",
                $fichier,
                $ligne,
                $nom
            ));

            continue;
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
