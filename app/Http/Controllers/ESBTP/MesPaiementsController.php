<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPReliquatDetail;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MesPaiementsController extends Controller
{
    /**
     * Afficher la page "Mes Paiements" pour l'étudiant connecté
     */
    public function index(Request $request)
    {
        try {
            // 1. Récupérer l'étudiant connecté
            $user = Auth::user();
            $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

            if (!$etudiant) {
                return redirect()->route('dashboard')->with('error', 'Aucun étudiant associé à ce compte.');
            }

            // 2. Récupérer l'année universitaire courante
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if (!$anneeCourante) {
                return view('etudiants.mes-paiements.index', [
                    'etudiant' => $etudiant,
                    'paiements' => collect([]),
                    'kpiStats' => [
                        'totalFrais' => 0,
                        'totalPaye' => 0,
                        'resteDu' => 0,
                        'tauxPaiement' => 0,
                        'nombrePaiements' => 0,
                    ],
                ]);
            }

            // 3. Récupérer l'inscription de l'année courante avec relations
            $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeCourante->id)
                ->where('status', 'active')
                ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
                ->first();

            if (!$inscription) {
                return view('etudiants.mes-paiements.index', [
                    'etudiant' => $etudiant,
                    'inscription' => null,
                    'anneeCourante' => $anneeCourante,
                    'paiements' => collect([]),
                    'kpiStats' => [
                        'totalFrais' => 0,
                        'totalPaye' => 0,
                        'resteDu' => 0,
                        'tauxPaiement' => 0,
                        'nombrePaiements' => 0,
                    ],
                ])->with('warning', 'Vous n\'avez pas d\'inscription active pour l\'année en cours. Veuillez contacter l\'administration.');
            }

            // 4. Calculer les KPI financiers (même logique que NotificationService)

            // Frais souscrits année courante
            $fraisSouscrits = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                ->where('is_active', true)
                ->get();
            $totalFraisAnnee = $fraisSouscrits->sum('amount');

            // Reliquats entrants années précédentes
            $reliquatsEntrants = ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
                ->actifs()
                ->get();
            $totalReliquats = $reliquatsEntrants->sum('solde_restant');

            // Total attendu
            $totalAttendu = $totalFraisAnnee + $totalReliquats;

            // Total payé (tous les paiements validés)
            $totalPaye = ESBTPPaiement::where('inscription_id', $inscription->id)
                ->where('status', 'validé')
                ->sum('montant');

            // Solde restant
            $soldeRestant = max(0, $totalAttendu - $totalPaye);

            // Taux de paiement
            $tauxPaiement = $totalAttendu > 0
                ? round(($totalPaye / $totalAttendu) * 100, 2)
                : 0;

            // 5. Récupérer tous les paiements de l'inscription courante
            $paiements = ESBTPPaiement::where('inscription_id', $inscription->id)
                ->with(['inscription.classe', 'inscription.anneeUniversitaire', 'validatedBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            // 6. Préparer les stats KPI
            $kpiStats = [
                'totalFrais' => $totalAttendu,
                'totalPaye' => $totalPaye,
                'resteDu' => $soldeRestant,
                'tauxPaiement' => $tauxPaiement,
                'nombrePaiements' => $paiements->count(),
                'nombreValides' => $paiements->where('status', 'validé')->count(),
                'nombreEnAttente' => $paiements->where('status', 'en_attente')->count(),
                'nombreRejetes' => $paiements->where('status', 'rejeté')->count(),
            ];

            return view('etudiants.mes-paiements.index', compact(
                'etudiant',
                'paiements',
                'kpiStats',
                'inscription',
                'anneeCourante'
            ));

        } catch (\Exception $e) {
            Log::error('Erreur mes-paiements.index: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue.');
        }
    }
}
