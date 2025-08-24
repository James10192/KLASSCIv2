<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ESBTPParent;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPAnneeUniversitaire;
use PDF;

class ParentBulletinController extends Controller
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:parent']);
    }

    /**
     * Affiche la liste des bulletins de tous les étudiants du parent
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $parent = ESBTPParent::where('user_id', $user->id)->firstOrFail();
        
        // Récupérer les étudiants associés au parent
        $etudiants = ESBTPEtudiant::whereHas('parents', function($query) use ($parent) {
            $query->where('esbtp_parents.id', $parent->id);
        })->get();
        
        // Récupérer les années universitaires pour le filtre
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        // Préparer les données des bulletins par étudiant
        $etudiantsBulletins = [];
        foreach ($etudiants as $etudiant) {
            $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            $etudiantsBulletins[$etudiant->id] = [
                'etudiant' => $etudiant,
                'bulletins' => $bulletins
            ];
        }
        
        return view('parent.bulletins.index', compact('etudiantsBulletins', 'anneesUniversitaires', 'anneeEnCours'));
    }

    /**
     * Affiche les bulletins d'un étudiant spécifique
     *
     * @param  int  $etudiantId
     * @return \Illuminate\Http\Response
     */
    public function showStudentBulletins($etudiantId)
    {
        $user = Auth::user();
        $parent = ESBTPParent::where('user_id', $user->id)->firstOrFail();
        
        // Vérifier que l'étudiant appartient au parent
        $etudiant = ESBTPEtudiant::where('id', $etudiantId)
            ->whereHas('parents', function($query) use ($parent) {
                $query->where('esbtp_parents.id', $parent->id);
            })->firstOrFail();
        
        // Récupérer les années universitaires pour le filtre
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        
        // Récupérer les bulletins de l'étudiant
        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('parent.bulletins.student', compact('etudiant', 'bulletins', 'anneesUniversitaires'));
    }

    /**
     * Affiche les détails d'un bulletin spécifique
     *
     * @param  int  $bulletinId
     * @return \Illuminate\Http\Response
     */
    public function show($bulletinId)
    {
        $user = Auth::user();
        $parent = ESBTPParent::where('user_id', $user->id)->firstOrFail();
        
        // Récupérer le bulletin et vérifier qu'il appartient à un étudiant du parent
        $bulletin = ESBTPBulletin::with(['etudiant', 'classe', 'anneeUniversitaire', 'resultatsMatiere.matiere'])
            ->where('id', $bulletinId)
            ->whereHas('etudiant.parents', function($query) use ($parent) {
                $query->where('esbtp_parents.id', $parent->id);
            })->firstOrFail();
        
        // Récupérer l'étudiant
        $etudiant = $bulletin->etudiant;
        
        return view('parent.bulletins.show', compact('bulletin', 'etudiant'));
    }

    /**
     * Télécharge le PDF d'un bulletin
     *
     * @param  int  $bulletinId
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf($bulletinId)
    {
        $user = Auth::user();
        $parent = ESBTPParent::where('user_id', $user->id)->firstOrFail();
        
        // Récupérer le bulletin et vérifier qu'il appartient à un étudiant du parent
        $bulletin = ESBTPBulletin::with(['etudiant', 'classe', 'anneeUniversitaire'])
            ->where('id', $bulletinId)
            ->whereHas('etudiant.parents', function($query) use ($parent) {
                $query->where('esbtp_parents.id', $parent->id);
            })->firstOrFail();
        
        // Déléguer la génération PDF au contrôleur principal pour utiliser les calculs corrigés
        $bulletinController = new \App\Http\Controllers\ESBTPBulletinController();
        return $bulletinController->genererPDF($bulletin);
    }
} 