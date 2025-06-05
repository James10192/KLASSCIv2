<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ESBTPParent;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPResultat;
use App\Models\User;
use App\Models\Classe;
use PDF;
use App\Models\ESBTPFiliere;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use App\Models\ESBTPConfigMatiere;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\ESBTPCategorie;
use App\Models\ESBTPCertificat;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPCycle;
use Carbon\Carbon;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Helpers\SettingsHelper;

class ESBTPBulletinController extends Controller
{
    protected $absenceService;

    public function __construct(ESBTPAbsenceService $absenceService)
    {
        $this->absenceService = $absenceService;
    }

    /**
     * RÃ©cupÃ¨re les configurations depuis les settings pour les PDF
     */
    private function getPDFConfig()
    {
        return [
            // Informations de l'Ã©tablissement
            'school_name' => SettingsHelper::get('establishment.school_name', 'Ã‰cole SpÃ©ciale du BÃ¢timent et des Travaux Publics'),
            'school_type' => SettingsHelper::get('establishment.school_type', 'Enseignement SupÃ©rieur Technique'),
            'school_authorization' => SettingsHelper::get('establishment.authorization_number', ''),
            'school_address' => SettingsHelper::get('establishment.address', 'BP 2541 Yamoussoukro'),
            'school_phone' => SettingsHelper::get('establishment.phone', 'TÃ©l/Fax: 30 64 39 93 - Cel: 05 93 34 26 : 07 72 88 56'),
            'school_email' => SettingsHelper::get('establishment.email', 'esbtp@aviso.ci'),
            'school_website' => SettingsHelper::get('establishment.website', ''),
            'school_city' => SettingsHelper::get('establishment.city', 'Yamoussoukro'),
            'school_country' => SettingsHelper::get('establishment.country', 'CÃ´te d\'Ivoire'),
            'director_name' => SettingsHelper::get('establishment.director_name', ''),
            'director_title' => SettingsHelper::get('establishment.director_title', 'Directeur'),

            // Configuration PDF
            'pdf_margin_top' => SettingsHelper::get('pdf.margin_top', 15),
            'pdf_margin_bottom' => SettingsHelper::get('pdf.margin_bottom', 15),
            'pdf_margin_left' => SettingsHelper::get('pdf.margin_left', 10),
            'pdf_margin_right' => SettingsHelper::get('pdf.margin_right', 10),
            'pdf_font_size' => SettingsHelper::get('pdf.font_size', 12),
            'pdf_header_font_size' => SettingsHelper::get('pdf.header_font_size', 14),
            'pdf_title_font_size' => SettingsHelper::get('pdf.title_font_size', 16),
            'pdf_show_watermark' => SettingsHelper::get('pdf.show_watermark', false),
            'pdf_watermark_text' => SettingsHelper::get('pdf.watermark_text', 'CONFIDENTIEL'),
            'pdf_show_signature' => SettingsHelper::get('pdf.show_signature', true),
            'pdf_header_text' => SettingsHelper::get('pdf.header_text', ''),
            'pdf_footer_text' => SettingsHelper::get('pdf.footer_text', ''),

            // Logo - Corriger la clÃ© de 'school_logo' Ã  'logo'
            'logo' => SettingsHelper::get('establishment.logo', 'images/esbtp_logo.png'),
        ];
    }

    /**
     * PrÃ©pare le logo en base64 pour l'intÃ©gration dans le PDF
     */
    private function prepareLogoBase64($logoPath)
    {
        // Essayer d'abord le chemin depuis les settings
        $fullPath = public_path($logoPath);

        if (file_exists($fullPath)) {
            $logoType = pathinfo($fullPath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($fullPath);
            Log::info('Logo chargÃ© avec succÃ¨s depuis: ' . $fullPath);
            return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }

        // Essayer les chemins alternatifs
        $alternativePaths = [
            'images/esbtp_logo.png',
            'images/logo.jpeg',
            'images/esbtp_logo_white.png',
            'storage/logos/' . basename($logoPath)
        ];

        foreach ($alternativePaths as $altPath) {
            $fullPath = public_path($altPath);
            if (file_exists($fullPath)) {
                $logoType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($fullPath);
                Log::info('Logo alternatif chargÃ© avec succÃ¨s depuis: ' . $fullPath);
                return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
            }
        }

        Log::warning('Aucun logo trouvÃ© pour le chemin: ' . $logoPath);
        return null;
    }

    /**
     * Affiche la liste des bulletins avec filtre par annÃ©e et classe
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // PÃ©riodes disponibles (dÃ©finir les pÃ©riodes pour la vue)
        $periodes = [
            (object)['id' => 'semestre1', 'nom' => 'Premier Semestre', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)],
            (object)['id' => 'semestre2', 'nom' => 'DeuxiÃ¨me Semestre', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)],
            (object)['id' => 'annuel', 'nom' => 'Annuel', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)]
        ];

        // Statistiques pour les widgets
        $stats = [
            'total' => ESBTPBulletin::count(),
            'published' => ESBTPBulletin::where('is_published', true)->count(),
            'pending' => ESBTPBulletin::where('is_published', false)->count(),
            'periodes' => count($periodes)
        ];

        // Valeurs par dÃ©faut filtre
        $classe_id = $request->input('classe_id');
        $annee_id = $request->input('annee_universitaire_id',
            ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null);
        $periode_id = $request->input('periode_id');

        $query = ESBTPBulletin::with(['etudiant', 'classe', 'anneeUniversitaire']);

        // Application des filtres
        if ($classe_id) {
            $query->where('classe_id', $classe_id);
        }

        if ($annee_id) {
            $query->where('annee_universitaire_id', $annee_id);
        }

        if ($periode_id) {
            $query->where('periode', $periode_id);
        }

        // Utiliser paginate() au lieu de get() pour permettre l'utilisation de appends()
        $bulletins = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('esbtp.bulletins.index', compact(
            'bulletins',
            'classes',
            'anneesUniversitaires',
            'classe_id',
            'annee_id',
            'periodes',
            'periode_id',
            'stats'
        ));
    }

    /**
     * Affiche le formulaire de sÃ©lection d'Ã©tudiant pour crÃ©er un bulletin
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeActuelle = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        return view('esbtp.bulletins.create', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Enregistre un nouveau bulletin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
            'appreciation_generale' => 'nullable|string',
            'decision_conseil' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'L\'Ã©tudiant est obligatoire',
            'classe_id.required' => 'La classe est obligatoire',
            'annee_universitaire_id.required' => 'L\'annÃ©e universitaire est obligatoire',
            'periode.required' => 'La pÃ©riode est obligatoire',
        ]);

        DB::beginTransaction();
        try {
            // VÃ©rifier si l'Ã©tudiant est bien inscrit dans cette classe pour cette annÃ©e
            $etudiantInscrit = ESBTPEtudiant::findOrFail($request->etudiant_id)
                ->inscriptions()
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->exists();

            if (!$etudiantInscrit) {
                return redirect()->back()
                    ->with('error', 'L\'Ã©tudiant n\'est pas inscrit dans cette classe pour cette annÃ©e universitaire')
                    ->withInput();
            }

            // VÃ©rifier s'il existe dÃ©jÃ  un bulletin pour cet Ã©tudiant, cette classe, cette annÃ©e et cette pÃ©riode
            $bulletinExistant = ESBTPBulletin::where('etudiant_id', $request->etudiant_id)
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->where('periode', $request->periode)
                ->exists();

            if ($bulletinExistant) {
                return redirect()->back()
                    ->with('error', 'Un bulletin existe dÃ©jÃ  pour cet Ã©tudiant pour cette pÃ©riode')
                    ->withInput();
            }

            // CrÃ©er le bulletin
            $bulletin = new ESBTPBulletin();
            $bulletin->etudiant_id = $request->etudiant_id;
            $bulletin->classe_id = $request->classe_id;
            $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
            $bulletin->periode = $request->periode;
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->user_id = Auth::id();
            $bulletin->save();

            // RÃ©cupÃ©rer toutes les matiÃ¨res de la classe
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $matieres = $classe->matieres;

            // Pour chaque matiÃ¨re, calculer la moyenne et crÃ©er un rÃ©sultat
            foreach ($matieres as $matiere) {
                // RÃ©cupÃ©rer toutes les Ã©valuations de cette matiÃ¨re pour cette classe
                $evaluations = $matiere ? $matiere->evaluations()
                    ->where('classe_id', $classe->id)
                    ->where('periode', $request->periode)
                    ->get() : collect();

                Log::info('RÃ©cupÃ©ration des Ã©valuations', [
                    'matiere_id' => $matiere->id,
                    'nombre_evaluations' => $evaluations->count(),
                    'classe_id' => $classe->id,
                    'periode' => $request->periode
                ]);

                if (!$evaluations || $evaluations->isEmpty()) {
                    continue; // Passer Ã  la matiÃ¨re suivante s'il n'y a pas d'Ã©valuations
                }

                // RÃ©cupÃ©rer les notes de l'Ã©tudiant pour ces Ã©valuations
                $notes = ESBTPNote::whereIn('evaluation_id', $evaluations->pluck('id'))
                    ->where('etudiant_id', $request->etudiant_id)
                    ->get();

                if (!$notes || $notes->isEmpty()) {
                    continue; // Passer Ã  la matiÃ¨re suivante s'il n'y a pas de notes
                }

                // Calculer la moyenne
                $sommeNotes = 0;
                $sommeCoefficients = 0;

                foreach ($notes as $note) {
                    $evaluation = $evaluations->where('id', $note->evaluation_id)->first();
                    $sommeNotes += ($note->valeur / $evaluation->bareme) * 20 * $evaluation->coefficient;
                    $sommeCoefficients += $evaluation->coefficient;
                }

                $moyenne = $sommeCoefficients > 0 ? $sommeNotes / $sommeCoefficients : null;

                // RÃ©cupÃ©rer le coefficient de la matiÃ¨re pour cette classe
                $pivotData = $classe->matieres()->where('matiere_id', $matiere->id)->first()->pivot;
                $coefficient = $pivotData->coefficient ?? 1;

                // CrÃ©er le rÃ©sultat pour cette matiÃ¨re
                $resultat = new ESBTPResultatMatiere();
                $resultat->bulletin_id = $bulletin->id;
                $resultat->matiere_id = $matiere->id;
                $resultat->moyenne = $moyenne;
                $resultat->coefficient = $coefficient;
                $resultat->commentaire = null;
                $resultat->save();
            }

            // Calculer et mettre Ã  jour la moyenne gÃ©nÃ©rale du bulletin
            $this->calculerMoyenneGenerale($bulletin);

            // DÃ©terminer la pÃ©riode pour le calcul des absences
            // Par exemple: utiliser la date de dÃ©but et de fin du semestre
            $anneeUniversitaire = ESBTPAnneeUniversitaire::find($request->annee_universitaire_id);
            if ($anneeUniversitaire) {
                // Exemple: si periode = 'S1' (1er semestre)
                if ($request->periode == 'S1') {
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = Carbon::parse($dateDebut)->addMonths(4)->format('Y-m-d'); // Environ 4 mois pour un semestre
                } else if ($request->periode == 'S2') {
                    $dateDebut = Carbon::parse($anneeUniversitaire->date_debut)->addMonths(4)->format('Y-m-d');
                    $dateFin = $anneeUniversitaire->date_fin;
                } else {
                    // Pour les pÃ©riodes diffÃ©rentes ou pÃ©riodes trimestrielles
                    // Adapter la logique selon vos besoins
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = $anneeUniversitaire->date_fin;
                }

                // Calculer les absences pour la pÃ©riode du bulletin
                $donneeAbsences = $this->calculerAbsencesPourBulletin(
                    $request->etudiant_id,
                    $request->classe_id,
                    $dateDebut,
                    $dateFin
                );

                // IntÃ©grer les absences au bulletin
                $bulletin = $this->integrerAbsencesAuBulletin($bulletin, $donneeAbsences);
            }

            DB::commit();
            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a Ã©tÃ© crÃ©Ã© avec succÃ¨s');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la crÃ©ation du bulletin: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Calcule et met Ã  jour la moyenne gÃ©nÃ©rale d'un bulletin
     */
    private function calculerMoyenneGenerale(ESBTPBulletin $bulletin)
    {
        Log::info('Calcul de la moyenne gÃ©nÃ©rale pour le bulletin ' . $bulletin->id);

        try {
            $resultats = $bulletin->resultats;
            Log::info('Nombre de rÃ©sultats trouvÃ©s: ' . $resultats->count());

            if ($resultats->isEmpty()) {
                Log::info('Aucun rÃ©sultat trouvÃ© pour le bulletin ' . $bulletin->id);
                $bulletin->moyenne_generale = null;
                $bulletin->save();
                return;
            }

            $sommePoints = 0;
            $sommeCoefficients = 0;

            foreach ($resultats as $resultat) {
                if ($resultat->moyenne !== null) {
                    Log::info('RÃ©sultat pour matiÃ¨re ' . $resultat->matiere_id . ': moyenne=' . $resultat->moyenne . ', coefficient=' . $resultat->coefficient);
                    $sommePoints += $resultat->moyenne * $resultat->coefficient;
                    $sommeCoefficients += $resultat->coefficient;
                } else {
                    Log::info('RÃ©sultat ignorÃ© pour matiÃ¨re ' . $resultat->matiere_id . ' (moyenne null)');
                }
            }

            Log::info('Somme des points: ' . $sommePoints . ', Somme des coefficients: ' . $sommeCoefficients);
            $moyenneGenerale = $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : null;
            Log::info('Moyenne gÃ©nÃ©rale calculÃ©e: ' . $moyenneGenerale);

            $bulletin->moyenne_generale = $moyenneGenerale;
            $bulletin->save();
            Log::info('Moyenne gÃ©nÃ©rale enregistrÃ©e pour le bulletin ' . $bulletin->id);

            // Calculer le rang si la moyenne a changÃ©
            $this->calculerRang($bulletin);
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul de la moyenne gÃ©nÃ©rale: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Calcule et met Ã  jour le rang de l'Ã©tudiant dans sa classe
     */
    private function calculerRang($bulletin)
    {
        // RÃ©cupÃ©rer tous les bulletins de la mÃªme classe pour la mÃªme pÃ©riode
        $bulletins = ESBTPBulletin::where('classe_id', $bulletin->classe_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->where('periode', $bulletin->periode)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        // Mettre Ã  jour l'effectif de la classe
        $bulletin->effectif_classe = $bulletins->count();

        // Trouver le rang de l'Ã©tudiant
        foreach ($bulletins as $index => $b) {
            if ($b->id === $bulletin->id) {
                $bulletin->rang = $index + 1;
                break;
            }
        }

        $bulletin->save();
    }

    /**
     * Affiche un bulletin spÃ©cifique.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPBulletin $bulletin)
    {
        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere', 'user']);
        return view('esbtp.bulletins.show', compact('bulletin'));
    }

    /**
     * Affiche le formulaire de modification d'un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPBulletin $bulletin)
    {
        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere']);
        return view('esbtp.bulletins.edit', compact('bulletin'));
    }

    /**
     * Met Ã  jour un bulletin spÃ©cifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPBulletin $bulletin)
    {
        $request->validate([
            'resultats' => 'required|array',
            'resultats.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne' => 'nullable|numeric|min:0|max:20',
            'resultats.*.coefficient' => 'required|numeric|min:0',
            'resultats.*.commentaire' => 'nullable|string',
            'appreciation_generale' => 'nullable|string',
            'decision_conseil' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Mettre Ã  jour les informations du bulletin
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->save();

            // Mettre Ã  jour les rÃ©sultats par matiÃ¨re
            foreach ($request->resultats as $resultatData) {
                $matiereId = $resultatData['matiere_id'];
                $moyenne = $resultatData['moyenne'] !== null && $resultatData['moyenne'] !== ''
                    ? $resultatData['moyenne'] : null;

                $resultat = ESBTPResultatMatiere::where('bulletin_id', $bulletin->id)
                    ->where('matiere_id', $matiereId)
                    ->first();

                if ($resultat) {
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                } else {
                    $resultat = new ESBTPResultatMatiere();
                    $resultat->bulletin_id = $bulletin->id;
                    $resultat->matiere_id = $matiereId;
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                }
            }

            // Recalculer la moyenne gÃ©nÃ©rale
            $this->calculerMoyenneGenerale($bulletin);

            DB::commit();
            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a Ã©tÃ© mis Ã  jour avec succÃ¨s');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise Ã  jour du bulletin: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime un bulletin spÃ©cifique.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPBulletin $bulletin)
    {
        try {
            $bulletin->delete();
            return redirect()->route('esbtp.bulletins.index')->with('success', 'Bulletin supprimÃ© avec succÃ¨s.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * GÃ©nÃ¨re un PDF du bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function genererPDF(ESBTPBulletin $bulletin)
    {
        try {
            Log::info('DÃ©but de la gÃ©nÃ©ration du PDF pour le bulletin #' . $bulletin->id);

            // Charger toutes les relations nÃ©cessaires avec eager loading, y compris les relations imbriquÃ©es
            $bulletin->load([
                'etudiant',
                'classe.niveauEtude',
                'classe.filiere',
                'anneeUniversitaire',
                'resultats.matiere',
                'user'
            ]);

            // VÃ©rifier que les relations essentielles sont chargÃ©es
            if (!$bulletin->etudiant) {
                Log::error('Relation etudiant manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("L'Ã©tudiant associÃ© Ã  ce bulletin n'a pas Ã©tÃ© trouvÃ©. Veuillez vÃ©rifier que l'Ã©tudiant existe et est correctement associÃ© au bulletin.");
            }

            if (!$bulletin->classe) {
                Log::error('Relation classe manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("La classe associÃ©e Ã  ce bulletin n'a pas Ã©tÃ© trouvÃ©e. Veuillez vÃ©rifier que la classe existe et est correctement associÃ©e au bulletin.");
            }

            if (!$bulletin->anneeUniversitaire) {
                Log::error('Relation anneeUniversitaire manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("L'annÃ©e universitaire associÃ©e Ã  ce bulletin n'a pas Ã©tÃ© trouvÃ©e. Veuillez vÃ©rifier que l'annÃ©e universitaire existe et est correctement associÃ©e au bulletin.");
            }

            // Calculer la moyenne gÃ©nÃ©rale si pas dÃ©jÃ  fait
            if (!$bulletin->moyenne_generale) {
                try {
                    $bulletin->calculerMoyenneGenerale();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la moyenne gÃ©nÃ©rale: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->moyenne_generale = 0;
                }
            }

            // Calculer la mention si pas dÃ©jÃ  fait
            if (!$bulletin->mention) {
                try {
                    $bulletin->calculerMention();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la mention: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->mention = 'Non calculÃ©e';
                }
            }

            // Calculer le rang si pas dÃ©jÃ  fait
            if (!$bulletin->rang) {
                try {
                    $bulletin->calculerRang();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul du rang: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->rang = 0;
                }
            }

            // Calculer les absences justifiÃ©es et non justifiÃ©es
            try {
                $absences = $this->calculerAbsencesDetailees($bulletin);
                $bulletin->absences_justifiees = $absences['justifiees'];
                $bulletin->absences_non_justifiees = $absences['non_justifiees'];
                $bulletin->total_absences = $absences['total'];
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des absences: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $bulletin->absences_justifiees = 0;
                $bulletin->absences_non_justifiees = 0;
                $bulletin->total_absences = 0;
            }

            // Si les absences sont toujours Ã  zÃ©ro, essayer la mÃ©thode basÃ©e sur l'attendance
            if ($bulletin->absences_justifiees == 0 && $bulletin->absences_non_justifiees == 0) {
                try {
                    Log::info('Tentative de calcul des absences via le service pour le bulletin #' . $bulletin->id);

                    $absencesAttendance = $this->absenceService->calculerDetailAbsences(
                        $bulletin->etudiant_id,
                        $bulletin->classe_id,
                        $bulletin->anneeUniversitaire->date_debut,
                        $bulletin->anneeUniversitaire->date_fin
                    );

                    $bulletin->absences_justifiees = $absencesAttendance['justifiees'];
                    $bulletin->absences_non_justifiees = $absencesAttendance['non_justifiees'];
                    $bulletin->total_absences = $absencesAttendance['total'];
                    Log::info('Calcul des absences via le service rÃ©ussi: ' . json_encode($absencesAttendance));
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul des absences via le service: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                }
            }

            // Grouper les rÃ©sultats par type d'enseignement (gÃ©nÃ©ral ou technique)
            try {
                // S'assurer que les rÃ©sultats sont chargÃ©s
                if ($bulletin->resultats->isEmpty()) {
                    Log::warning('Aucun rÃ©sultat trouvÃ© pour le bulletin #' . $bulletin->id);
                }

                // VÃ©rifier que chaque rÃ©sultat a une matiÃ¨re associÃ©e
                foreach ($bulletin->resultats as $resultat) {
                    if (!$resultat->matiere) {
                        Log::warning('RÃ©sultat #' . $resultat->id . ' sans matiÃ¨re associÃ©e pour le bulletin #' . $bulletin->id);
                    }
                }

                $resultatsGeneraux = $bulletin->resultats->filter(function($resultat) {
                    return $resultat->matiere && $resultat->matiere->type_formation == 'generale';
                });

                $resultatsTechniques = $bulletin->resultats->filter(function($resultat) {
                    return $resultat->matiere && $resultat->matiere->type_formation == 'technologique_professionnelle';
                });

                // VÃ©rifier si des rÃ©sultats ont Ã©tÃ© trouvÃ©s aprÃ¨s filtrage
                if ($resultatsGeneraux->isEmpty() && $resultatsTechniques->isEmpty()) {
                    Log::warning('Aucun rÃ©sultat trouvÃ© aprÃ¨s filtrage par type de formation pour le bulletin #' . $bulletin->id);
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors du filtrage des rÃ©sultats: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
            }

            // Calculer les moyennes par type d'enseignement
            try {
                $moyenneGenerale = $bulletin->calculerMoyenneParType('generale');
                $moyenneTechnique = $bulletin->calculerMoyenneParType('technologique_professionnelle');
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des moyennes par type: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $moyenneGenerale = 0;
                $moyenneTechnique = 0;
            }

            // GÃ©nÃ©rer le PDF avec les configurations de l'Ã©cole
            $config = $this->getPDFConfig();

            $data = [
                'bulletin' => $bulletin,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneTechnique' => $moyenneTechnique,
                'absencesJustifiees' => $bulletin->absences_justifiees,
                'absencesNonJustifiees' => $bulletin->absences_non_justifiees,
                'absences_justifiees' => $bulletin->absences_justifiees,
                'absences_non_justifiees' => $bulletin->absences_non_justifiees,
                'config' => $config
            ];

            // Log des variables d'absences pour debugging
            Log::info('Variables d\'absence pour le PDF dans genererPDF:', [
                'bulletin_absences_justifiees' => $bulletin->absences_justifiees ?? 'Non dÃ©fini',
                'bulletin_absences_non_justifiees' => $bulletin->absences_non_justifiees ?? 'Non dÃ©fini',
                'data_absencesJustifiees' => $data['absencesJustifiees'] ?? 'Non dÃ©fini',
                'data_absencesNonJustifiees' => $data['absencesNonJustifiees'] ?? 'Non dÃ©fini',
                'data_absences_justifiees' => $data['absences_justifiees'] ?? 'Non dÃ©fini',
                'data_absences_non_justifiees' => $data['absences_non_justifiees'] ?? 'Non dÃ©fini',
            ]);

            // PrÃ©parer le logo en base64
            $data['logoBase64'] = $this->prepareLogoBase64($config['school_logo']);

            try {
                Log::info('Chargement de la vue PDF pour le bulletin #' . $bulletin->id);
                $pdf = PDF::loadView('esbtp.bulletins.bulletin-pdf', $data);
                $pdf->setPaper('a4', 'portrait');
                $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

                // Nom du fichier PDF
                $filename = 'bulletin_' .
                            ($bulletin->etudiant ? $bulletin->etudiant->matricule : 'unknown') . '_' .
                            ($bulletin->classe ? $bulletin->classe->code : 'unknown') . '_' .
                            $bulletin->periode . '_' .
                            ($bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire->libelle : 'unknown') . '.pdf';

                Log::info('PDF gÃ©nÃ©rÃ© avec succÃ¨s pour le bulletin #' . $bulletin->id);
                // TÃ©lÃ©charger le PDF
                return $pdf->download($filename);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la gÃ©nÃ©ration du PDF: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());

                // Enregistrer des informations supplÃ©mentaires pour le dÃ©bogage
                Log::error('DonnÃ©es du bulletin: ' . json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));

                return back()->with('error', 'Une erreur est survenue lors de la gÃ©nÃ©ration du PDF: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la prÃ©paration des donnÃ©es pour le PDF: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            // Enregistrer des informations supplÃ©mentaires pour le dÃ©bogage
            if (isset($bulletin)) {
                Log::error('DonnÃ©es du bulletin: ' . json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));
            }

            return back()->with('error', 'Une erreur est survenue lors de la gÃ©nÃ©ration du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Calcule les absences dÃ©taillÃ©es pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return array
     */
    private function calculerAbsencesDetailees($bulletin)
    {
        try {
            \Log::info('DÃ©but du calcul des absences dÃ©taillÃ©es pour le bulletin #' . $bulletin->id);

            // VÃ©rifier que les relations nÃ©cessaires sont chargÃ©es
            if (!$bulletin->etudiant || !$bulletin->classe || !$bulletin->anneeUniversitaire) {
                \Log::error('Relations essentielles manquantes pour le calcul des absences du bulletin #' . $bulletin->id);
                throw new \Exception("DonnÃ©es incomplÃ¨tes pour calculer les absences. Veuillez vÃ©rifier que l'Ã©tudiant, la classe et l'annÃ©e universitaire sont correctement dÃ©finis.");
            }

            // VÃ©rifier que les dates de l'annÃ©e universitaire sont dÃ©finies
            if (!$bulletin->anneeUniversitaire->date_debut || !$bulletin->anneeUniversitaire->date_fin) {
                \Log::error('Dates de l\'annÃ©e universitaire non dÃ©finies pour le bulletin #' . $bulletin->id);
                throw new \Exception("Les dates de dÃ©but et de fin de l'annÃ©e universitaire ne sont pas dÃ©finies.");
            }

            // Utiliser le service d'absences pour calculer les absences
            $absences = $this->absenceService->calculerDetailAbsences(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->anneeUniversitaire->date_debut,
                $bulletin->anneeUniversitaire->date_fin
            );

            \Log::info('Absences dÃ©taillÃ©es calculÃ©es avec succÃ¨s pour le bulletin #' . $bulletin->id, $absences);

            return $absences;

        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul des absences dÃ©taillÃ©es: ' . $e->getMessage(), [
                'bulletin_id' => $bulletin->id,
                'etudiant_id' => $bulletin->etudiant_id ?? 'non dÃ©fini',
                'classe_id' => $bulletin->classe_id ?? 'non dÃ©fini',
                'trace' => $e->getTraceAsString()
            ]);

            // Retourner des valeurs par dÃ©faut en cas d'erreur
            return [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0,
                'detail' => [
                    'justifiees' => [],
                    'non_justifiees' => []
                ]
            ];
        }
    }

    /**
     * Calcule le total des heures d'absence pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return int
     */
    private function calculerTotalAbsences($bulletin)
    {
        \Log::info('Calcul du total des absences pour le bulletin #' . $bulletin->id);

        try {
            // Utiliser le service d'absences pour calculer les absences
            $absences = $this->absenceService->calculerDetailAbsences(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->anneeUniversitaire->date_debut,
                $bulletin->anneeUniversitaire->date_fin
            );

            \Log::info('Total des absences calculÃ©: ' . $absences['total'] . ' heures');

            return $absences['total'];
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul du total des absences: ' . $e->getMessage(), [
                'bulletin_id' => $bulletin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return 0;
        }
    }

    /**
     * GÃ©nÃ¨re les bulletins pour une classe entiÃ¨re.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function genererClasseBulletins(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
        ]);

        try {
            Log::info('DÃ©but de la gÃ©nÃ©ration des bulletins', $request->all());
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($request->annee_universitaire_id);

            // RÃ©cupÃ©rer tous les Ã©tudiants inscrits dans cette classe pour cette annÃ©e
            try {
                Log::info('RÃ©cupÃ©ration des Ã©tudiants inscrits');

                // Utiliser une requÃªte directe Ã  la place de la relation 'inscriptions'
                $etudiantIds = DB::table('esbtp_inscriptions')
                    ->where('classe_id', $request->classe_id)
                    ->where('annee_universitaire_id', $request->annee_universitaire_id)
                    ->where('status', 'active')
                    ->pluck('etudiant_id');

                $etudiants = ESBTPEtudiant::whereIn('id', $etudiantIds)->get();

                // Si aucun Ã©tudiant n'est trouvÃ© par cette mÃ©thode, essayer de rÃ©cupÃ©rer tous les Ã©tudiants de la classe
                if ($etudiants->isEmpty()) {
                    Log::info('Aucun Ã©tudiant trouvÃ© via les inscriptions, recherche alternative');
                    $etudiants = ESBTPEtudiant::where('classe_id', $request->classe_id)->get();
                }

                Log::info('Nombre d\'Ã©tudiants trouvÃ©s: ' . $etudiants->count());

                if ($etudiants->isEmpty()) {
                    Log::warning('Aucun Ã©tudiant trouvÃ© pour la classe ' . $classe->name);
                    return redirect()->route('esbtp.bulletins.index')
                        ->with('warning', 'Aucun Ã©tudiant trouvÃ© pour la classe sÃ©lectionnÃ©e.');
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la rÃ©cupÃ©ration des Ã©tudiants: ' . $e->getMessage());
                Log::error('SQL: ' . $e->getTraceAsString());
                throw $e;
            }

            $bulletinsGeneres = 0;

            foreach ($etudiants as $etudiant) {
                Log::info('Traitement de l\'Ã©tudiant: ' . $etudiant->id . ' - ' . $etudiant->nom . ' ' . $etudiant->prenoms);
                // VÃ©rifier si un bulletin existe dÃ©jÃ  pour cet Ã©tudiant
                try {
                    $bulletinExistant = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                        ->where('classe_id', $request->classe_id)
                        ->where('annee_universitaire_id', $request->annee_universitaire_id)
                        ->where('periode', $request->periode)
                        ->exists();

                    if ($bulletinExistant) {
                        Log::info('Bulletin existant pour l\'Ã©tudiant: ' . $etudiant->id);
                        continue; // Passer Ã  l'Ã©tudiant suivant
                    }
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la vÃ©rification du bulletin existant: ' . $e->getMessage());
                    Log::error('SQL: ' . $e->getTraceAsString());
                    throw $e;
                }

                // CrÃ©er une requÃªte simulÃ©e pour rÃ©utiliser la mÃ©thode store
                $bulletinRequest = new Request([
                    'etudiant_id' => $etudiant->id,
                    'classe_id' => $request->classe_id,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'periode' => $request->periode,
                    'appreciation_generale' => null,
                    'decision_conseil' => null,
                ]);

                // Appeler la mÃ©thode store mais sans rediriger
                try {
                    DB::beginTransaction();

                    // CrÃ©er le bulletin
                    $bulletin = new ESBTPBulletin();
                    $bulletin->etudiant_id = $etudiant->id;
                    $bulletin->classe_id = $request->classe_id;
                    $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
                    $bulletin->periode = $request->periode;
                    $bulletin->appreciation_generale = null;
                    $bulletin->decision_conseil = null;
                    $bulletin->user_id = Auth::id();
                    $bulletin->save();
                    Log::info('Bulletin crÃ©Ã©: ' . $bulletin->id);

                    // RÃ©cupÃ©rer toutes les matiÃ¨res de la classe
                    $matieres = $classe->matieres;
                    Log::info('Nombre de matiÃ¨res trouvÃ©es: ' . $matieres->count());

                    // Pour chaque matiÃ¨re, calculer la moyenne et crÃ©er un rÃ©sultat
                    foreach ($matieres as $matiere) {
                        Log::info('Traitement de la matiÃ¨re: ' . $matiere->id . ' - ' . ($matiere->nom ?? $matiere->name ?? 'Nom inconnu'));

                        // VÃ©rifier si la matiÃ¨re est valide
                        if (!$matiere || !$matiere->id) {
                            Log::warning('MatiÃ¨re invalide trouvÃ©e');
                            continue;
                        }

                        // RÃ©cupÃ©rer toutes les Ã©valuations de cette matiÃ¨re pour cette classe
                        try {
                            $evaluations = $matiere->evaluations()
                                ->where('classe_id', $classe->id)
                                ->where('periode', $request->periode)
                                ->get();

                            Log::info('Nombre d\'Ã©valuations trouvÃ©es: ' . $evaluations->count(), [
                                'matiere_id' => $matiere->id,
                                'classe_id' => $classe->id,
                                'periode' => $request->periode
                            ]);

                            if (!$evaluations || $evaluations->isEmpty()) {
                                Log::info('Pas d\'Ã©valuations pour la matiÃ¨re et la pÃ©riode: ' . $matiere->id, [
                                    'periode' => $request->periode
                                ]);

                                // CrÃ©er un rÃ©sultat vide pour cette matiÃ¨re
                                try {
                                    // RÃ©cupÃ©rer le coefficient de la matiÃ¨re pour cette classe
                                    $coefficient = 1; // Valeur par dÃ©faut
                                    try {
                                        $pivot = DB::table('esbtp_classe_matiere')
                                            ->where('classe_id', $classe->id)
                                            ->where('matiere_id', $matiere->id)
                                            ->first();

                                        if ($pivot && isset($pivot->coefficient)) {
                                            $coefficient = $pivot->coefficient;
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Erreur lors de la rÃ©cupÃ©ration du coefficient: ' . $e->getMessage());
                                    }

                                    $resultat = new ESBTPResultatMatiere();
                                    $resultat->bulletin_id = $bulletin->id;
                                    $resultat->matiere_id = $matiere->id;
                                    $resultat->moyenne = null; // Pas de moyenne car pas d'Ã©valuations
                                    $resultat->coefficient = $coefficient;
                                    $resultat->commentaire = null;
                                    $resultat->save();
                                    Log::info('RÃ©sultat vide crÃ©Ã© pour la matiÃ¨re: ' . $matiere->id);
                                } catch (\Exception $e) {
                                    Log::error('Erreur lors de la crÃ©ation du rÃ©sultat vide: ' . $e->getMessage());
                                }

                                continue; // Passer Ã  la matiÃ¨re suivante s'il n'y a pas d'Ã©valuations
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la rÃ©cupÃ©ration des Ã©valuations: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            continue; // Passer Ã  la matiÃ¨re suivante en cas d'erreur
                        }

                        // RÃ©cupÃ©rer les notes de l'Ã©tudiant pour ces Ã©valuations
                        try {
                            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
                                ->where('classe_id', $request->classe_id)
                                ->whereHas('evaluation', function($query) use ($request) {
                                    $query->where('annee_universitaire_id', $request->annee_universitaire_id);
                                    if ($request->periode != 'annuel') {
                                        $query->where('periode', $request->periode);
                                    }
                                })
                                ->get();

                            Log::info('Nombre de notes trouvÃ©es: ' . $notes->count());

                            if (!$notes || $notes->isEmpty()) {
                                Log::info('Pas de notes pour l\'Ã©tudiant: ' . $etudiant->id . ' dans la matiÃ¨re: ' . $matiere->id);
                                continue; // Passer Ã  la matiÃ¨re suivante s'il n'y a pas de notes
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la rÃ©cupÃ©ration des notes: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            throw $e;
                        }

                        // Calculer la moyenne
                        $sommeNotes = 0;
                        $sommeCoefficients = 0;

                        foreach ($notes as $note) {
                            $evaluation = $notes->where('evaluation_id', $note->evaluation_id)->first();
                            $sommeNotes += ($note->valeur / $evaluation->bareme) * 20 * $evaluation->coefficient;
                            $sommeCoefficients += $evaluation->coefficient;
                        }

                        $moyenne = $sommeCoefficients > 0 ? $sommeNotes / $sommeCoefficients : null;

                        // RÃ©cupÃ©rer le coefficient de la matiÃ¨re pour cette classe
                        try {
                            $pivotData = $classe->matieres()->where('matiere_id', $matiere->id)->first()->pivot;
                            $coefficient = $pivotData->coefficient ?? 1;
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la rÃ©cupÃ©ration du coefficient: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            $coefficient = 1; // Valeur par dÃ©faut en cas d'erreur
                        }

                        // CrÃ©er le rÃ©sultat pour cette matiÃ¨re
                        try {
                            $resultat = new ESBTPResultatMatiere();
                            $resultat->bulletin_id = $bulletin->id;
                            $resultat->matiere_id = $matiere->id;
                            $resultat->moyenne = $moyenne;
                            $resultat->coefficient = $coefficient;
                            $resultat->commentaire = null;
                            $resultat->save();
                            Log::info('RÃ©sultat crÃ©Ã© pour la matiÃ¨re: ' . $matiere->id . ' avec moyenne: ' . $moyenne);
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la crÃ©ation du rÃ©sultat: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            throw $e;
                        }
                    }

                    // Calculer et mettre Ã  jour la moyenne gÃ©nÃ©rale du bulletin
                    try {
                        Log::info('Calcul de la moyenne gÃ©nÃ©rale pour le bulletin: ' . $bulletin->id);
                        $this->calculerMoyenneGenerale($bulletin);
                    } catch (\Exception $e) {
                        Log::error('Erreur lors du calcul de la moyenne gÃ©nÃ©rale: ' . $e->getMessage());
                        Log::error('SQL: ' . $e->getTraceAsString());
                        throw $e;
                    }

                    DB::commit();
                    $bulletinsGeneres++;
                    Log::info('Bulletin gÃ©nÃ©rÃ© avec succÃ¨s pour l\'Ã©tudiant: ' . $etudiant->id);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur lors de la gÃ©nÃ©ration du bulletin pour l\'Ã©tudiant: ' . $etudiant->id . ' - ' . $e->getMessage());
                    Log::error('SQL: ' . $e->getTraceAsString());
                    // Continuer avec l'Ã©tudiant suivant
                }
            }

            if ($bulletinsGeneres > 0) {
                Log::info('Bulletins gÃ©nÃ©rÃ©s avec succÃ¨s: ' . $bulletinsGeneres);
                return redirect()->route('esbtp.bulletins.index')
                    ->with('success', $bulletinsGeneres . ' bulletins ont Ã©tÃ© gÃ©nÃ©rÃ©s avec succÃ¨s');
            } else {
                Log::info('Aucun bulletin gÃ©nÃ©rÃ©');
                return redirect()->route('esbtp.bulletins.index')
                    ->with('info', 'Aucun nouveau bulletin n\'a Ã©tÃ© gÃ©nÃ©rÃ©. Tous les bulletins existent dÃ©jÃ  ou il n\'y a pas de donnÃ©es suffisantes.');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la gÃ©nÃ©ration des bulletins: ' . $e->getMessage());
            Log::error('SQL: ' . $e->getTraceAsString());

            return redirect()->route('esbtp.bulletins.index')
                ->with('error', 'Une erreur est survenue lors de la gÃ©nÃ©ration des bulletins: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page de sÃ©lection pour les bulletins
     *
     * @return \Illuminate\Http\Response
     */
    public function select()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeActuelle = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        return view('esbtp.bulletins.select', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Affiche les rÃ©sultats des Ã©tudiants
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resultats(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'semestre' => 'nullable|in:1,2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $classe_id = $request->classe_id;
        $semestre = $request->semestre;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses') ? $request->include_all_statuses : true; // Par dÃ©faut, inclure tous les statuts
        $periode = $semestre; // Map semestre to periode for view compatibility

        Log::info('Resultats method called with params', [
            'classe_id' => $classe_id,
            'semestre' => $semestre,
            'annee_universitaire_id' => $annee_universitaire_id,
            'include_all_statuses' => $include_all_statuses
        ]);

        // If classe_id is provided, get the corresponding academic year
        if ($classe_id && !$annee_universitaire_id) {
            $classe = ESBTPClasse::find($classe_id);
            if ($classe && $classe->annee_universitaire_id) {
                $annee_universitaire_id = $classe->annee_universitaire_id;
            }
        }

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
        }

        // For view compatibility
        $annee_id = $annee_universitaire_id;

        // Get annee object for view display
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // Always load all active classes with relationships, regardless of filters
        $classes = ESBTPClasse::with(['filiere', 'niveau'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $periodes = ['1' => 'Semestre 1', '2' => 'Semestre 2'];
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Get selected classe information
        $classeObj = null;
        $classe = null;
        if ($classe_id) {
            $classeObj = ESBTPClasse::with('filiere')->find($classe_id);
            $classe = $classeObj; // Alias for view compatibility
        }

        // Get students and notes
        $etudiants = []; // Renamed from $students for view compatibility
        $notes = [];
        $moyennes = []; // For storing student averages
        $rangs = []; // For storing student ranks
        $bulletins = []; // For storing student bulletins

        if ($classe_id) {
            // Get students through inscriptions for the selected class and year
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                // CORRECTION : Inversion de la logique du filtre
                if (!$include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions.classe.filiere', 'inscriptions.classe.niveau'])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            Log::info('Ã‰tudiants rÃ©cupÃ©rÃ©s pour la classe', [
                'classe_id' => $classe_id,
                'annee_universitaire_id' => $annee_universitaire_id,
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, also get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les pÃ©riodes" est sÃ©lectionnÃ©
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spÃ©cifiÃ©, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                Log::info('Notes rÃ©cupÃ©rÃ©es pour les Ã©tudiants', [
                    'etudiants_count' => $etudiants->count(),
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les pÃ©riodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins
                $this->getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, $bulletins);
            }
        } else if ($annee_universitaire_id) {
            // If no class selected but academic year is set, get all students enrolled in that year
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($annee_universitaire_id, $include_all_statuses) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);

                // Inverser la condition pour inclure tous les statuts par dÃ©faut
                if ($include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions' => function ($query) use ($annee_universitaire_id) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);
            }])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            \Log::info('Ã‰tudiants rÃ©cupÃ©rÃ©s par annÃ©e', [
                'annee_universitaire_id' => $annee_universitaire_id,
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les pÃ©riodes" est sÃ©lectionnÃ©
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->whereHas('evaluation', function ($query) use ($annee_universitaire_id) {
                        $query->where('annee_universitaire_id', $annee_universitaire_id);
                    })
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spÃ©cifiÃ©, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                \Log::info('Notes rÃ©cupÃ©rÃ©es par annÃ©e', [
                    'annee_id' => $annee_universitaire_id,
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les pÃ©riodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins - we don't have a specific class so use individual student inscriptions
                $this->getStudentBulletins($etudiants, null, $annee_universitaire_id, $semestre, $bulletins);
            }
        } else {
            // If no filters are applied, get all active students
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($include_all_statuses) {
                // Ne filtrer sur le statut 'active' que si include_all_statuses est false
                if (!$include_all_statuses) {
                $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions'])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            \Log::info('Ã‰tudiants rÃ©cupÃ©rÃ©s sans filtres', [
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les pÃ©riodes" est sÃ©lectionnÃ©
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spÃ©cifiÃ©, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                \Log::info('Notes rÃ©cupÃ©rÃ©es sans filtres', [
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les pÃ©riodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins - we don't have a specific class so use individual student inscriptions
                $this->getStudentBulletins($etudiants, null, $annee_universitaire_id, $semestre, $bulletins);
            }
        }

        return view('esbtp.resultats.index', compact(
            'classes',
            'periodes',
            'annees_universitaires',
            'classe_id',
            'classeObj',
            'classe',
            'semestre',
            'periode',
            'annee_universitaire_id',
            'annee_id',
            'anneeUniversitaire',
            'etudiants',
            'notes',
            'moyennes',
            'rangs',
            'bulletins'
        ));
    }

    /**
     * Helper method to calculate student statistics (averages and ranks)
     */
    private function calculateStudentStats($etudiants, $notes, &$moyennes, &$rangs)
    {
        $moyennes = [];
        $rangs = [];

        foreach ($etudiants as $etudiant) {
            $notesEtudiant = $notes->where('etudiant_id', $etudiant->id);

            if ($notesEtudiant->count() > 0) {
                $totalPoints = 0;
                $totalCoefficients = 0;

                foreach ($notesEtudiant as $note) {
                    $coefficient = $note->matiere->coefficient ?? 1;
                    $totalPoints += $note->valeur * $coefficient;
                    $totalCoefficients += $coefficient;
                }

                $moyenne = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;
                $moyennes[$etudiant->id] = round($moyenne, 2);
            } else {
                $moyennes[$etudiant->id] = 0;
            }
        }

        // Calculer les rangs
        $moyennesTriees = arsort($moyennes);
        $rang = 1;
        $derniereMoyenne = null;
        $compteur = 0;

        foreach ($moyennes as $etudiantId => $moyenne) {
            $compteur++;
            if ($derniereMoyenne !== null && $moyenne < $derniereMoyenne) {
                $rang = $compteur;
            }
            $rangs[$etudiantId] = $rang;
            $derniereMoyenne = $moyenne;
        }
    }

    /**
     * RÃ©cupÃ¨re les bulletins des Ã©tudiants pour une classe donnÃ©e
     */
    private function getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, &$bulletins)
    {
        $bulletins = [];

        foreach ($etudiants as $etudiant) {
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->where('classe_id', $classe_id)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->where('periode', $semestre)
                ->first();

            if ($bulletin) {
                $bulletins[$etudiant->id] = $bulletin;
            }
        }
    }

    /**
     * Affiche le bulletin de l'Ã©tudiant connectÃ©
     */
    public function monBulletin(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasRole('etudiant')) {
            return redirect()->route('dashboard')->with('error', 'AccÃ¨s non autorisÃ©');
        }

        $etudiant = $user->etudiant;
        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil Ã©tudiant non trouvÃ©');
        }

        // RÃ©cupÃ©rer l'annÃ©e universitaire active
        $anneeActive = ESBTPAnneeUniversitaire::where('is_active', true)->first();
        if (!$anneeActive) {
            return view('esbtp.bulletins.mon-bulletin', [
                'bulletins' => collect(),
                'etudiant' => $etudiant,
                'message' => 'Aucune annÃ©e universitaire active trouvÃ©e'
            ]);
        }

        // RÃ©cupÃ©rer les bulletins de l'Ã©tudiant pour l'annÃ©e active
        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
