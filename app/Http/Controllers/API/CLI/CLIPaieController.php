<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Endpoints CLI pour la paie enseignants (seed démo distant).
 */
class CLIPaieController extends Controller
{
    /**
     * Déclenche la commande paie:seed-demo (taux profs + séances suivant le planning).
     * POST /api/cli/paie/seed-demo
     */
    public function seedDemo(Request $request)
    {
        $params = array_filter([
            '--weeks' => $request->integer('weeks') ?: null,
            '--max-matieres' => $request->integer('max_matieres') ?: null,
            '--dry-run' => $request->boolean('dry_run') ?: null,
        ], fn ($v) => $v !== null);

        $code = Artisan::call('paie:seed-demo', $params);
        $output = Artisan::output();

        return response()->json([
            'success' => $code === 0,
            'exit_code' => $code,
            'output' => $output,
        ], $code === 0 ? 200 : 500);
    }
}
