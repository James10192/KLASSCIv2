<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPComptabiliteConfiguration;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFacture;
use App\Models\ESBTPFactureDetail;
use App\Models\ESBTPCategorieDepense;
use App\Models\ESBTPDepense;
use App\Models\ESBTPFournisseur;
use App\Models\ESBTPBourse;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPTransactionFinanciere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ESBTPComptabiliteController extends Controller
{
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:access_comptabilite_module');
    }

    /**
     * Affiche le tableau de bord de la comptabilité
     */
    public function index()
    {
        // Récupérer les statistiques pour le tableau de bord
        $statsRecettes = $this->getStatsRecettes();
        $statsDepenses = $this->getStatsDepenses();
        $statsPaiements = $this->getStatsPaiements();
        $topEtudiants = $this->getTopEtudiants();
        $topDettes = $this->getTopDettes();
        $recettesParMois = $this->getRecettesParMois();
        $depensesParMois = $this->getDepensesParMois();

        return view('esbtp.comptabilite.index', compact(
            'statsRecettes',
            'statsDepenses',
            'statsPaiements',
            'topEtudiants',
            'topDettes',
            'recettesParMois',
            'depensesParMois'
        ));
    }

    /**
     * Récupère les statistiques des recettes
     */
    private function getStatsRecettes()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return [
                'total' => 0,
                'mensuel' => 0,
                'annuel' => 0,
                'previsionnel' => 0
            ];
        }

        // Total des paiements reçus
        $totalPaiements = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('statut', 'completé')
            ->sum('montant');

        // Paiements du mois en cours
        $paiementsMensuels = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('statut', 'completé')
            ->whereMonth('date_paiement', Carbon::now()->month)
            ->whereYear('date_paiement', Carbon::now()->year)
            ->sum('montant');

        // Paiements de l'année en cours
        $paiementsAnnuels = ESBTPPaiement::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('statut', 'completé')
            ->whereYear('date_paiement', Carbon::now()->year)
            ->sum('montant');

        // Montant prévisionnel (total des frais de scolarité configurés)
        $totalPrevisionnel = ESBTPFraisScolarite::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('est_actif', true)
            ->sum('montant_total');

        return [
            'total' => $totalPaiements,
            'mensuel' => $paiementsMensuels,
            'annuel' => $paiementsAnnuels,
            'previsionnel' => $totalPrevisionnel
        ];
    }

    /**
     * Récupère les statistiques des dépenses
     */
    private function getStatsDepenses()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return [
                'total' => 0,
                'mensuel' => 0,
                'salaires' => 0,
                'fournitures' => 0
            ];
        }

        // On prend l'année scolaire en cours (qui peut s'étendre sur 2 années civiles)
        $dateDebut = Carbon::parse($anneeEnCours->date_debut);
        $dateFin = Carbon::parse($anneeEnCours->date_fin);

        // Total des dépenses
        $totalDepenses = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
            ->where('statut', 'validée')
            ->sum('montant');

        // Dépenses du mois en cours
        $depensesMensuelles = ESBTPDepense::whereMonth('date_depense', Carbon::now()->month)
            ->whereYear('date_depense', Carbon::now()->year)
            ->where('statut', 'validée')
            ->sum('montant');

        // Total des salaires
        $totalSalaires = ESBTPSalaire::where('annee_universitaire_id', $anneeEnCours->id)
            ->where('statut', 'payé')
            ->sum('montant_net');

        // Total des dépenses en fournitures
        $idCategorieFournitures = ESBTPCategorieDepense::where('nom', 'like', '%fourniture%')->first();
        $totalFournitures = 0;

        if ($idCategorieFournitures) {
            $totalFournitures = ESBTPDepense::where('categorie_id', $idCategorieFournitures->id)
                ->whereBetween('date_depense', [$dateDebut, $dateFin])
                ->where('statut', 'validée')
                ->sum('montant');
        }

        return [
            'total' => $totalDepenses,
            'mensuel' => $depensesMensuelles,
            'salaires' => $totalSalaires,
            'fournitures' => $totalFournitures
        ];
    }

    /**
     * Récupère les statistiques des paiements
     */
    private function getStatsPaiements()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return [
                'total' => 0,
                'complets' => 0,
                'partiels' => 0,
                'impayés' => 0,
                'taux_recouvrement' => 0
            ];
        }

        // Nombre total d'inscriptions
        $totalInscriptions = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)->count();

        // Nombre d'étudiants ayant payé complètement
        $etudiantsPayeComplet = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_paiements.etudiant_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) >= (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        // Nombre d'étudiants ayant payé partiellement
        $etudiantsPayePartiel = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_paiements.etudiant_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) > 0 AND SUM(esbtp_paiements.montant) < (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        // Nombre d'étudiants n'ayant rien payé
        $etudiantsImpaye = $totalInscriptions - $etudiantsPayeComplet - $etudiantsPayePartiel;

        // Taux de recouvrement
        $tauxRecouvrement = $totalInscriptions > 0 ?
            round(($etudiantsPayeComplet / $totalInscriptions) * 100, 2) : 0;

        return [
            'total' => $totalInscriptions,
            'complets' => $etudiantsPayeComplet,
            'partiels' => $etudiantsPayePartiel,
            'impayés' => $etudiantsImpaye,
            'taux_recouvrement' => $tauxRecouvrement
        ];
    }

    /**
     * Récupère les meilleurs payeurs (top 5)
     */
    private function getTopEtudiants()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        return DB::table('esbtp_paiements')
            ->join('esbtp_etudiants', 'esbtp_paiements.etudiant_id', '=', 'esbtp_etudiants.id')
            ->select('esbtp_etudiants.id', 'esbtp_etudiants.nom', 'esbtp_etudiants.prenom', DB::raw('SUM(esbtp_paiements.montant) as total_paye'))
            ->where('esbtp_paiements.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy('esbtp_etudiants.id', 'esbtp_etudiants.nom', 'esbtp_etudiants.prenom')
            ->orderByDesc('total_paye')
            ->limit(5)
            ->get();
    }

    /**
     * Récupère les plus grands débiteurs (top 5)
     */
    private function getTopDettes()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        return DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_frais_scolarite', function($join) {
                $join->on('esbtp_frais_scolarite.filiere_id', '=', 'esbtp_inscriptions.filiere_id');
                $join->on('esbtp_frais_scolarite.niveau_id', '=', 'esbtp_inscriptions.niveau_id');
                $join->on('esbtp_frais_scolarite.annee_universitaire_id', '=', 'esbtp_inscriptions.annee_universitaire_id');
            })
            ->leftJoin('esbtp_paiements', function($join) {
                $join->on('esbtp_paiements.etudiant_id', '=', 'esbtp_inscriptions.etudiant_id');
                $join->on('esbtp_paiements.annee_universitaire_id', '=', 'esbtp_inscriptions.annee_universitaire_id');
            })
            ->select(
                'esbtp_etudiants.id',
                'esbtp_etudiants.nom',
                'esbtp_etudiants.prenom',
                'esbtp_frais_scolarite.montant_total',
                DB::raw('COALESCE(SUM(esbtp_paiements.montant), 0) as montant_paye'),
                DB::raw('esbtp_frais_scolarite.montant_total - COALESCE(SUM(esbtp_paiements.montant), 0) as dette')
            )
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeEnCours->id)
            ->groupBy(
                'esbtp_etudiants.id',
                'esbtp_etudiants.nom',
                'esbtp_etudiants.prenom',
                'esbtp_frais_scolarite.montant_total'
            )
            ->having(DB::raw('esbtp_frais_scolarite.montant_total - COALESCE(SUM(esbtp_paiements.montant), 0)'), '>', 0)
            ->orderByDesc('dette')
            ->limit(5)
            ->get();
    }

    /**
     * Récupère les recettes par mois pour l'année en cours
     */
    private function getRecettesParMois()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        $debut = Carbon::parse($anneeEnCours->date_debut);
        $fin = Carbon::parse($anneeEnCours->date_fin);

        $mois = [];
        $recettes = [];

        for ($date = $debut; $date->lte($fin); $date->addMonth()) {
            $mois[] = $date->translatedFormat('F Y');

            $total = ESBTPPaiement::whereMonth('date_paiement', $date->month)
                ->whereYear('date_paiement', $date->year)
                ->where('statut', 'completé')
                ->sum('montant');

            $recettes[] = $total;
        }

        return [
            'labels' => $mois,
            'data' => $recettes
        ];
    }

    /**
     * Récupère les dépenses par mois pour l'année en cours
     */
    private function getDepensesParMois()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$anneeEnCours) {
            return collect([]);
        }

        $debut = Carbon::parse($anneeEnCours->date_debut);
        $fin = Carbon::parse($anneeEnCours->date_fin);

        $mois = [];
        $depenses = [];

        for ($date = $debut; $date->lte($fin); $date->addMonth()) {
            $mois[] = $date->translatedFormat('F Y');

            $total = ESBTPDepense::whereMonth('date_depense', $date->month)
                ->whereYear('date_depense', $date->year)
                ->where('statut', 'validée')
                ->sum('montant');

            $depenses[] = $total;
        }

        return [
            'labels' => $mois,
            'data' => $depenses
        ];
    }

    /**
     * Affiche la liste des paiements
     */
    public function paiements()
    {
        $paiements = ESBTPPaiement::with(['etudiant', 'anneeUniversitaire', 'createur'])
            ->orderBy('date_paiement', 'desc')
            ->paginate(15);

        return view('esbtp.comptabilite.paiements.index', compact('paiements'));
    }

    /**
     * Affiche le formulaire de création d'un paiement
     */
    public function createPaiement()
    {
        $etudiants = ESBTPEtudiant::all();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::all();
        $modesPaiement = ['espèces', 'chèque', 'virement', 'mobile money', 'carte bancaire'];

        return view('esbtp.comptabilite.paiements.create', compact('etudiants', 'anneesUniversitaires', 'modesPaiement'));
    }

    /**
     * Enregistre un nouveau paiement
     */
    public function storePaiement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'type_paiement' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|string',
            'date_paiement' => 'required|date',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Générer une référence unique pour le paiement
        $reference = 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999);

        // Créer le paiement
        $paiement = new ESBTPPaiement();
        $paiement->etudiant_id = $request->etudiant_id;
        $paiement->annee_universitaire_id = $request->annee_universitaire_id;
        $paiement->type_paiement = $request->type_paiement;
        $paiement->montant = $request->montant;
        $paiement->reference_paiement = $reference;
        $paiement->mode_paiement = $request->mode_paiement;
        $paiement->numero_transaction = $request->numero_transaction;
        $paiement->date_paiement = $request->date_paiement;
        $paiement->date_echeance = $request->date_echeance;
        $paiement->description = $request->description;
        $paiement->statut = 'completé';
        $paiement->createur_id = Auth::id();
        $paiement->save();

        // Enregistrer la transaction dans le journal financier
        $transaction = new ESBTPTransactionFinanciere();
        $transaction->type = 'revenu';
        $transaction->transactionable_type = get_class($paiement);
        $transaction->transactionable_id = $paiement->id;
        $transaction->montant = $paiement->montant;
        $transaction->sens = 'crédit';
        $transaction->categorie = 'paiement_scolarite';
        $transaction->reference = $paiement->reference_paiement;
        $transaction->date_transaction = $paiement->date_paiement;
        $transaction->description = $paiement->description;
        $transaction->createur_id = Auth::id();
        $transaction->save();

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Affiche les détails d'un paiement
     */
    public function showPaiement($id)
    {
        $paiement = ESBTPPaiement::with(['etudiant', 'anneeUniversitaire', 'createur', 'validateur'])
            ->findOrFail($id);

        return view('esbtp.comptabilite.paiements.show', compact('paiement'));
    }

    /**
     * Affiche le formulaire d'édition d'un paiement
     */
    public function editPaiement($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->statut === 'validé') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        $etudiants = ESBTPEtudiant::all();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::all();
        $modesPaiement = ['espèces', 'chèque', 'virement', 'mobile money', 'carte bancaire'];

        return view('esbtp.comptabilite.paiements.edit', compact('paiement', 'etudiants', 'anneesUniversitaires', 'modesPaiement'));
    }

    /**
     * Met à jour un paiement
     */
    public function updatePaiement(Request $request, $id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->statut === 'validé') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        $validator = Validator::make($request->all(), [
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'type_paiement' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|string',
            'date_paiement' => 'required|date',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Mettre à jour le paiement
        $paiement->etudiant_id = $request->etudiant_id;
        $paiement->annee_universitaire_id = $request->annee_universitaire_id;
        $paiement->type_paiement = $request->type_paiement;
        $paiement->montant = $request->montant;
        $paiement->mode_paiement = $request->mode_paiement;
        $paiement->numero_transaction = $request->numero_transaction;
        $paiement->date_paiement = $request->date_paiement;
        $paiement->date_echeance = $request->date_echeance;
        $paiement->description = $request->description;
        $paiement->updated_by = Auth::id();
        $paiement->save();

        // Mettre à jour la transaction dans le journal financier
        $transaction = ESBTPTransactionFinanciere::where('transactionable_type', get_class($paiement))
            ->where('transactionable_id', $paiement->id)
            ->first();

        if ($transaction) {
            $transaction->montant = $paiement->montant;
            $transaction->date_transaction = $paiement->date_paiement;
            $transaction->description = $paiement->description;
            $transaction->save();
        }

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement mis à jour avec succès.');
    }

    /**
     * Valide un paiement
     */
    public function validerPaiement($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être validé
        if ($paiement->statut !== 'en_attente') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement ne peut pas être validé.');
        }

        // Valider le paiement
        $paiement->statut = 'validé';
        $paiement->date_validation = now();
        $paiement->validated_by = Auth::id();
        $paiement->save();

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement validé avec succès.');
    }

    /**
     * Rejette un paiement
     */
    public function rejeterPaiement($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être rejeté
        if ($paiement->statut !== 'en_attente') {
            return redirect()->route('esbtp.comptabilite.paiements')
                ->with('error', 'Ce paiement ne peut pas être rejeté.');
        }

        // Rejeter le paiement
        $paiement->statut = 'rejeté';
        $paiement->date_validation = now();
        $paiement->validated_by = Auth::id();
        $paiement->save();

        return redirect()->route('esbtp.comptabilite.paiements')
            ->with('success', 'Paiement rejeté avec succès.');
    }

    /**
     * Génère un reçu de paiement
     */
    public function genererRecu($id)
    {
        $paiement = ESBTPPaiement::with(['etudiant', 'anneeUniversitaire', 'createur', 'validateur'])
            ->findOrFail($id);

        // Ici, vous pourriez générer un PDF ou simplement afficher une page de reçu
        return view('esbtp.comptabilite.paiements.recu', compact('paiement'));
    }

    /**
     * Affiche les rapports financiers
     */
    public function rapports()
    {
        $statsRecettes = $this->getStatsRecettes();
        $statsDepenses = $this->getStatsDepenses();
        $statsPaiements = $this->getStatsPaiements();
        $recettesParMois = $this->getRecettesParMois();
        $depensesParMois = $this->getDepensesParMois();

        return view('esbtp.comptabilite.rapports', compact(
            'statsRecettes',
            'statsDepenses',
            'statsPaiements',
            'recettesParMois',
            'depensesParMois'
        ));
    }

    /**
     * Génère un rapport financier personnalisé
     */
    public function generateReport(Request $request)
    {
        // Logique pour générer un rapport personnalisé selon les paramètres de la requête
        $dateDebut = $request->input('date_debut', now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('date_fin', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', 'general');

        // Récupération des données selon le type de rapport
        $data = [];

        switch ($type) {
            case 'paiements':
                $data['paiements'] = ESBTPPaiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
                    ->with(['etudiant', 'anneeUniversitaire'])
                    ->get();
                break;

            case 'depenses':
                $data['depenses'] = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
                    ->with(['categorie', 'createur'])
                    ->get();
                break;

            case 'general':
            default:
                $data['paiements'] = ESBTPPaiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
                    ->with(['etudiant', 'anneeUniversitaire'])
                    ->get();

                $data['depenses'] = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
                    ->with(['categorie', 'createur'])
                    ->get();

                $data['totalRecettes'] = $data['paiements']->sum('montant');
                $data['totalDepenses'] = $data['depenses']->sum('montant');
                $data['balance'] = $data['totalRecettes'] - $data['totalDepenses'];
                break;
        }

        $data['dateDebut'] = $dateDebut;
        $data['dateFin'] = $dateFin;
        $data['type'] = $type;

        return view('esbtp.comptabilite.rapports', compact('data'));
    }

    /**
     * Exporte un rapport financier
     */
    public function exportReport(Request $request)
    {
        // Logique pour exporter un rapport au format PDF, Excel ou CSV
        $format = $request->input('format', 'pdf');

        // Exemple simple, dans un cas réel, vous utiliseriez une bibliothèque comme Dompdf ou Laravel Excel
        return redirect()->back()->with('success', 'Fonctionnalité d\'export en cours de développement.');
    }

    /**
     * Affiche la liste des frais de scolarité
     */
    public function fraisScolarite()
    {
        $query = ESBTPFraisScolarite::with(['filiere', 'niveau', 'anneeUniversitaire']);

        // Filtres
        if (request()->has('filiere') && !empty(request('filiere'))) {
            $query->where('filiere_id', request('filiere'));
        }

        if (request()->has('niveau') && !empty(request('niveau'))) {
            $query->where('niveau_id', request('niveau'));
        }

        if (request()->has('annee') && !empty(request('annee'))) {
            $query->where('annee_universitaire_id', request('annee'));
        }

        $fraisScolarites = $query->orderBy('created_at', 'desc')->paginate(15);

        // Récupérer les données pour les filtres
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        return view('esbtp.comptabilite.frais-scolarite.index', compact('fraisScolarites', 'filieres', 'niveaux', 'annees'));
    }

    /**
     * Affiche le formulaire de création des frais de scolarité
     */
    public function createFraisScolarite()
    {
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        return view('esbtp.comptabilite.frais-scolarite.create', compact('filieres', 'niveaux', 'annees'));
    }

    /**
     * Enregistre de nouveaux frais de scolarité
     */
    public function storeFraisScolarite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'montant_total' => 'required|numeric|min:0',
            'frais_inscription' => 'required|numeric|min:0',
            'frais_mensuel' => 'nullable|numeric|min:0',
            'frais_trimestriel' => 'nullable|numeric|min:0',
            'frais_semestriel' => 'nullable|numeric|min:0',
            'frais_annuel' => 'nullable|numeric|min:0',
            'nombre_echeances' => 'required|integer|min:1',
            'details' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Vérifier si une configuration de frais existe déjà pour cette combinaison
        $existingFrais = ESBTPFraisScolarite::where('filiere_id', $request->filiere_id)
            ->where('niveau_id', $request->niveau_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->first();

        if ($existingFrais) {
            return redirect()->back()
                ->with('error', 'Une configuration de frais existe déjà pour cette combinaison filière/niveau/année.')
                ->withInput();
        }

        // Créer les frais de scolarité
        $fraisScolarite = new ESBTPFraisScolarite();
        $fraisScolarite->filiere_id = $request->filiere_id;
        $fraisScolarite->niveau_id = $request->niveau_id;
        $fraisScolarite->annee_universitaire_id = $request->annee_universitaire_id;
        $fraisScolarite->montant_total = $request->montant_total;
        $fraisScolarite->frais_inscription = $request->frais_inscription;
        $fraisScolarite->frais_mensuel = $request->frais_mensuel;
        $fraisScolarite->frais_trimestriel = $request->frais_trimestriel;
        $fraisScolarite->frais_semestriel = $request->frais_semestriel;
        $fraisScolarite->frais_annuel = $request->frais_annuel;
        $fraisScolarite->nombre_echeances = $request->nombre_echeances;
        $fraisScolarite->details = $request->details;
        $fraisScolarite->est_actif = true;
        $fraisScolarite->save();

        return redirect()->route('esbtp.comptabilite.frais-scolarite')
            ->with('success', 'Configuration des frais de scolarité enregistrée avec succès.');
    }

    /**
     * Affiche les détails des frais de scolarité
     */
    public function showFraisScolarite($id)
    {
        $fraisScolarite = ESBTPFraisScolarite::with(['filiere', 'niveau', 'anneeUniversitaire'])
            ->findOrFail($id);

        return view('esbtp.comptabilite.frais-scolarite.show', compact('fraisScolarite'));
    }

    /**
     * Affiche le formulaire d'édition des frais de scolarité
     */
    public function editFraisScolarite($id)
    {
        $fraisScolarite = ESBTPFraisScolarite::findOrFail($id);
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::orderBy('name')->get();
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();

        return view('esbtp.comptabilite.frais-scolarite.edit', compact('fraisScolarite', 'filieres', 'niveaux', 'annees'));
    }

    /**
     * Met à jour les frais de scolarité
     */
    public function updateFraisScolarite(Request $request, $id)
    {
        $fraisScolarite = ESBTPFraisScolarite::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'montant_total' => 'required|numeric|min:0',
            'frais_inscription' => 'required|numeric|min:0',
            'frais_mensuel' => 'nullable|numeric|min:0',
            'frais_trimestriel' => 'nullable|numeric|min:0',
            'frais_semestriel' => 'nullable|numeric|min:0',
            'frais_annuel' => 'nullable|numeric|min:0',
            'nombre_echeances' => 'required|integer|min:1',
            'details' => 'nullable|string',
            'est_actif' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Vérifier si une autre configuration de frais existe déjà pour cette combinaison
        $existingFrais = ESBTPFraisScolarite::where('filiere_id', $request->filiere_id)
            ->where('niveau_id', $request->niveau_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existingFrais) {
            return redirect()->back()
                ->with('error', 'Une autre configuration de frais existe déjà pour cette combinaison filière/niveau/année.')
                ->withInput();
        }

        // Mettre à jour les frais de scolarité
        $fraisScolarite->filiere_id = $request->filiere_id;
        $fraisScolarite->niveau_id = $request->niveau_id;
        $fraisScolarite->annee_universitaire_id = $request->annee_universitaire_id;
        $fraisScolarite->montant_total = $request->montant_total;
        $fraisScolarite->frais_inscription = $request->frais_inscription;
        $fraisScolarite->frais_mensuel = $request->frais_mensuel;
        $fraisScolarite->frais_trimestriel = $request->frais_trimestriel;
        $fraisScolarite->frais_semestriel = $request->frais_semestriel;
        $fraisScolarite->frais_annuel = $request->frais_annuel;
        $fraisScolarite->nombre_echeances = $request->nombre_echeances;
        $fraisScolarite->details = $request->details;
        $fraisScolarite->est_actif = $request->has('est_actif');
        $fraisScolarite->save();

        return redirect()->route('esbtp.comptabilite.frais-scolarite')
            ->with('success', 'Configuration des frais de scolarité mise à jour avec succès.');
    }

    /**
     * Supprime des frais de scolarité
     */
    public function destroyFraisScolarite($id)
    {
        $fraisScolarite = ESBTPFraisScolarite::findOrFail($id);

        // Vérifier si des inscriptions utilisent cette configuration
        $inscriptionsUtilisant = \App\Models\ESBTPInscription::where('filiere_id', $fraisScolarite->filiere_id)
            ->where('niveau_id', $fraisScolarite->niveau_id)
            ->where('annee_universitaire_id', $fraisScolarite->annee_universitaire_id)
            ->count();

        if ($inscriptionsUtilisant > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer cette configuration car elle est utilisée par ' . $inscriptionsUtilisant . ' inscription(s).');
        }

        $fraisScolarite->delete();

        return redirect()->route('esbtp.comptabilite.frais-scolarite')
            ->with('success', 'Configuration des frais de scolarité supprimée avec succès.');
    }

    /**
     * Affiche la liste des bourses
     */
    public function bourses()
    {
        try {
            $bourses = ESBTPBourse::with(['etudiant', 'anneeUniversitaire', 'createur'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return view('esbtp.comptabilite.bourses.index', compact('bourses'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors du chargement des bourses : ' . $e->getMessage());
        }
    }

    /**
     * Affiche le formulaire de création d'une nouvelle bourse
     */
    public function createBourse()
    {
        try {
            $etudiants = ESBTPEtudiant::orderBy('nom')->orderBy('prenoms')->get();
            $annees = ESBTPAnneeUniversitaire::orderBy('name')->get();

            return view('esbtp.comptabilite.bourses.create', compact('etudiants', 'annees'));
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Erreur lors du chargement du formulaire : ' . $e->getMessage());
        }
    }

    /**
     * Enregistre une nouvelle bourse
     */
    public function storeBourse(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annees_universitaires,id',
            'type_bourse' => 'required|string|max:50',
            'montant' => 'nullable|numeric|min:0',
            'pourcentage' => 'nullable|numeric|min:0|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'statut' => 'required|in:active,suspendue,terminée',
            'organisme_financeur' => 'nullable|string|max:255',
            'conditions' => 'nullable|string',
            'commentaires' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'Veuillez sélectionner un étudiant.',
            'etudiant_id.exists' => 'L\'étudiant sélectionné n\'existe pas.',
            'annee_universitaire_id.required' => 'Veuillez sélectionner une année universitaire.',
            'annee_universitaire_id.exists' => 'L\'année universitaire sélectionnée n\'existe pas.',
            'type_bourse.required' => 'Veuillez sélectionner un type de bourse.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être positif.',
            'pourcentage.numeric' => 'Le pourcentage doit être un nombre.',
            'pourcentage.min' => 'Le pourcentage doit être positif.',
            'pourcentage.max' => 'Le pourcentage ne peut pas dépasser 100%.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'statut.required' => 'Veuillez sélectionner un statut.',
            'statut.in' => 'Le statut sélectionné n\'est pas valide.',
        ]);

        // Validation : soit montant soit pourcentage doit être renseigné
        if (!$request->montant && !$request->pourcentage) {
            return back()->withErrors(['montant' => 'Veuillez renseigner soit un montant soit un pourcentage.'])->withInput();
        }

        if ($request->montant && $request->pourcentage) {
            return back()->withErrors(['pourcentage' => 'Veuillez renseigner soit un montant soit un pourcentage, pas les deux.'])->withInput();
        }

        try {
            $data = $request->all();
            $data['createur_id'] = auth()->id();

            ESBTPBourse::create($data);

            return redirect()->route('esbtp.comptabilite.bourses')->with('success', 'Bourse créée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création de la bourse : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Affiche les détails d'une bourse
     */
    public function showBourse($id)
    {
        try {
            $bourse = ESBTPBourse::with(['etudiant', 'anneeUniversitaire', 'createur'])->findOrFail($id);

            return view('esbtp.comptabilite.bourses.show', compact('bourse'));
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Bourse non trouvée.');
        }
    }

    /**
     * Affiche le formulaire d'édition d'une bourse
     */
    public function editBourse($id)
    {
        try {
            $bourse = ESBTPBourse::findOrFail($id);
            $etudiants = ESBTPEtudiant::orderBy('nom')->orderBy('prenoms')->get();
            $annees = ESBTPAnneeUniversitaire::orderBy('name')->get();

            return view('esbtp.comptabilite.bourses.edit', compact('bourse', 'etudiants', 'annees'));
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Bourse non trouvée.');
        }
    }

    /**
     * Met à jour une bourse
     */
    public function updateBourse(Request $request, $id)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annees_universitaires,id',
            'type_bourse' => 'required|string|max:50',
            'montant' => 'nullable|numeric|min:0',
            'pourcentage' => 'nullable|numeric|min:0|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'statut' => 'required|in:active,suspendue,terminée',
            'organisme_financeur' => 'nullable|string|max:255',
            'conditions' => 'nullable|string',
            'commentaires' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'Veuillez sélectionner un étudiant.',
            'etudiant_id.exists' => 'L\'étudiant sélectionné n\'existe pas.',
            'annee_universitaire_id.required' => 'Veuillez sélectionner une année universitaire.',
            'annee_universitaire_id.exists' => 'L\'année universitaire sélectionnée n\'existe pas.',
            'type_bourse.required' => 'Veuillez sélectionner un type de bourse.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être positif.',
            'pourcentage.numeric' => 'Le pourcentage doit être un nombre.',
            'pourcentage.min' => 'Le pourcentage doit être positif.',
            'pourcentage.max' => 'Le pourcentage ne peut pas dépasser 100%.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'statut.required' => 'Veuillez sélectionner un statut.',
            'statut.in' => 'Le statut sélectionné n\'est pas valide.',
        ]);

        // Validation : soit montant soit pourcentage doit être renseigné
        if (!$request->montant && !$request->pourcentage) {
            return back()->withErrors(['montant' => 'Veuillez renseigner soit un montant soit un pourcentage.'])->withInput();
        }

        if ($request->montant && $request->pourcentage) {
            return back()->withErrors(['pourcentage' => 'Veuillez renseigner soit un montant soit un pourcentage, pas les deux.'])->withInput();
        }

        try {
            $bourse = ESBTPBourse::findOrFail($id);
            $bourse->update($request->all());

            return redirect()->route('esbtp.comptabilite.bourses')->with('success', 'Bourse mise à jour avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour de la bourse : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Supprime une bourse
     */
    public function destroyBourse($id)
    {
        try {
            $bourse = ESBTPBourse::findOrFail($id);
            $bourse->delete();

            return redirect()->route('esbtp.comptabilite.bourses')->with('success', 'Bourse supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()->route('esbtp.comptabilite.bourses')->with('error', 'Erreur lors de la suppression de la bourse : ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page de configuration de la comptabilité
     */
    public function configuration()
    {
        $configurations = ESBTPComptabiliteConfiguration::orderBy('cle')->get();

        return view('esbtp.comptabilite.configuration', compact('configurations'));
    }

    /**
     * Met à jour la configuration de la comptabilité
     */
    public function updateConfiguration(Request $request)
    {
        foreach ($request->configurations as $id => $value) {
            $config = ESBTPComptabiliteConfiguration::findOrFail($id);
            $config->valeur = $value;
            $config->save();
        }

        return redirect()->route('esbtp.comptabilite.configuration')
            ->with('success', 'Configuration mise à jour avec succès.');
    }

    /**
     * Affiche la liste des fournisseurs
     */
    public function fournisseurs()
    {
        $fournisseurs = ESBTPFournisseur::orderBy('nom')->paginate(15);

        return view('esbtp.comptabilite.fournisseurs.index', compact('fournisseurs'));
    }

    /**
     * Affiche le formulaire d'ajout d'un fournisseur
     */
    public function createFournisseur()
    {
        return view('esbtp.comptabilite.fournisseurs.create');
    }

    /**
     * Enregistre un nouveau fournisseur
     */
    public function storeFournisseur(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:255',
            'est_actif' => 'nullable|boolean',
        ]);

        $fournisseur = new \App\Models\ESBTPFournisseur();
        $fournisseur->nom = $validated['nom'];
        $fournisseur->telephone = $validated['telephone'] ?? null;
        $fournisseur->email = $validated['email'] ?? null;
        $fournisseur->adresse = $validated['adresse'] ?? null;
        $fournisseur->est_actif = $validated['est_actif'] ?? 1;
        $fournisseur->code = 'F-' . date('Ymd') . '-' . str_pad(ESBTPFournisseur::max('id')+1, 3, '0', STR_PAD_LEFT);
        $fournisseur->save();

        return redirect()->route('esbtp.comptabilite.fournisseurs')
            ->with('success', 'Fournisseur ajouté avec succès.');
    }

    /**
     * Affiche le formulaire d'édition d'un fournisseur
     */
    public function editFournisseur($id)
    {
        $fournisseur = \App\Models\ESBTPFournisseur::findOrFail($id);
        return view('esbtp.comptabilite.fournisseurs.edit', compact('fournisseur'));
    }

    /**
     * Affiche la liste des factures
     */
    public function factures()
    {
        $factures = ESBTPFacture::with(['etudiant', 'anneeUniversitaire', 'createur'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('esbtp.comptabilite.factures.index', compact('factures'));
    }

    /**
     * Affiche la liste des salaires
     */
    public function salaires()
    {
        $salaires = ESBTPSalaire::with(['user', 'anneeUniversitaire', 'createur', 'validateur'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculer les montants totaux des salaires
        $totalSalaires = ESBTPSalaire::sum('montant_net');
        $totalPayes = ESBTPSalaire::where('statut', 'payé')->sum('montant_net');
        $totalEnAttente = ESBTPSalaire::where('statut', 'en attente')->sum('montant_net');

        return view('esbtp.comptabilite.salaires.index', compact('salaires', 'totalSalaires', 'totalPayes', 'totalEnAttente'));
    }

    /**
     * Affiche le formulaire de création d'un salaire
     */
    public function createSalaire()
    {
        // Récupérer la liste des employés (enseignants et administratifs)
        $users = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['enseignant', 'secretaire', 'superAdmin']);
        })->orderBy('name')->get();

        return view('esbtp.comptabilite.salaires.create', compact('users'));
    }

    /**
     * Enregistre un nouveau salaire
     */
    public function storeSalaire(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer',
            'salaire_base' => 'required|numeric|min:0',
            'heures_supplementaires' => 'nullable|numeric|min:0',
            'primes' => 'nullable|numeric|min:0',
            'retenues' => 'nullable|numeric|min:0',
            'montant_net' => 'required|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'statut' => 'required|in:calculé,validé,payé',
            'description' => 'nullable|string',
        ]);

        // Vérifier si un salaire existe déjà pour cet employé pour ce mois et cette année
        $existingSalaire = ESBTPSalaire::where('user_id', $request->user_id)
            ->where('mois', $request->mois)
            ->where('annee', $request->annee)
            ->first();

        if ($existingSalaire) {
            return redirect()->back()
                ->with('error', 'Un salaire existe déjà pour cet employé pour la période spécifiée.')
                ->withInput();
        }

        // Créer le nouveau salaire
        $salaire = new ESBTPSalaire();
        $salaire->user_id = $request->user_id;
        $salaire->mois = $request->mois;
        $salaire->annee = $request->annee;
        $salaire->salaire_base = $request->salaire_base;
        $salaire->heures_supplementaires = $request->heures_supplementaires ?? 0;
        $salaire->primes = $request->primes ?? 0;
        $salaire->retenues = $request->retenues ?? 0;
        $salaire->montant_net = $request->montant_net;
        $salaire->date_paiement = $request->date_paiement;
        $salaire->statut = $request->statut;
        $salaire->notes = $request->description;
        $salaire->createur_id = auth()->id();

        // Si le statut est "payé", définir la date de validation et le validateur
        if ($request->statut == 'payé') {
            $salaire->validateur_id = auth()->id();
            $salaire->date_validation = now();
        }

        $salaire->save();

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', 'Le salaire a été créé avec succès.');
    }

    /**
     * Affiche les détails d'un salaire
     */
    public function showSalaire($id)
    {
        $salaire = ESBTPSalaire::with(['user', 'anneeUniversitaire', 'createur', 'validateur'])
            ->findOrFail($id);

        return view('esbtp.comptabilite.salaires.show', compact('salaire'));
    }

    /**
     * Affiche le formulaire d'édition d'un salaire
     */
    public function editSalaire($id)
    {
        $salaire = ESBTPSalaire::findOrFail($id);

        // Récupérer la liste des employés
        $users = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['enseignant', 'secretaire', 'superAdmin']);
        })->orderBy('name')->get();

        return view('esbtp.comptabilite.salaires.edit', compact('salaire', 'users'));
    }

    /**
     * Met à jour un salaire existant
     */
    public function updateSalaire(Request $request, $id)
    {
        $salaire = ESBTPSalaire::findOrFail($id);

        // Validation des données
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer',
            'salaire_base' => 'required|numeric|min:0',
            'heures_supplementaires' => 'nullable|numeric|min:0',
            'primes' => 'nullable|numeric|min:0',
            'retenues' => 'nullable|numeric|min:0',
            'montant_net' => 'required|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'statut' => 'required|in:calculé,validé,payé',
            'description' => 'nullable|string',
        ]);

        // Vérifier si un autre salaire existe déjà pour cet employé pour ce mois et cette année
        $existingSalaire = ESBTPSalaire::where('user_id', $request->user_id)
            ->where('mois', $request->mois)
            ->where('annee', $request->annee)
            ->where('id', '!=', $id)
            ->first();

        if ($existingSalaire) {
            return redirect()->back()
                ->with('error', 'Un autre salaire existe déjà pour cet employé pour la période spécifiée.')
                ->withInput();
        }

        // Mettre à jour le salaire
        $salaire->user_id = $request->user_id;
        $salaire->mois = $request->mois;
        $salaire->annee = $request->annee;
        $salaire->salaire_base = $request->salaire_base;
        $salaire->heures_supplementaires = $request->heures_supplementaires ?? 0;
        $salaire->primes = $request->primes ?? 0;
        $salaire->retenues = $request->retenues ?? 0;
        $salaire->montant_net = $request->montant_net;
        $salaire->date_paiement = $request->date_paiement;
        $salaire->notes = $request->description;

        // Si le statut change pour "payé", mettre à jour le validateur et la date de validation
        if ($request->statut == 'payé' && $salaire->statut != 'payé') {
            $salaire->validateur_id = auth()->id();
            $salaire->date_validation = now();
        }

        $salaire->statut = $request->statut;
        $salaire->save();

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', 'Le salaire a été mis à jour avec succès.');
    }

    /**
     * Supprime un salaire
     */
    public function destroySalaire($id)
    {
        $salaire = ESBTPSalaire::findOrFail($id);
        $salaire->delete();

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', 'Le salaire a été supprimé avec succès.');
    }

    /**
     * Génère un bulletin de salaire
     */
    public function bulletinSalaire($id)
    {
        $salaire = ESBTPSalaire::with(['user', 'anneeUniversitaire', 'createur', 'validateur'])
            ->findOrFail($id);

        // Préparer les données pour le PDF
        $data = [
            'salaire' => $salaire,
            'employe' => $salaire->user,
            'mois' => [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ][$salaire->mois],
            'annee' => $salaire->annee,
            'date_emission' => now()->format('d/m/Y')
        ];

        // Générer le PDF
        $pdf = \PDF::loadView('esbtp.comptabilite.salaires.bulletin', $data);

        // Télécharger le PDF
        $filename = 'bulletin_salaire_' . $salaire->user->name . '_' . $data['mois'] . '_' . $data['annee'] . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Met à jour le statut d'un salaire
     */
    public function updateStatusSalaire($id, $status)
    {
        $salaire = ESBTPSalaire::findOrFail($id);

        // Vérifier que le statut est valide
        if (!in_array($status, ['calculé', 'validé', 'payé'])) {
            return redirect()->back()->with('error', 'Statut invalide.');
        }

        // Mettre à jour le statut
        $salaire->statut = $status;

        // Si le statut est "payé", mettre à jour le validateur et la date de validation
        if ($status == 'payé' && $salaire->validateur_id === null) {
            $salaire->validateur_id = auth()->id();
            $salaire->date_validation = now();

            // Mettre à jour la date de paiement si elle n'est pas définie
            if ($salaire->date_paiement === null) {
                $salaire->date_paiement = now();
            }
        }

        $salaire->save();

        $statusText = [
            'calculé' => 'calculé',
            'validé' => 'validé',
            'payé' => 'payé'
        ][$status];

        return redirect()->route('esbtp.comptabilite.salaires')
            ->with('success', "Le salaire a été marqué comme $statusText avec succès.");
    }

    public function dashboard(Request $request)
    {
        // Récupérer les listes pour les filtres
        $annees = \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get();
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $classes = \App\Models\ESBTPClasse::orderBy('name')->get();

        // Filtres
        $anneeId = $request->get('annee');
        $filiereId = $request->get('filiere');
        $classeId = $request->get('classe');

        $feeQuery = \App\Models\ESBTP\Fee::query();
        if ($anneeId) $feeQuery->where('academic_year_id', $anneeId);
        if ($filiereId) $feeQuery->whereHas('class', function($q) use ($filiereId) { $q->where('filiere_id', $filiereId); });
        if ($classeId) $feeQuery->where('class_id', $classeId);

        $totalDue = (clone $feeQuery)->pending()->sum('amount');
        $totalPaid = (clone $feeQuery)->paid()->sum('amount');
        $totalOverdue = (clone $feeQuery)->overdue()->sum('amount');
        $countPaid = (clone $feeQuery)->paid()->count();
        $countPartiallyPaid = (clone $feeQuery)->partiallyPaid()->count();
        $countOverdue = (clone $feeQuery)->overdue()->count();
        $countDue = (clone $feeQuery)->due()->count();

        // Préparer les données pour le graph (encaissements par mois)
        $labelsMois = [];
        $dataEncaissements = [];
        if ($anneeId) {
            $annee = $annees->where('id', $anneeId)->first();
        } else {
            $annee = $annees->first(); // Par défaut, la plus récente
        }
        if ($annee) {
            $debut = \Carbon\Carbon::parse($annee->date_debut);
            $fin = \Carbon\Carbon::parse($annee->date_fin);
            for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
                $labelsMois[] = $date->translatedFormat('M Y');
                $paymentQuery = \App\Models\ESBTP\Payment::completed();
                $paymentQuery->whereMonth('payment_date', $date->month)
                    ->whereYear('payment_date', $date->year);
                if ($anneeId) $paymentQuery->where('academic_year_id', $anneeId);
                if ($filiereId) $paymentQuery->whereHas('fee.class', function($q) use ($filiereId) { $q->where('filiere_id', $filiereId); });
                if ($classeId) $paymentQuery->whereHas('fee', function($q) use ($classeId) { $q->where('class_id', $classeId); });
                $dataEncaissements[] = $paymentQuery->sum('amount');
            }
        }

        // Préparer les données pour le graph (dépenses par mois)
        $labelsMoisDepenses = [];
        $dataDepensesMensuelles = [];
        if ($annee) {
            $debut = \Carbon\Carbon::parse($annee->date_debut);
            $fin = \Carbon\Carbon::parse($annee->date_fin);
            for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
                $labelsMoisDepenses[] = $date->translatedFormat('M Y');
                $depenseQuery = \App\Models\ESBTPDepense::whereBetween('date_depense', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->where('statut', 'validée');
                if ($filiereId) $depenseQuery->where('filiere_id', $filiereId);
                if ($classeId) $depenseQuery->where('classe_id', $classeId);
                $dataDepensesMensuelles[] = $depenseQuery->sum('montant');
            }
        }

        // Agrégats dépenses (centralisation)
        $statsDepenses = [
            'total' => 0,
            'mensuel' => 0,
            'salaires' => 0,
            'fournitures' => 0
        ];
        if ($annee) {
            $dateDebut = \Carbon\Carbon::parse($annee->date_debut);
            $dateFin = \Carbon\Carbon::parse($annee->date_fin);
            $depenseQuery = \App\Models\ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])->where('statut', 'validée');
            if ($filiereId) $depenseQuery->where('filiere_id', $filiereId);
            if ($classeId) $depenseQuery->where('classe_id', $classeId);
            $statsDepenses['total'] = $depenseQuery->sum('montant');
            $statsDepenses['mensuel'] = (clone $depenseQuery)->whereMonth('date_depense', now()->month)->whereYear('date_depense', now()->year)->sum('montant');
            // Salaires et fournitures peuvent être affinés si besoin
        }

        return view('esbtp.comptabilite.dashboard', compact(
            'totalDue', 'totalPaid', 'totalOverdue',
            'countPaid', 'countPartiallyPaid', 'countOverdue', 'countDue',
            'annees', 'filieres', 'classes',
            'labelsMois', 'dataEncaissements',
            'statsDepenses',
            'labelsMoisDepenses', 'dataDepensesMensuelles'
        ));
    }

    /**
     * Supprime un fournisseur
     */
    public function destroyFournisseur($id)
    {
        $fournisseur = \App\Models\ESBTPFournisseur::findOrFail($id);
        $fournisseur->delete();
        return redirect()->route('esbtp.comptabilite.fournisseurs')
            ->with('success', 'Fournisseur supprimé avec succès.');
    }
}
