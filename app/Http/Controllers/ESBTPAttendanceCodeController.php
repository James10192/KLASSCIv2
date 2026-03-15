<?php

namespace App\Http\Controllers;

use App\Models\ESBTPDailyCode;
use App\Models\ESBTPEnseignant;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPSeanceCours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ESBTPAttendanceCodeController extends Controller
{
    /**
     * Affiche la page de génération de code
     */
    public function index()
    {
        $this->authorize('generate-attendance-codes');

        $activeCodes = ESBTPDailyCode::with(['generator', 'seance.matiere', 'seance.classe', 'seance.teacher'])
            ->where('status', 'active')
            ->where('valid_until', '>', now())
            ->get();
            
        // Pour compatibilité avec la vue existante, prendre le premier
        $activeCode = $activeCodes->first();

        $recentCodes = ESBTPDailyCode::with(['generator', 'seance.matiere', 'seance.classe', 'seance.teacher'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Récupérer les séances à venir pour la sélection
        $today = Carbon::now();
        $seancesAVenir = ESBTPSeanceCours::with(['matiere', 'classe', 'teacher', 'emploiTemps'])
            ->whereNotNull('matiere_id')
            ->whereNotNull('classe_id')
            ->orderBy('heure_debut')
            ->limit(20)
            ->get();

        return view('esbtp.attendance.generate-code', compact('activeCode', 'activeCodes', 'recentCodes', 'seancesAVenir'));
    }

    /**
     * Génère un nouveau code d'émargement
     */
    public function generate(Request $request)
    {
        $this->authorize('generate-attendance-codes');

        $request->validate([
            'description' => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|integer|min:5|max:1440',
            'seance_id' => 'nullable|exists:esbtp_seances_cours,id'
        ]);

        try {
            // Log de debug : ID utilisateur
            Log::debug('Tentative de génération de code', [
                'user_id' => Auth::id(),
                'user' => Auth::user(),
                'seance_id' => $request->seance_id
            ]);

            // Logique d'invalidation intelligente - TOUJOURS invalider les codes du même type
            if ($request->seance_id) {
                // Si c'est pour une séance spécifique, invalider TOUS les codes pour cette séance
                ESBTPDailyCode::where('status', 'active')
                    ->where('seance_id', $request->seance_id)
                    ->update(['status' => 'expired']);
            } else {
                // Si c'est un code général, invalider TOUS les codes généraux actifs
                ESBTPDailyCode::where('status', 'active')
                    ->whereNull('seance_id')
                    ->update(['status' => 'expired']);
            }

            // Paramètres du code
            $description = $request->description;
            $durationMinutes = $request->duration_minutes ?? 60; // 1 heure par défaut
            $seanceId = $request->seance_id;

            // Générer un nouveau code avec les paramètres
            $code = ESBTPDailyCode::createDailyCode($description, $durationMinutes, $seanceId);

            Log::info('Nouveau code d\'émargement généré', [
                'code' => $code->code,
                'created_by' => Auth::id(),
                'seance_id' => $seanceId,
                'description' => $description,
                'duration_minutes' => $durationMinutes
            ]);

            $message = 'Nouveau code généré avec succès : ' . $code->code;
            if ($description) {
                $message .= ' (' . $description . ')';
            }

            return redirect()->route('esbtp.attendance-codes.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'user' => Auth::user()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la génération du code : ' . $e->getMessage());
        }
    }

    /**
     * Valide un code d'émargement
     */
    public function validate(Request $request, array $rules = [], array $messages = [], array $customAttributes = [])
    {
        if (!empty($rules)) {
            return parent::validate($request, $rules, $messages, $customAttributes);
        }
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        try {
            $code = ESBTPDailyCode::where('code', strtoupper($request->code))
                ->where('status', 'active')
                ->where('valid_until', '>', now())
                ->first();

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code invalide ou expiré'
                ], 400);
            }

            // Vérifier le nombre de tentatives
            if ($code->failed_attempts >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre maximum de tentatives atteint'
                ], 400);
            }

            // Mettre à jour les compteurs de tentatives
            $code->total_attempts++;
            $code->last_attempt_at = now();

            if ($code->isValid()) {
                $code->successful_attempts++;
                $code->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Code valide'
                ]);
            } else {
                $code->failed_attempts++;
                $code->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Code invalide'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation du code', [
                'error' => $e->getMessage(),
                'code' => $request->code
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du code'
            ], 500);
        }
    }

    /**
     * Invalide un code d'émargement
     */
    public function invalidate($id)
    {
        $this->authorize('generate-attendance-codes');

        try {
            $code = ESBTPDailyCode::findOrFail($id);
            $code->status = 'cancelled';
            $code->save();

            Log::info('Code d\'émargement invalidé', [
                'code_id' => $id,
                'invalidated_by' => Auth::id()
            ]);

            return redirect()->back()
                ->with('success', 'Code invalidé avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'invalidation du code', [
                'error' => $e->getMessage(),
                'code_id' => $id
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'invalidation du code');
        }
    }

    /**
     * Nettoie les codes multiples actifs - garde seulement le plus récent
     */
    public function cleanupDuplicates()
    {
        $this->authorize('generate-attendance-codes');

        try {
            // Nettoyer les codes généraux multiples (sans seance_id)
            $generalCodes = ESBTPDailyCode::where('status', 'active')
                ->whereNull('seance_id')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($generalCodes->count() > 1) {
                // Garder le plus récent, invalider les autres
                $recentCode = $generalCodes->first();
                $generalCodes->slice(1)->each(function($code) {
                    $code->update(['status' => 'expired']);
                });
                
                $invalidated = $generalCodes->count() - 1;
                Log::info('Codes généraux dupliqués nettoyés', [
                    'kept_code' => $recentCode->code,
                    'invalidated_count' => $invalidated
                ]);
            }

            return redirect()->back()
                ->with('success', 'Nettoyage terminé. Codes dupliqués supprimés.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage des codes', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors du nettoyage des codes');
        }
    }

    /**
     * Affiche les statistiques d'utilisation des codes
     */
    public function statistics()
    {
        $this->authorize('view-attendance-statistics');

        $stats = [
            'total_codes' => ESBTPDailyCode::count(),
            'active_codes' => ESBTPDailyCode::where('status', 'active')->count(),
            'expired_codes' => ESBTPDailyCode::where('status', 'expired')->count(),
            'cancelled_codes' => ESBTPDailyCode::where('status', 'cancelled')->count(),
            'total_attempts' => ESBTPDailyCode::sum('total_attempts'),
            'successful_attempts' => ESBTPDailyCode::sum('successful_attempts'),
            'failed_attempts' => ESBTPDailyCode::sum('failed_attempts')
        ];

        $recentActivity = ESBTPDailyCode::with(['generator', 'attendances'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('esbtp.attendance.statistics', compact('stats', 'recentActivity'));
    }
}
