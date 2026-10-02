<?php

/**
 * Ce que coûte la trace des actions lentes, isolé du reste.
 *
 *   php bin/mesurer-surcout-traces.php [requetes_sql_par_page] [repetitions]
 *
 * Deux coûts seulement varient avec elle : l'écouteur SQL (une addition par
 * requête et par mesure ouverte) et le middleware (ouvrir, puis fermer et
 * comparer aux seuils après la réponse). Le script mesure chacun sur une base
 * sqlite en mémoire, puis les rapporte à une page qui ferait N requêtes. Il
 * n'écrit rien : les seuils sont posés hors d'atteinte.
 */

use App\Domain\Exploitation\TracesLentes\MesuresEnCours;
use App\Http\Middleware\MesureLesRequetesLentes;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.default' => 'bench', 'database.connections.bench' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
DB::statement('create table settings (id integer primary key, key text, value text, is_active int default 1)');
DB::insert("insert into settings (key, value) values ('exploitation.traces_lentes.seuil_ms', '600000'), ('exploitation.traces_lentes.seuil_requetes', '1000000')");

$parPage = (int) ($argv[1] ?? 300);
$reps = (int) ($argv[2] ?? 200);

$page = function () use ($parPage) {
    for ($i = 0; $i < $parPage; $i++) {
        DB::select('select 1');
    }
};

$chrono = function (callable $f) use ($reps): array {
    $f(); // chauffe
    $t = [];
    for ($r = 0; $r < $reps; $r++) {
        $d = hrtime(true);
        $f();
        $t[] = (hrtime(true) - $d) / 1e6;
    }
    sort($t);

    return ['mediane' => $t[intdiv(count($t), 2)], 'p95' => $t[(int) ceil(0.95 * count($t)) - 1]];
};

// 1. La page, mesures fermées : l'écouteur parcourt une liste vide.
config(['app.traces_lentes' => false]);
$sans = $chrono($page);

// 2. La même page sous mesure, via le vrai middleware, fermeture comprise.
config(['app.traces_lentes' => true]);
$avec = $chrono(function () use ($page, $app) {
    $request = Request::create('/esbtp/inscriptions', 'GET');
    $mw = $app->make(MesureLesRequetesLentes::class);
    $reponse = $mw->handle($request, function () use ($page) {
        $page();

        return new Response('ok');
    });
    $app->make(MesureLesRequetesLentes::class)->terminate($request, $reponse);
});

$ecart = $avec['mediane'] - $sans['mediane'];
printf("Page simulée : %d requêtes SQL, %d répétitions\n", $parPage, $reps);
printf("  sans mesure : médiane %.3f ms, p95 %.3f ms\n", $sans['mediane'], $sans['p95']);
printf("  avec mesure : médiane %.3f ms, p95 %.3f ms (dont terminate, après la réponse)\n", $avec['mediane'], $avec['p95']);
printf("  surcoût     : %.3f ms par page, soit %.2f µs par requête SQL\n", $ecart, $ecart * 1000 / max(1, $parPage));
