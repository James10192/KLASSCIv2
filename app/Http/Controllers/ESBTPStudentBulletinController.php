<?php

namespace App\Http\Controllers;

use App\Exceptions\CoefficientMissingException;
use App\Helpers\SettingsHelper;
use App\Models\Classe;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\ESBTP\ESBTPAbsenceService;
use Carbon\Carbon;
use App\Http\Requests\Bulletin\BulkUpdateMoyennesRequest;
use App\Http\Requests\Bulletin\GenerateClasseBulletinsRequest;
use App\Http\Requests\Bulletin\StoreBulletinRequest;
use App\Http\Requests\Bulletin\UpdateBulletinRequest;
use App\Http\Requests\Bulletin\UpdateMoyennesRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PDF;

class ESBTPStudentBulletinController extends Controller
{
    private $absenceService;
    private $bulletinService;

    public function __construct(\App\Services\ESBTP\ESBTPAbsenceService $absenceService, \App\Services\BulletinService $bulletinService)
    {
        $this->absenceService = $absenceService;
        $this->bulletinService = $bulletinService;
    }

    /**
     * Calcule les indicateurs clés affichés sur la page des résultats.
     */
    /**
     * Build the students query based on filters (extracted from resultats method)
     */
    /**
     * Helper method to get pre-calculated results from ESBTPResultat table
     */
    /**
     * Helper method to calculate student statistics using the same logic as resultatEtudiant.
     * Integrates ESBTPResultat (manual grade overrides) when classeId/anneeUniversitaireId are provided.
     */
    /**
     * Helper method to get bulletins for students
     */
    /**
     * Affiche le bulletin de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function monBulletin(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Récupérer l'étudiant associé à l'utilisateur
        $etudiant = $user->etudiant;

        if (! $etudiant) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre compte utilisateur n\'est pas associé à un étudiant.');
        }

        // Récupérer les paramètres de filtre
        $anneeId = $request->input('annee_universitaire_id',
            ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null);
        $periode = $request->input('periode');

        // Récupérer l'inscription active de l'étudiant
        $inscription = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', 'active')
            ->first();

        if (! $inscription) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'êtes pas inscrit pour l\'année universitaire sélectionnée.');
        }

        // Récupérer la classe de l'étudiant
        $classe = $inscription->classe;

        if (! $classe) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre inscription n\'est associée à aucune classe.');
        }

        // Récupérer le bulletin de l'étudiant
        $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeId);

        if ($periode) {
            $bulletin = $bulletin->where('periode', $periode);
        }

        $bulletin = $bulletin->first();

        // Si le bulletin n'existe pas encore, on affiche un message
        if (! $bulletin) {
            // Récupérer toutes les années universitaires pour le filtre
            $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

            return view('esbtp.bulletin.mon-bulletin', compact(
                'etudiant',
                'classe',
                'anneeId',
                'periode',
                'anneesUniversitaires'
            ))->with('warning', 'Le bulletin n\'est pas encore disponible pour la période sélectionnée.');
        }

        // Récupérer les détails du bulletin
        $detailsBulletin = ESBTPBulletinDetail::where('bulletin_id', $bulletin->id)
            ->with(['matiere'])
            ->get();

        // Regrouper les détails par UE si nécessaire
        $detailsParUE = [];

        foreach ($detailsBulletin as $detail) {
            $ueId = $detail->matiere->ue_id ?? 'sans_ue';
            if (! isset($detailsParUE[$ueId])) {
                $detailsParUE[$ueId] = [
                    'ue' => $detail->matiere->ue ?? null,
                    'details' => [],
                ];
            }
            $detailsParUE[$ueId]['details'][] = $detail;
        }

        // Calculer les statistiques globales
        $moyenneGenerale = $bulletin->moyenne_generale;
        $rangGeneral = $bulletin->rang;
        $effectifClasse = $bulletin->effectif_classe;
        $creditsTotaux = $detailsBulletin->sum('credits_valides');
        $decisionConseil = $bulletin->decision_conseil;

        // Récupérer toutes les années universitaires pour le filtre
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        return view('esbtp.bulletin.mon-bulletin', compact(
            'etudiant',
            'classe',
            'bulletin',
            'detailsBulletin',
            'detailsParUE',
            'moyenneGenerale',
            'rangGeneral',
            'effectifClasse',
            'creditsTotaux',
            'decisionConseil',
            'anneeId',
            'periode',
            'anneesUniversitaires'
        ));
    }

    /**
     * Affiche les bulletins de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function studentBulletins()
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (! $etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // 1. Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $anneeCourante) {
            return view('esbtp.bulletins.mon-bulletin', [
                'bulletins' => collect([]),
                'etudiant' => $etudiant,
                'anneeCourante' => null,
                'inscription' => null,
            ]);
        }

        // 2. Vérifier si l'étudiant a une inscription active pour l'année courante
        $inscription = $etudiant->inscriptions()
            ->where('status', 'active')
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
            ->first();

        if (! $inscription) {
            return view('esbtp.bulletins.mon-bulletin', [
                'bulletins' => collect([]),
                'etudiant' => $etudiant,
                'anneeCourante' => $anneeCourante,
                'inscription' => null,
            ])->with('warning', 'Vous n\'avez pas d\'inscription active pour l\'année en cours. Veuillez contacter l\'administration.');
        }

        // 3. Récupérer les bulletins de l'année courante uniquement
        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->with(['classe', 'anneeUniversitaire'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Pour chaque bulletin, calculer les données via le BulletinService si nécessaire
        foreach ($bulletins as $bulletin) {
            try {
                // Utiliser le BulletinService pour obtenir les vraies données
                $donnees = $this->bulletinService->genererDonneesBulletin(
                    $etudiant->id,
                    $bulletin->classe_id,
                    $bulletin->annee_universitaire_id,
                    $bulletin->periode
                );

                $bulletin->moyenne_generale = $donnees['moyenneAvecAssiduite'];
                $bulletin->rang = $donnees['rang'];
                $bulletin->effectif_classe = $donnees['effectif'];
                $bulletin->mention = $donnees['appreciation'];

            } catch (\Exception $e) {
                // Si le bulletin n'est pas encore configuré, on garde les données par défaut
                $bulletin->moyenne_generale = null;
                $bulletin->rang = null;
                $bulletin->effectif_classe = null;
                $bulletin->mention = 'Non disponible';
            }
        }

        return view('esbtp.bulletins.mon-bulletin', compact('bulletins', 'etudiant', 'anneeCourante', 'inscription'));
    }

    /**
     * Affiche un bulletin spécifique de l'étudiant connecté
     */
    public function showStudentBulletin($bulletinId)
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (! $etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer le bulletin en s'assurant qu'il appartient à l'étudiant connecté
        $bulletin = ESBTPBulletin::where('id', $bulletinId)
            ->where('etudiant_id', $etudiant->id)
            ->with(['classe', 'anneeUniversitaire'])
            ->first();

        if (! $bulletin) {
            return redirect()->route('mon-bulletin.index')->with('error', 'Bulletin non trouvé ou non autorisé.');
        }

        try {
            // Utiliser le BulletinService unifié pour générer les données
            $donnees = $this->bulletinService->genererDonneesBulletin(
                $etudiant->id,
                $bulletin->classe_id,
                $bulletin->annee_universitaire_id,
                $bulletin->periode
            );

            // Ajouter le logo pour l'affichage (les settings sont déjà fournis par le service)
            $logoBase64 = $this->bulletinService->prepareLogoBase64($donnees['settings']['school_logo'] ?? null);
            $donnees['logoBase64'] = $logoBase64;

            // Utiliser exactement le même template que la preview admin
            return view($this->bulletinService->getBulletinTemplateView(), $donnees);

        } catch (\Exception $e) {
            // Gestion des erreurs de configuration
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                return redirect()->route('mon-bulletin.index')
                    ->with('error', 'Ce bulletin n\'est pas encore configuré ou disponible.');
            }

            return redirect()->route('mon-bulletin.index')
                ->with('error', 'Erreur lors de l\'affichage du bulletin : '.$e->getMessage());
        }
    }
}
