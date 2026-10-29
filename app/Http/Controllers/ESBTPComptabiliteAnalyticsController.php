<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPComptabiliteConfiguration;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPBourse;
use App\Models\ESBTPTransactionFinanciere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPClasse;
use App\Models\User;
use App\Services\ComptabiliteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ESBTPComptabiliteAnalyticsController extends Controller
{
    /**
     * Constructeur avec injection des services optimisés
     */
    public function __construct(
        ComptabiliteService $comptabiliteService
    ) {
        $this->comptabiliteService = $comptabiliteService;

        $this->middleware('auth');
        $this->middleware('comptabilite.access');
    }


    /**
     * Configuration de la comptabilité
     */
    public function configurationComptabilite()
    {
        $configurations = \App\Models\ESBTPComptabiliteConfiguration::orderBy('cle')->get();
        $typesFrais = \App\Models\ESBTPTypeFrais::orderBy('nom')->get();

        return view('esbtp.comptabilite.configuration.index', compact('configurations', 'typesFrais'));
    }


    /**
     * Générer un rapport personnalisé via le builder avancé - Task #6
     */
    public function genererRapportPersonnalise(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'period' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'components' => 'required|array|min:1'
        ]);

        try {
            $reportingService = app(\App\Services\ReportingService::class);

            $parametres = [
                'type' => 'personnalise',
                'name' => $request->input('name'),
                'period' => $request->input('period'),
                'components' => $request->input('components'),
                'date_debut' => $request->input('date_debut', now()->startOfMonth()),
                'date_fin' => $request->input('date_fin', now()->endOfMonth()),
                'filters' => $request->input('filters', [])
            ];

            $rapport = $reportingService->genererRapportPersonnalise($parametres);

            // Exporter selon le format demandé
            $format = $request->input('format');
            $exportUrl = $reportingService->exporterDonnees($format, $rapport);

            // Enregistrer l'historique de génération
            $this->enregistrerHistoriqueRapport([
                'nom' => $parametres['name'],
                'type' => 'personnalise',
                'format' => $format,
                'parametres' => json_encode($parametres),
                'genere_par' => Auth::id(),
                'url_fichier' => $exportUrl
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Rapport généré avec succès',
                'url' => $exportUrl,
                'rapport' => $rapport
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur génération rapport personnalisé: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'parametres' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Programmer un rapport automatique - Task #6
     */
    public function programmerRapport(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'time' => 'required|date_format:H:i',
            'recipients' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'components' => 'required|array'
        ]);

        try {
            // Créer l'entrée de rapport programmé
            $rapportProgramme = \App\Models\ESBTPRapportProgramme::create([
                'nom' => $request->input('name'),
                'frequence' => $request->input('frequency'),
                'heure_envoi' => $request->input('time'),
                'destinataires' => $request->input('recipients'),
                'format_export' => $request->input('format'),
                'configuration' => json_encode([
                    'components' => $request->input('components'),
                    'filters' => $request->input('filters', []),
                    'include_predictive' => $request->input('include_predictive', false)
                ]),
                'est_actif' => true,
                'cree_par' => Auth::id(),
                'prochaine_execution' => $this->calculerProchaineExecution(
                    $request->input('frequency'),
                    $request->input('time')
                )
            ]);

            // Programmer le job dans Laravel Scheduler
            $this->programmerJobRapport($rapportProgramme);

            return response()->json([
                'success' => true,
                'message' => 'Rapport programmé avec succès',
                'id' => $rapportProgramme->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur programmation rapport: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'parametres' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la programmation: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Lister les rapports programmés - Task #6
     */
    public function listeRapportsProgrammes()
    {
        $rapportsProgrammes = \App\Models\ESBTPRapportProgramme::with(['createur'])
            ->orderBy('prochaine_execution')
            ->paginate(15);

        $statistiques = [
            'total_programmes' => \App\Models\ESBTPRapportProgramme::count(),
            'actifs' => \App\Models\ESBTPRapportProgramme::where('est_actif', true)->count(),
            'executions_reussies' => \App\Models\ESBTPHistoriqueRapport::where('statut', 'succes')
                ->whereDate('created_at', '>=', now()->subDays(30))->count(),
            'executions_echouees' => \App\Models\ESBTPHistoriqueRapport::where('statut', 'echec')
                ->whereDate('created_at', '>=', now()->subDays(30))->count()
        ];

        return view('esbtp.comptabilite.rapports.scheduled', compact('rapportsProgrammes', 'statistiques'));
    }


    /**
     * Modèles de rapports sauvegardés - Task #6
     */
    public function modelesRapports()
    {
        $modeles = \App\Models\ESBTPModeleRapport::with(['createur'])
            ->orderBy('nom')
            ->get();

        $categories = [
            'financier' => 'Rapports Financiers',
            'performance' => 'Analyses de Performance',
            'recouvrement' => 'Suivi Recouvrement',
            'predictif' => 'Analytics Prédictives'
        ];

        return view('esbtp.comptabilite.rapports.templates', compact('modeles', 'categories'));
    }


    /**
     * Sauvegarder un modèle de rapport - Task #6
     */
    public function sauvegarderModele(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:esbtp_modeles_rapports,nom',
            'description' => 'nullable|string|max:500',
            'categorie' => 'required|in:financier,performance,recouvrement,predictif',
            'components' => 'required|array',
            'parametres' => 'array'
        ]);

        try {
            $modele = \App\Models\ESBTPModeleRapport::create([
                'nom' => $request->input('nom'),
                'description' => $request->input('description'),
                'categorie' => $request->input('categorie'),
                'configuration' => json_encode([
                    'components' => $request->input('components'),
                    'parametres' => $request->input('parametres', []),
                    'version' => '1.0'
                ]),
                'est_partage' => $request->input('est_partage', false),
                'cree_par' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Modèle sauvegardé avec succès',
                'modele' => $modele
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }




    /**
     * Générer un reçu de paiement.
     */
    public function genererRecuPaiement($id)
    {
        $paiement = ESBTPPaiement::with([
            'inscription.etudiant.user',
            'inscription.filiere',
            'inscription.niveau',
            'inscription.anneeUniversitaire',
            'createdBy'
        ])->findOrFail($id);

        $pdf = PDF::loadView('esbtp.comptabilite.paiements.recu', compact('paiement'));
        return $pdf->stream('recu_paiement_' . $paiement->id . '.pdf');
    }

    // === MÉTHODES PRIVÉES POUR ANALYTICS PRÉDICTIVES ===









    // === MÉTHODES UTILITAIRES ===

    private function calculerProchaineExecution($frequence, $heure)
    {
        $now = now();
        $time = \Carbon\Carbon::createFromFormat('H:i', $heure);

        switch ($frequence) {
            case 'daily':
                $prochaine = $now->copy()->setTime($time->hour, $time->minute);
                if ($prochaine <= $now) {
                    $prochaine->addDay();
                }
                break;

            case 'weekly':
                $prochaine = $now->copy()->next(\Carbon\Carbon::MONDAY)->setTime($time->hour, $time->minute);
                break;

            case 'monthly':
                $prochaine = $now->copy()->startOfMonth()->addMonth()->setTime($time->hour, $time->minute);
                break;

            case 'quarterly':
                $prochaine = $now->copy()->startOfQuarter()->addQuarter()->setTime($time->hour, $time->minute);
                break;

            default:
                $prochaine = $now->copy()->addDay()->setTime($time->hour, $time->minute);
        }

        return $prochaine;
    }


    private function programmerJobRapport($rapportProgramme)
    {
        // Ici vous ajouteriez la logique pour programmer le job dans Laravel Scheduler
        // Pour l'instant, on enregistre juste l'information
        \Log::info('Rapport programmé créé', ['id' => $rapportProgramme->id]);
    }


    private function enregistrerHistoriqueRapport($donnees)
    {
        return \App\Models\ESBTPHistoriqueRapport::create($donnees);
    }




    // Méthodes simplifiées pour les calculs ML (à implémenter selon les besoins)
    private function getHistoriqueRecettes($mois) { /* Implementation */ return []; }


















































































}
