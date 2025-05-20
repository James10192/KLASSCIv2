<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ESBTPClasseController;
use App\Models\ESBTPEmploiTemps;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes API pour ESBTP
Route::get('/classes/{classe}/matieres', [ESBTPClasseController::class, 'getMatieresForApi'])
    ->name('api.classes.matieres');

// Route pour vérifier l'existence d'un emploi du temps
Route::get('/check-emploi-temps/{id}', function ($id) {
    try {
        // Vérifier avec DB::table pour éviter les problèmes de modèle
        $emploiTempsDB = DB::table('esbtp_emploi_temps')->where('id', $id)->first();

        // Vérifier avec le modèle Eloquent
        $emploiTemps = ESBTPEmploiTemps::find($id);

        // Vérifier avec withTrashed pour voir si l'emploi du temps a été soft-deleted
        $emploiTempsWithTrashed = ESBTPEmploiTemps::withTrashed()->find($id);

        return response()->json([
            'exists' => $emploiTemps !== null,
            'id' => $id,
            'details' => [
                'db_table_exists' => $emploiTempsDB !== null,
                'eloquent_exists' => $emploiTemps !== null,
                'with_trashed_exists' => $emploiTempsWithTrashed !== null,
                'is_soft_deleted' => $emploiTempsWithTrashed && $emploiTempsWithTrashed->deleted_at !== null,
                'db_table_data' => $emploiTempsDB,
                'eloquent_data' => $emploiTemps ? [
                    'id' => $emploiTemps->id,
                    'classe_id' => $emploiTemps->classe_id,
                    'annee_universitaire_id' => $emploiTemps->annee_universitaire_id,
                    'is_active' => $emploiTemps->is_active,
                    'deleted_at' => $emploiTemps->deleted_at,
                ] : null,
                'with_trashed_data' => $emploiTempsWithTrashed ? [
                    'id' => $emploiTempsWithTrashed->id,
                    'classe_id' => $emploiTempsWithTrashed->classe_id,
                    'annee_universitaire_id' => $emploiTempsWithTrashed->annee_universitaire_id,
                    'is_active' => $emploiTempsWithTrashed->is_active,
                    'deleted_at' => $emploiTempsWithTrashed->deleted_at,
                ] : null,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'exists' => false,
            'id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('api.check-emploi-temps');

// Routes pour le calcul des absences
Route::prefix('absences')->group(function () {
    Route::post('/calculer', 'App\Http\Controllers\ESBTPCalculAbsencesController@calculerAbsencesEtudiant');
    Route::post('/resume-par-seance', 'App\Http\Controllers\ESBTPCalculAbsencesController@resumeAbsencesParSeance');
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Attendance sync route
    Route::post('/attendance/sync', [App\Http\Controllers\ESBTP\Api\AttendanceSyncController::class, 'sync'])
        ->name('api.attendance.sync');
});
