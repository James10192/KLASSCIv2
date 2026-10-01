<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Services\Reinscription\BulkReinscriptionService;
use App\Services\Reinscription\ReinscriptionDashboardStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Point d'entree leger de la page de pilotage des reinscriptions.
 *
 * Le controleur historique calcule les compteurs en relisant toute la promotion
 * et les notes etudiant par etudiant. Ce controleur conserve exactement la meme
 * vue/variables mais delegue les compteurs a un read-model traite par lots.
 */
final class ReinscriptionIndexController extends Controller
{
    public function __invoke(
        Request $request,
        ReinscriptionDashboardStats $dashboardStats,
        BulkReinscriptionService $bulk,
    ) {
        try {
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeAcademique = $anneeCourante
                ? $anneeCourante->name
                : date('Y').'-'.(date('Y') + 1);

            $filieres = ESBTPFiliere::where('is_active', true)->get();
            $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();

            $statistiques = $dashboardStats->calculate();

            // Le modal groupe ne doit jamais rendre toute la page indisponible.
            // S'il rencontre une donnee historique incoherente, la page reste
            // consultable et le probleme est journalise pour correction.
            try {
                $bulkEligibleStudents = $bulk->listEligibleStudents();
            } catch (\Throwable $e) {
                $bulkEligibleStudents = collect();
                Log::warning('Reinscription index: liste bulk indisponible', [
                    'error' => $e->getMessage(),
                ]);
            }

            return view('esbtp.reinscription.index', compact(
                'statistiques',
                'anneeAcademique',
                'filieres',
                'niveaux',
                'bulkEligibleStudents'
            ))->withErrors(collect());
        } catch (\Throwable $e) {
            Log::error('Reinscription index: echec du chargement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Dernier filet : si une donnee metier isolee est incorrecte, on
            // rend quand meme la page avec des compteurs vides plutot qu'un 500.
            $anneeAcademique = isset($anneeAcademique)
                ? $anneeAcademique
                : date('Y').'-'.(date('Y') + 1);
            $filieres = $filieres ?? collect();
            $niveaux = $niveaux ?? collect();
            $bulkEligibleStudents = collect();
            $statistiques = [
                'passages' => 0,
                'rattrapages' => 0,
                'redoublements' => 0,
                'valides' => 0,
                'abandons_annee' => 0,
                'abandons_ecole' => 0,
                'errors' => 0,
            ];

            return view('esbtp.reinscription.index', compact(
                'statistiques',
                'anneeAcademique',
                'filieres',
                'niveaux',
                'bulkEligibleStudents'
            ))->withErrors([
                'error' => 'Certaines donnees de reinscription n’ont pas pu etre analysees. La page reste disponible ; le detail a ete journalise.',
            ]);
        }
    }
}
